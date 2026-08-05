<?php
/**
 * reset_password.php
 * Halaman Setting Password Baru setelah OTP Reset Terverifikasi
 */

define('AUTH_BASE', '');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/firebase_helper.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$email = $_SESSION['reset_verified_email'] ?? '';

if (empty($email)) {
    header("Location: forgot_password.php");
    exit;
}

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass  = trim($_POST['password'] ?? '');
    $conf_pass = trim($_POST['confirm_password'] ?? '');

    if (strlen($new_pass) < 6) {
        $error = "Password minimal terdiri dari 6 karakter.";
    } elseif ($new_pass !== $conf_pass) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        try {
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
            
            // 1. Update password in MySQL database
            $up = $pdo->prepare("UPDATE users SET password = ? WHERE LOWER(email) = ?");
            $up->execute([$hashed, strtolower($email)]);

            // 2. Sync updated password with Firebase Auth
            firebaseUpdatePassword($email, $new_pass);

            // Log activity
            $u_stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
            $u_stmt->execute([strtolower($email)]);
            $uid = $u_stmt->fetchColumn();
            if ($uid) {
                $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)")
                    ->execute([$uid, "Berhasil memperbarui kata sandi akun (Reset Password)"]);
            }

            unset($_SESSION['reset_verified_email']);

            header("Location: login.php?message=password_reset_success");
            exit;

        } catch (PDOException $e) {
            $error = "Gagal mereset password: " . $e->getMessage();
        }
    }
}

$page_title = "Atur Password Baru — MicroClimeet";
$custom_css = ['auth.css'];
include 'includes/header.php';
?>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; position: relative; background: url('assets/images/backgrounds/greenhouse.png') no-repeat center center / cover fixed;">
    <!-- Organic Emerald Tint Overlay (Soft & Seamless Blending) -->
    <div style="position: absolute; inset: 0; background: linear-gradient(135deg, rgba(13, 93, 49, 0.40) 0%, rgba(6, 44, 23, 0.65) 100%); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 1;"></div>

    <div class="auth-card glass-card" style="position: relative; z-index: 2; width: 100%; max-width: 440px; padding: 36px; border-radius: 28px; background: rgba(255, 255, 255, 0.60); backdrop-filter: blur(28px) saturate(190%); -webkit-backdrop-filter: blur(28px) saturate(190%); border: 1.5px solid rgba(255, 255, 255, 0.65); box-shadow: 0 30px 80px rgba(6, 44, 23, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.8); text-align: center;">

        <div style="width:64px;height:64px;border-radius:50%;background:rgba(13,93,49,0.1);color:#0D5D31;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <i data-lucide="shield-check" style="width:32px;height:32px;"></i>
        </div>

        <h2 style="font-family:var(--font-heading);font-weight:800;color:#0D5D31;margin:0 0 6px 0;font-size:1.35rem;">
            Atur Password Baru
        </h2>
        <p style="font-size:0.86rem;color:var(--text-secondary);margin:0 0 20px 0;">
            Email Terverifikasi: <strong><?php echo htmlspecialchars($email); ?></strong>
        </p>

        <?php if ($error): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:12px;padding:10px 14px;margin-bottom:18px;color:#b91c1c;font-size:0.85rem;text-align:left;">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom:16px;text-align:left;">
                <label style="display:block;font-weight:700;font-size:0.83rem;color:var(--text-primary);margin-bottom:6px;">Kata Sandi Baru</label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter"
                       style="width:100%;padding:12px 16px;border-radius:14px;border:1.5px solid rgba(0,0,0,0.15);font-size:0.92rem;box-sizing:border-box;">
            </div>

            <div style="margin-bottom:22px;text-align:left;">
                <label style="display:block;font-weight:700;font-size:0.83rem;color:var(--text-primary);margin-bottom:6px;">Konfirmasi Kata Sandi Baru</label>
                <input type="password" name="confirm_password" required placeholder="Ketik ulang password baru"
                       style="width:100%;padding:12px 16px;border-radius:14px;border:1.5px solid rgba(0,0,0,0.15);font-size:0.92rem;box-sizing:border-box;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;border-radius:30px;font-size:0.95rem;font-weight:800;background:#0D5D31!important;color:#fff!important;border:none;cursor:pointer;box-shadow:0 6px 20px rgba(13,93,49,0.3);">
                💾 Simpan Password Baru
            </button>
        </form>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
