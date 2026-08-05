<?php
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect user if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: petani/index.php");
    }
    exit;
}

$page_title = "Masuk ke Sistem";
$custom_css = ['landing.css'];
$base_url = '';

$message = '';
$error   = '';

if (isset($_GET['message']) && $_GET['message'] === 'password_reset_success') {
    $message = "Kata sandi Anda telah berhasil diperbarui! Silakan masuk dengan password baru Anda.";
}

if (isset($_GET['error']) && $_GET['error'] === 'account_deleted') {
    $error = "🚫 Akun Anda telah dihapus atau ditangguhkan oleh Administrator. Silakan hubungi Admin atau mendaftar kembali.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = "Email dan kata sandi wajib diisi!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = "⚠️ Alamat email Anda belum terdaftar atau salah! Silakan mendaftar terlebih dahulu.";
            } elseif (!password_verify($password, $user['password'])) {
                $error = "⚠️ Kata sandi (password) yang Anda masukkan salah!";
            } else {
                if ($user['status'] === 'inactive') {
                    $error = "Akun Anda belum disetujui Admin. Silakan tunggu persetujuan Admin MicroClimeet.";
                } elseif ($user['status'] === 'suspended') {
                    $error = "Akun Anda telah ditangguhkan/diblokir oleh Administrator. Silakan hubungi admin. ";
                } else {
                    // Check if 2FA via Email is enabled
                    if (!empty($user['two_factor_enabled'])) {
                        require_once 'config/mail_helper.php';
                        generateAndSendEmailOTP($pdo, $user['email'], '2fa');
                        $_SESSION['pending_2fa_user_id'] = $user['id'];
                        $_SESSION['pending_2fa_email']   = $user['email'];
                        header("Location: verify_email_otp.php?purpose=2fa");
                        exit;
                    }

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name']    = $user['name'];
                    $_SESSION['email']   = $user['email'];
                    $_SESSION['role']    = $user['role'];
                    $_SESSION['photo']   = $user['photo'] ?? null;
                    
                    // Log activity
                    $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                    $log_stmt->execute([$user['id'], "Melakukan login ke dalam sistem"]);
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header("Location: admin/index.php");
                    } else {
                        header("Location: petani/index.php");
                    }
                    exit;
                }
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

    <div class="glass-card" style="position: relative; z-index: 2; width: 100%; max-width: 440px; text-align: left; background: rgba(255, 255, 255, 0.60); backdrop-filter: blur(28px) saturate(190%); -webkit-backdrop-filter: blur(28px) saturate(190%); border: 1.5px solid rgba(255, 255, 255, 0.65); box-shadow: 0 30px 80px rgba(6, 44, 23, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.8); border-radius: 28px; padding: 36px;">
        <div style="text-align: center; margin-bottom: 28px;">
            <a href="index.php"><img src="assets/images/brand/logo.png" alt="MicroClimeet Logo" style="height: 46px; margin-bottom: 12px;"></a>
            <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 4px;">Selamat Datang <em style="font-family: var(--font-serif); font-weight: 400;">Kembali</em></h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Silakan masuk untuk mengakses dashboard pemantauan</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="glass-card" style="background: rgba(34, 197, 94, 0.12); border-color: rgba(34, 197, 94, 0.25); color: #15803d; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 700;">
                <i data-lucide="check-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25); color: #c93535; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                <i data-lucide="alert-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nama@email.com" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 14px;">
                <label for="password">Kata Sandi (Password)</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <label style="font-size: 0.85rem; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="checkbox" name="remember" style="accent-color: var(--accent-color);"> Ingat Saya
                </label>
                <a href="forgot_password.php" style="font-size: 0.85rem; color: var(--accent-orange); font-weight: 600;">Lupa Sandi?</a>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px 24px;">Masuk Sekarang</button>
        </form>

        <!-- Divider -->
        <div style="display:flex;align-items:center;margin:22px 0;">
            <div style="flex:1;height:1px;background:rgba(0,0,0,0.1);"></div>
            <span style="padding:0 12px;font-size:0.78rem;color:var(--text-secondary);font-weight:700;">ATAU</span>
            <div style="flex:1;height:1px;background:rgba(0,0,0,0.1);"></div>
        </div>

        <!-- Google Sign-In Firebase Button -->
        <button type="button" onclick="loginWithGoogleFirebase()" class="btn" style="width:100%;padding:12px;border-radius:30px;font-weight:700;font-size:0.88rem;background:#fff;color:#3c4043;border:1.5px solid #dadce0;display:inline-flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 2px 6px rgba(0,0,0,0.05);cursor:pointer;transition:all 0.2s;">
            <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.259h2.908c1.702-1.567 2.684-3.874 2.684-6.617z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z"/><path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
            <span>Masuk dengan Akun Google</span>
        </button>

        <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
        <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
        <script src="assets/js/firebase-config.js"></script>

        <script>
        function loginWithGoogleFirebase() {
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
                        alert('⚠️ ' + (res.error || 'Gagal login via Google.'));
                    }
                })
                .catch((err) => {
                    console.error('Firebase Auth Error:', err);
                    alert('⚠️ Gagal terhubung ke Google Auth: ' + err.message);
                });
        }
        </script>
        
        <div style="text-align: center; margin-top: 24px; font-size: 0.9rem; color: #334155; font-weight: 600;">
            Belum memiliki akun? <a href="signup.php" style="color: #064e3b; font-weight: 800; text-decoration: none;">Daftar di sini</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
