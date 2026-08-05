<?php
/**
 * forgot_password.php
 * Halaman Minta Reset Password (Khusus Petani Email Biasa)
 */

define('AUTH_BASE', '');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mail_helper.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Alamat email tidak valid!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "Email tidak ditemukan di sistem. Silakan mendaftar terlebih dahulu.";
            } elseif (!empty($user['google_id']) && strpos($user['password'], '$2y$') === false) {
                $error = "Akun ini terhubung via Google OAuth. Silakan login langsung menggunakan tombol Google Sign-In.";
            } else {
                // Send 6-digit OTP code to Gmail for reset
                $res = generateAndSendEmailOTP($pdo, $email, 'reset');
                if ($res['success']) {
                    $_SESSION['pending_reset_email'] = $email;
                    $_SESSION['last_sent_otp_email'] = $email;

                    header("Location: verify_email_otp.php?purpose=reset");
                    exit;
                } else {
                    $error = $res['error'];
                }
            }
        } catch (PDOException $e) {
            $error = "Kesalahan sistem: " . $e->getMessage();
        }
    }
}

$page_title = "Lupa Password — MicroClimeet";
$custom_css = ['auth.css'];
include 'includes/header.php';
?>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; position: relative; background: url('assets/images/backgrounds/greenhouse.png') no-repeat center center / cover fixed;">
    <!-- Organic Emerald Tint Overlay (Soft & Seamless Blending) -->
    <div style="position: absolute; inset: 0; background: linear-gradient(135deg, rgba(13, 93, 49, 0.40) 0%, rgba(6, 44, 23, 0.65) 100%); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 1;"></div>

    <div class="auth-card glass-card" style="position: relative; z-index: 2; width: 100%; max-width: 440px; padding: 36px; border-radius: 28px; background: rgba(255, 255, 255, 0.60); backdrop-filter: blur(28px) saturate(190%); -webkit-backdrop-filter: blur(28px) saturate(190%); border: 1.5px solid rgba(255, 255, 255, 0.65); box-shadow: 0 30px 80px rgba(6, 44, 23, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.8); text-align: center;">

        <div style="width:64px;height:64px;border-radius:50%;background:rgba(13,93,49,0.1);color:#0D5D31;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <i data-lucide="key-round" style="width:32px;height:32px;"></i>
        </div>

        <h2 style="font-family:var(--font-heading);font-weight:800;color:#0D5D31;margin:0 0 6px 0;font-size:1.35rem;">
            Lupa Password
        </h2>
        <p style="font-size:0.86rem;color:var(--text-secondary);margin:0 0 20px 0;line-height:1.4;">
            Masukkan email terdaftar Anda. Kode OTP 6-Digit akan dikirimkan ke Gmail untuk mereset kata sandi.
        </p>

        <?php if ($error): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:12px;padding:10px 14px;margin-bottom:18px;color:#b91c1c;font-size:0.85rem;text-align:left;">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom:20px;text-align:left;">
                <label style="display:block;font-weight:700;font-size:0.83rem;color:var(--text-primary);margin-bottom:6px;">Alamat Email Gmail</label>
                <input type="email" name="email" required placeholder="nama@gmail.com"
                       style="width:100%;padding:12px 16px;border-radius:14px;border:1.5px solid rgba(0,0,0,0.15);font-size:0.92rem;box-sizing:border-box;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;border-radius:30px;font-size:0.95rem;font-weight:800;background:#0D5D31!important;color:#fff!important;border:none;cursor:pointer;box-shadow:0 6px 20px rgba(13,93,49,0.3);">
                🔑 Kirim Kode OTP Reset
            </button>
        </form>

        <div style="margin-top:22px;font-size:0.85rem;color:#334155;font-weight:600;">
            Sudah ingat kata sandi? <a href="login.php" style="color: #064e3b; font-weight: 800; text-decoration: none;">Masuk di sini</a>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
