<?php
/**
 * complete_google_signup.php
 * Form Pendaftaran Lanjutan Pendaftar Google: Komoditas Budidaya & Nomor Telepon/WA
 */

define('AUTH_BASE', '');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mail_helper.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$temp = $_SESSION['temp_google_signup'] ?? null;

if (!$temp || empty($temp['email'])) {
    header("Location: login.php");
    exit;
}

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commodity  = trim($_POST['commodity'] ?? 'Melon Premium');
    $phone      = trim($_POST['phone'] ?? '');
    $gh_name    = trim($_POST['greenhouse_name'] ?? 'Greenhouse Utama');
    $pass       = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT); // Auto-generated secure random password

    if (empty($phone) || empty($commodity)) {
        $error = "Komoditas budidaya dan nomor telepon wajib diisi!";
    } else {
        try {
            $user_email = strtolower(trim($temp['email']));
            // Check if user already exists by email or google_id
            $exist_stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = ? OR google_id = ? LIMIT 1");
            $exist_stmt->execute([$user_email, $temp['google_id']]);
            $existing_user = $exist_stmt->fetch();

            if ($existing_user) {
                $new_user_id = $existing_user['id'];
                // Update user profile info without forcing active status if inactive
                $up = $pdo->prepare(
                    "UPDATE users SET name = ?, phone = ?, google_id = ?, photo = COALESCE(NULLIF(photo, ''), ?) WHERE id = ?"
                );
                $up->execute([$temp['name'], $phone, $temp['google_id'], $temp['picture'], $new_user_id]);
            } else {
                // Insert new user into MySQL as inactive (Menunggu OTP & Approval Admin)
                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password, phone, role, status, google_id, photo)
                     VALUES (?, ?, ?, ?, 'petani', 'inactive', ?, ?)"
                );
                $stmt->execute([
                    $temp['name'],
                    $user_email,
                    $pass,
                    $phone,
                    $temp['google_id'],
                    $temp['picture']
                ]);
                $new_user_id = $pdo->lastInsertId();
            }

            // Check if greenhouse already exists for this user
            $gh_exist = $pdo->prepare("SELECT id FROM greenhouses WHERE user_id = ? LIMIT 1");
            $gh_exist->execute([$new_user_id]);
            $existing_gh_id = $gh_exist->fetchColumn();

            if (!$existing_gh_id) {
                // Insert default greenhouse for new farmer (Unassigned device_code until subscribed)
                $gh_stmt = $pdo->prepare(
                    "INSERT INTO greenhouses (user_id, name, commodity, location, status)
                     VALUES (?, ?, ?, 'Lahan Budidaya Utama', 'inactive')"
                );
                $gh_stmt->execute([
                    $new_user_id,
                    $gh_name,
                    $commodity
                ]);
                $new_gh_id = $pdo->lastInsertId();

                // Seed default crop cycle
                try {
                    $pdo->prepare(
                        "INSERT INTO crop_cycles (greenhouse_id, crop_name, planting_date, estimated_harvest_date)
                         VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 75 DAY))"
                    )->execute([$new_gh_id, $commodity]);
                } catch (PDOException $e) {}
            } else {
                // Update commodity on existing greenhouse
                $pdo->prepare("UPDATE greenhouses SET commodity = ? WHERE id = ?")->execute([$commodity, $existing_gh_id]);
            }

            // Log activity
            $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)")
                ->execute([$new_user_id, "Menyelesaikan pendaftaran profil petani via Google Sign-In"]);

            // Save pending registration user ID & email
            $_SESSION['pending_signup_user_id'] = $new_user_id;
            $_SESSION['last_sent_otp_email']    = $user_email;

            unset($_SESSION['temp_google_signup']);

            // Send Email OTP verification to Gmail
            generateAndSendEmailOTP($pdo, $user_email, 'register');

            header("Location: verify_email_otp.php?purpose=register&email=" . urlencode($user_email));
            exit;

        } catch (PDOException $e) {
            $error = "Gagal menyelesaikan pendaftaran: " . $e->getMessage();
        }
    }
}

