<?php
/**
 * verify_email_otp.php
 * Halaman Verifikasi Kode OTP 6-Digit Gmail (Pendaftaran & 2FA Login)
 */

define('AUTH_BASE', '');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mail_helper.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$purpose = in_array($_GET['purpose'] ?? '', ['register', '2fa', 'reset']) ? $_GET['purpose'] : 'register';
$message = '';
$error   = '';

// Determine target email
$target_email = '';
$user_id      = 0;

if ($purpose === '2fa') {
    $target_email = $_SESSION['pending_2fa_email'] ?? '';
    $user_id      = $_SESSION['pending_2fa_user_id'] ?? 0;
} elseif ($purpose === 'reset') {
    $target_email = $_SESSION['pending_reset_email'] ?? '';
} else {
    $user_id      = $_SESSION['pending_signup_user_id'] ?? ($_SESSION['user_id'] ?? 0);
    if ($user_id > 0) {
        $u_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $u_stmt->execute([$user_id]);
        $target_email = $u_stmt->fetchColumn() ?: '';
    }
}

if (empty($target_email)) {
    $target_email = $_GET['email'] ?? ($_SESSION['last_sent_otp_email'] ?? '');
}

if (!empty($target_email)) {
    $target_email = strtolower(trim($target_email));
    $_SESSION['last_sent_otp_email'] = $target_email;
}

// Change account request
if (isset($_GET['action']) && $_GET['action'] === 'change_account') {
    unset($_SESSION['pending_signup_user_id']);
    unset($_SESSION['last_sent_otp_email']);
    unset($_SESSION['temp_google_signup']);
    header("Location: signup.php");
    exit;
}

// Resend OTP request
if (isset($_GET['resend']) && !empty($target_email)) {
    $res = generateAndSendEmailOTP($pdo, $target_email, $purpose);
    if ($res['success']) {
        $message = "Kode OTP baru telah dikirimkan ke " . htmlspecialchars($target_email) . ". Silakan cek inbox/spam Gmail Anda.";
    } else {
        $error = $res['error'];
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_code = trim($_POST['otp_code'] ?? '');

    if (empty($target_email)) {
        $error = "Sesi email verifikasi telah berakhir/kosong. Silakan klik 'Gunakan Email / Akun Lain' di bawah untuk mendaftar ulang.";
    } elseif (strlen($otp_code) !== 6 || !ctype_digit($otp_code)) {
        $error = "Kode OTP harus berupa 6 angka rahasia.";
    } else {
        $isValid = verifyEmailOTP($pdo, $target_email, $otp_code, $purpose);

        if ($isValid) {
            if ($purpose === '2fa') {
                // 2FA Verification Success -> Finalize login
                $uid = $_SESSION['pending_2fa_user_id'] ?? 0;
                $u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $u_stmt->execute([$uid]);
                $user = $u_stmt->fetch();

                if ($user) {
                    if ($user['status'] === 'suspended') {
                        $error = "🚫 Akun Anda telah ditangguhkan/diblokir oleh Administrator.";
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role']    = $user['role'];
                        $_SESSION['name']    = $user['name'];
                        $_SESSION['photo']   = $user['photo'];

                        unset($_SESSION['pending_2fa_user_id']);
                        unset($_SESSION['pending_2fa_email']);

                        $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)")
                            ->execute([$user['id'], "Berhasil verifikasi 2FA via Email Gmail & masuk ke dashboard"]);

                        header("Location: " . ($user['role'] === 'admin' ? 'admin/index.php' : 'petani/index.php'));
                        exit;
                    }
                }
            } elseif ($purpose === 'reset') {
                // Reset Password OTP Success -> Redirect to set new password
                $_SESSION['reset_verified_email'] = $target_email;
                unset($_SESSION['pending_reset_email']);
                header("Location: reset_password.php");
                exit;
            } else {
                // Registration OTP Verification Success -> Mark email verified
                $u_stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = ? OR id = ?");
                $u_stmt->execute([strtolower($target_email), $user_id]);
                $user = $u_stmt->fetch();

                if ($user) {
                    $pdo->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = ?")->execute([$user['id']]);

                    unset($_SESSION['pending_signup_user_id']);

                    // Check if Admin approved account
                    if ($user['status'] === 'active') {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role']    = $user['role'];
                        $_SESSION['name']    = $user['name'];
                        $_SESSION['photo']   = $user['photo'];

                        header("Location: petani/index.php");
                        exit;
                    } else {
                        // User needs Admin Approval first
                        header("Location: pending_approval.php?email=" . urlencode($user['email']));
                        exit;
                    }
                }
                header("Location: pending_approval.php");
                exit;
            }
        } else {
            $error = "Kode OTP salah atau telah kadaluarsa. Silakan periksa kembali inbox Gmail Anda.";
        }
    }
}

