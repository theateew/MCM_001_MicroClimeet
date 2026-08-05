<?php
require_once 'config/database.php';

$page_title = "Pendaftaran Akun Petani";
$custom_css = ['landing.css'];
$base_url = '';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $commodity_select = trim($_POST['commodity_select'] ?? '');
    $commodity_custom = trim($_POST['commodity_custom'] ?? '');
    $commodity = ($commodity_select === 'other') ? $commodity_custom : $commodity_select;
    if (empty($commodity)) {
        $commodity = 'Melon Premium';
    }
    
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $error = "Semua kolom pendaftaran wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id, status, email_verified_at FROM users WHERE LOWER(email) = ?");
            $stmt->execute([$email]);
            $existing_user = $stmt->fetch();

            if ($existing_user) {
                // If user is already active OR email is verified
                if ($existing_user['status'] === 'active' || !empty($existing_user['email_verified_at'])) {
                    $error = "Email sudah terdaftar & aktif! Silakan gunakan email lain atau silakan masuk ke akun Anda.";
                } else {
                    // Pending unverified user re-submitting the form: Update profile & password, send new OTP
                    require_once 'config/firebase_helper.php';
                    $fbRes = firebaseRegisterEmailUser($email, $password);
                    $firebase_uid = $fbRes['uid'] ?? null;

                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $up_stmt = $pdo->prepare(
                        "UPDATE users SET name = ?, password = ?, phone = ?, google_id = COALESCE(google_id, ?) WHERE id = ?"
                    );
                    $up_stmt->execute([$name, $hashed_password, $phone, $firebase_uid, $existing_user['id']]);

                    // Update commodity on greenhouse
                    try {
                        $pdo->prepare("UPDATE greenhouses SET commodity = ? WHERE user_id = ?")->execute([$commodity, $existing_user['id']]);
                    } catch (PDOException $e) {}

                    // Send 6-digit OTP verification code to Gmail
                    require_once 'config/mail_helper.php';
                    generateAndSendEmailOTP($pdo, $email, 'register');
                    
                    $_SESSION['pending_signup_user_id'] = $existing_user['id'];
                    $_SESSION['last_sent_otp_email']    = $email;

                    header("Location: verify_email_otp.php?purpose=register&email=" . urlencode($email));
                    exit;
                }
            } else {
                require_once 'config/firebase_helper.php';
                // 1. Register email user in Firebase Auth
                $fbRes = firebaseRegisterEmailUser($email, $password);
                $firebase_uid = $fbRes['uid'] ?? null;

                // 2. Insert new user as inactive petani (Menunggu OTP & Approval Admin)
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password, phone, role, status, google_id)
                     VALUES (?, ?, ?, ?, 'petani', 'inactive', ?)"
                );
                $stmt->execute([$name, $email, $hashed_password, $phone, $firebase_uid]);
                
                $user_id = $pdo->lastInsertId();
                
                // Ensure commodity column exists in greenhouses table
                try {
                    $pdo->exec("ALTER TABLE greenhouses ADD COLUMN commodity VARCHAR(100) NULL AFTER name");
                } catch (PDOException $e) {}

                // Create initial greenhouse entry with chosen commodity (Unassigned device_code until subscribed)
                $gh_name = "Greenhouse Utama (" . $commodity . ")";
                $gh_stmt = $pdo->prepare("INSERT INTO greenhouses (user_id, name, commodity, status) VALUES (?, ?, ?, 'inactive')");
                $gh_stmt->execute([$user_id, $gh_name, $commodity]);

                // Log registration activity
                $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                $log_stmt->execute([$user_id, "Melakukan pendaftaran akun petani baru dengan komoditas: $commodity"]);

                // Send 6-digit OTP verification code to Gmail
                require_once 'config/mail_helper.php';
                generateAndSendEmailOTP($pdo, $email, 'register');
                
                // Save pending user ID without activating session login
                $_SESSION['pending_signup_user_id'] = $user_id;
                $_SESSION['last_sent_otp_email']    = $email;

                header("Location: verify_email_otp.php?purpose=register&email=" . urlencode($email));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<!-- Google Identity Services SDK -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; position: relative; background: url('assets/images/backgrounds/melonbg_clean.png') no-repeat center center / cover fixed;">
    <!-- Organic Emerald Tint Overlay (Soft & Seamless Blending) -->
    <div style="position: absolute; inset: 0; background: linear-gradient(135deg, rgba(13, 93, 49, 0.40) 0%, rgba(6, 44, 23, 0.65) 100%); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 1;"></div>

    <div class="glass-card" style="position: relative; z-index: 2; width: 100%; max-width: 480px; text-align: left; background: rgba(255, 255, 255, 0.60); backdrop-filter: blur(28px) saturate(190%); -webkit-backdrop-filter: blur(28px) saturate(190%); border: 1.5px solid rgba(255, 255, 255, 0.65); box-shadow: 0 30px 80px rgba(6, 44, 23, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.8); border-radius: 28px; padding: 36px;">
        <div style="text-align: center; margin-bottom: 28px;">
            <a href="index.php"><img src="assets/images/brand/logo.png" alt="MicroClimeet Logo" style="height: 46px; margin-bottom: 12px;"></a>
            <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 4px;">Daftar Akun <em style="font-family: var(--font-serif); font-weight: 400;">Baru</em></h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola iklim mikro greenhouse Anda hari ini</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="glass-card" style="background: rgba(74, 222, 128, 0.1); border-color: rgba(74, 222, 128, 0.25); color: #166534; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                <i data-lucide="check-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25); color: #c93535; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                <i data-lucide="alert-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($message)): ?>
        <form action="signup.php" method="POST">
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Masukkan nama lengkap Anda" required>
            </div>
            
            <div class="form-group">
                <label for="email">Alamat Email Gmail</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nama@gmail.com" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Nomor Telepon / WhatsApp</label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="Contoh: 08123456789" required>
            </div>

            <div class="form-group">
                <label for="commodity_select">Komoditas Budidaya Utama</label>
                <select name="commodity_select" id="commodity_select" class="form-control" onchange="toggleCustomCommodity(this)" required>
                    <option value="Melon Premium">Melon Premium</option>
                    <option value="Tomat Ceri">Tomat Ceri</option>
                    <option value="Paprika">Paprika</option>
                    <option value="Stroberi">Stroberi</option>
                    <option value="other">Lainnya (Isi Manual)</option>
                </select>
            </div>

            <div class="form-group" id="custom-commodity-group" style="display: none;">
                <label for="commodity_custom">Sebutkan Komoditas Lainnya</label>
                <input type="text" name="commodity_custom" id="commodity_custom" class="form-control" placeholder="mis. Buah Naga / Cabai Hydroponic">
            </div>
            
            <div class="form-group">
                <label for="password">Kata Sandi (Password)</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 14px 24px;">Daftar Akun Baru</button>
        </form>

        <!-- Divider -->
        <div style="display:flex;align-items:center;margin:22px 0;">
            <div style="flex:1;height:1px;background:rgba(0,0,0,0.1);"></div>
            <span style="padding:0 12px;font-size:0.78rem;color:var(--text-secondary);font-weight:700;">ATAU</span>
            <div style="flex:1;height:1px;background:rgba(0,0,0,0.1);"></div>
        </div>

        <!-- Google Sign-Up Firebase Button -->
        <button type="button" onclick="signUpWithGoogleFirebase()" class="btn" style="width:100%;padding:12px;border-radius:30px;font-weight:700;font-size:0.88rem;background:#fff;color:#3c4043;border:1.5px solid #dadce0;display:inline-flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 2px 6px rgba(0,0,0,0.05);cursor:pointer;transition:all 0.2s;">
            <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.259h2.908c1.702-1.567 2.684-3.874 2.684-6.617z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z"/><path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
            <span>Daftar Akun dengan Google</span>
        </button>

        <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
        <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
        <script src="assets/js/firebase-config.js"></script>

        <script>
        function signUpWithGoogleFirebase() {
            if (!firebase.apps.length) {
                firebase.initializeApp(firebaseConfig);
            }
            const provider = new firebase.auth.GoogleAuthProvider();
            firebase.auth().signInWithPopup(provider)
                .then((result) => {
                    const user = result.user;
                    return fetch('api/google_auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            email: user.email,
                            name: user.displayName,
                            picture: user.photoURL,
                            google_id: user.uid
                        })
                    });
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        window.location.href = res.redirect;
                    } else {
                        alert('⚠️ ' + (res.error || 'Gagal daftar via Google.'));
                    }
                })
                .catch((err) => {
                    console.error('Firebase Auth Error:', err);
                    alert('⚠️ Gagal terhubung ke Google Auth: ' + err.message);
                });
        }
        </script>

        <?php else: ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="https://wa.me/6283829634058?text=Halo%20Admin%20MicroClimeet,%20saya%20baru%20saja%20mendaftar%20dengan%20email%20<?php echo urlencode($email); ?>.%20Mohon%20untuk%20disetujui%20akunnya." target="_blank" class="btn btn-orange" style="width: 100%; margin-bottom: 12px;">
                    <i data-lucide="message-circle"></i> Konfirmasi ke WhatsApp Admin
                </a>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 24px; font-size: 0.9rem; color: #334155; font-weight: 600;">
            Sudah memiliki akun? <a href="login.php" style="color: #064e3b; font-weight: 800; text-decoration: none;">Masuk di sini</a>
        </div>
    </div>
</div>

<script>
function toggleCustomCommodity(select) {
    const customGroup = document.getElementById('custom-commodity-group');
    const customInput = document.getElementById('commodity_custom');
    if (select.value === 'other') {
        customGroup.style.display = 'block';
        customInput.required = true;
        customInput.focus();
    } else {
        customGroup.style.display = 'none';
        customInput.required = false;
    }
}
</script>

<?php include 'includes/footer.php'; ?>