$page_title = "Lengkapi Profil Budidaya — MicroClimeet";
$custom_css = ['auth.css'];
include 'includes/header.php';
?>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; position: relative; background: url('assets/images/backgrounds/greenhouse.png') no-repeat center center / cover fixed;">
    <!-- Organic Emerald Tint Overlay (Soft & Seamless Blending) -->
    <div style="position: absolute; inset: 0; background: linear-gradient(135deg, rgba(13, 93, 49, 0.40) 0%, rgba(6, 44, 23, 0.65) 100%); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 1;"></div>

    <div class="auth-card glass-card" style="position: relative; z-index: 2; width: 100%; max-width: 480px; padding: 36px; border-radius: 28px; background: rgba(255, 255, 255, 0.60); backdrop-filter: blur(28px) saturate(190%); -webkit-backdrop-filter: blur(28px) saturate(190%); border: 1.5px solid rgba(255, 255, 255, 0.65); box-shadow: 0 30px 80px rgba(6, 44, 23, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.8);">
        
        <!-- Header -->
        <div style="text-align:center;margin-bottom:24px;">
            <img src="assets/images/brand/logo.png" alt="MicroClimeet Logo" style="height:46px;margin-bottom:12px;">
            <h2 style="font-family:var(--font-heading);font-weight:800;color:#0D5D31;margin:0 0 6px 0;font-size:1.4rem;">Lengkapi Profil Budidaya</h2>
            <p style="font-size:0.86rem;color:var(--text-secondary);margin:0;">Satu langkah lagi untuk memulai monitoring smart greenhouse Anda.</p>
        </div>

        <!-- Google User Info Pill -->
        <div style="display:flex;align-items:center;gap:12px;background:rgba(13,93,49,0.06);border:1px solid rgba(13,93,49,0.18);border-radius:16px;padding:12px 16px;margin-bottom:22px;">
            <?php if (!empty($temp['picture'])): ?>
            <img src="<?php echo htmlspecialchars($temp['picture']); ?>" alt="Google Avatar" style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid #0D5D31;">
            <?php else: ?>
            <div style="width:42px;height:42px;border-radius:50%;background:#0D5D31;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;">G</div>
            <?php endif; ?>
            <div style="overflow:hidden;">
                <span style="display:block;font-weight:700;font-size:0.9rem;color:var(--text-primary);"><?php echo htmlspecialchars($temp['name']); ?></span>
                <span style="display:block;font-size:0.78rem;color:var(--text-secondary);white-space:nowrap;text-overflow:ellipsis;overflow:hidden;"><?php echo htmlspecialchars($temp['email']); ?></span>
            </div>
            <span style="margin-left:auto;background:#22c55e18;color:#15803d;border:1px solid #22c55e33;border-radius:20px;padding:2px 10px;font-size:0.72rem;font-weight:800;white-space:nowrap;">✓ Google Verified</span>
        </div>

        <?php if ($error): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:12px;padding:10px 14px;margin-bottom:18px;color:#b91c1c;font-size:0.85rem;">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST">
            <div style="margin-bottom:16px;">
                <label style="display:block;font-weight:700;font-size:0.83rem;color:var(--text-primary);margin-bottom:6px;">Komoditas Budidaya Utama</label>
                <select name="commodity" class="form-input" style="width:100%;padding:11px 14px;border-radius:12px;border:1.5px solid rgba(0,0,0,0.12);font-size:0.9rem;" required>
                    <option value="Melon Premium">Melon Premium (Hydroponic)</option>
                    <option value="Tomat Ceri">Tomat Ceri Greenhouse</option>
                    <option value="Cabai Paprika">Cabai Paprika Presisi</option>
                    <option value="Hydroponic Mix">Sayuran Hidroponik Mix</option>
                    <option value="Stroberi">Stroberi Modern</option>
                </select>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-weight:700;font-size:0.83rem;color:var(--text-primary);margin-bottom:6px;">No. Telepon / WhatsApp</label>
                <input type="text" name="phone" placeholder="Contoh: 081234567890" required style="width:100%;padding:11px 14px;border-radius:12px;border:1.5px solid rgba(0,0,0,0.12);font-size:0.9rem;box-sizing:border-box;">
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block;font-weight:700;font-size:0.83rem;color:var(--text-primary);margin-bottom:6px;">Nama Greenhouse Utama</label>
                <input type="text" name="greenhouse_name" value="Greenhouse Utama" required style="width:100%;padding:11px 14px;border-radius:12px;border:1.5px solid rgba(0,0,0,0.12);font-size:0.9rem;box-sizing:border-box;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;border-radius:30px;font-size:0.95rem;font-weight:800;background:#0D5D31!important;color:#fff!important;border:none;cursor:pointer;box-shadow:0 6px 20px rgba(13,93,49,0.3);display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                <i data-lucide="check-circle" style="width:18px;height:18px;"></i> Mulai Gunakan MicroClimeet
            </button>
        </form>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