$page_title = ($purpose === '2fa' ? "Verifikasi 2FA Email" : "Verifikasi Email Gmail") . " — MicroClimeet";
$custom_css = ['auth.css'];
include 'includes/header.php';
?>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; position: relative; background: url('assets/images/backgrounds/melonbg_clean.png') no-repeat center center / cover fixed;">
    <!-- Organic Emerald Tint Overlay (Soft & Seamless Blending) -->
    <div style="position: absolute; inset: 0; background: linear-gradient(135deg, rgba(13, 93, 49, 0.40) 0%, rgba(6, 44, 23, 0.65) 100%); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 1;"></div>

    <div class="auth-card glass-card" style="position: relative; z-index: 2; width: 100%; max-width: 440px; padding: 36px; border-radius: 28px; background: rgba(255, 255, 255, 0.60); backdrop-filter: blur(28px) saturate(190%); -webkit-backdrop-filter: blur(28px) saturate(190%); border: 1.5px solid rgba(255, 255, 255, 0.65); box-shadow: 0 30px 80px rgba(6, 44, 23, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.8); text-align: center;">

        <div style="width:64px;height:64px;border-radius:50%;background:rgba(13,93,49,0.1);color:#0D5D31;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <i data-lucide="mail-check" style="width:32px;height:32px;"></i>
        </div>

        <h2 style="font-family:var(--font-heading);font-weight:800;color:#0D5D31;margin:0 0 6px 0;font-size:1.35rem;">
            <?php echo $purpose === '2fa' ? 'Verifikasi 2FA Gmail' : 'Verifikasi Email Gmail'; ?>
        </h2>
        <p style="font-size:0.86rem;color:var(--text-secondary);margin:0 0 20px 0;line-height:1.4;">
            Masukkan <strong>6 angka kode OTP</strong> yang telah dikirimkan ke alamat email:<br>
            <strong style="color:var(--text-primary);"><?php echo htmlspecialchars($target_email ?: 'Gmail Anda'); ?></strong>
        </p>

        <?php if ($message): ?>
        <div style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.3);border-radius:12px;padding:10px 14px;margin-bottom:18px;color:#166534;font-size:0.83rem;">
            ✓ <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:12px;padding:10px 14px;margin-bottom:18px;color:#b91c1c;font-size:0.85rem;">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom:20px;">
                <input type="text" name="otp_code" maxlength="6" autofocus placeholder="• • • • • •" required
                       style="width:100%;padding:14px;border-radius:16px;border:2px solid #0D5D31;font-size:1.6rem;font-weight:900;letter-spacing:10px;text-align:center;font-family:monospace;background:#f0f7f2;color:#0D5D31;box-sizing:border-box;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;border-radius:30px;font-size:0.95rem;font-weight:800;background:#0D5D31!important;color:#fff!important;border:none;cursor:pointer;box-shadow:0 6px 20px rgba(13,93,49,0.3);">
                Verifikasi & Masuk
            </button>
        </form>

        <div style="margin-top:20px;font-size:0.85rem;color:#334155;font-weight:600;">
            Tidak menerima kode OTP?
            <a href="verify_email_otp.php?purpose=<?php echo urlencode($purpose); ?>&resend=1" style="color: #064e3b; font-weight: 800; text-decoration: none;">Kirim Ulang OTP</a>
        </div>

        <div style="margin-top:14px;">
            <a href="https://wa.me/6283829634058?text=Halo%20Admin%20MicroClimeet,%20saya%20mengalami%20kendala%20verifikasi%20kode%20OTP%20Gmail%20untuk%20email%20<?php echo urlencode($target_email); ?>" target="_blank" style="display:inline-flex;align-items:center;gap:6px;color:#064e3b;font-weight:800;font-size:0.82rem;text-decoration:none;">
                 Butuh Bantuan OTP? Hubungi Admin via WhatsApp
            </a>
        </div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px dashed rgba(0,0,0,0.1);display:flex;flex-direction:column;gap:10px;">
            <a href="verify_email_otp.php?action=change_account" style="color:#0f172a;font-size:0.84rem;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                 Gunakan Email / Akun Lain
            </a>
            <a href="index.php" style="color:#064e3b;font-size:0.8rem;font-weight:800;text-decoration:none;">
                 Kembali ke Halaman Utama (Landing Page)
            </a>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
