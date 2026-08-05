<?php
/**
 * config/mail_helper.php
 * Helper pengiriman & verifikasi Kode OTP 6-Digit via Gmail SMTP (microclimeet123@gmail.com)
 */

require_once __DIR__ . '/database.php';

/**
 * Pengiriman Email Sockets SMTP Resmi via Gmail Server (smtp.gmail.com:587 TLS)
 */
function sendGmailSMTP($toEmail, $subject, $htmlBody) {
    $smtpHost = "smtp.gmail.com";
    $smtpPort = 587;
    $username = "microclimeet123@gmail.com";
    $password = "bwhcjgtkdwhtnryg"; // Gmail App Password

    $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 15);
    if (!$socket) {
        error_log("Gmail SMTP Connection Error: $errstr ($errno)");
        return false;
    }

    $readSmtp = function($s) {
        $res = "";
        while ($line = fgets($s, 515)) {
            $res .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
        return $res;
    };

    $readSmtp($socket); // Read 220 banner

    fputs($socket, "EHLO localhost\r\n");
    $readSmtp($socket);

    fputs($socket, "STARTTLS\r\n");
    $startTlsRes = $readSmtp($socket);

    if (strpos($startTlsRes, '220') === false) {
        fclose($socket);
        return false;
    }

    // Enable TLS Encryption
    @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);

    fputs($socket, "EHLO localhost\r\n");
    $readSmtp($socket);

    fputs($socket, "AUTH LOGIN\r\n");
    $readSmtp($socket);

    fputs($socket, base64_encode($username) . "\r\n");
    $readSmtp($socket);

    fputs($socket, base64_encode($password) . "\r\n");
    $authRes = $readSmtp($socket);

    if (strpos($authRes, '235') === false) {
        error_log("Gmail SMTP Auth Failed: $authRes");
        fclose($socket);
        return false;
    }

    fputs($socket, "MAIL FROM: <{$username}>\r\n");
    $readSmtp($socket);

    fputs($socket, "RCPT TO: <{$toEmail}>\r\n");
    $readSmtp($socket);

    fputs($socket, "DATA\r\n");
    $readSmtp($socket);

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: MicroClimeet <{$username}>\r\n";
    $headers .= "To: <{$toEmail}>\r\n";
    $headers .= "Subject: {$subject}\r\n";
    $headers .= "Date: " . date("r") . "\r\n";

    $rawContent = $headers . "\r\n" . $htmlBody . "\r\n.\r\n";
    fputs($socket, $rawContent);
    $dataRes = $readSmtp($socket);

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return (strpos($dataRes, '250') !== false);
}

/**
 * Generate dan kirim kode OTP 6-Digit ke Gmail Pengguna
 */
if (!defined('ASIA_JAKARTA_TZ')) {
    date_default_timezone_set('Asia/Jakarta');
    define('ASIA_JAKARTA_TZ', true);
}

/**
 * Generate dan kirim kode OTP 6-Digit ke Gmail Pengguna
 */
function generateAndSendEmailOTP($pdo, $email, $purpose = 'register') {
    $email = strtolower(trim($email));
    $otpCode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);

    try {
        // Invalidate old OTPs for this email & purpose
        $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE LOWER(email) = ? AND purpose = ?");
        $stmt->execute([$email, $purpose]);

        // Insert new OTP record with 15 minutes expiration based on MySQL NOW()
        $ins = $pdo->prepare("INSERT INTO email_verifications (email, otp_code, purpose, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
        $ins->execute([$email, $otpCode, $purpose]);

        // Build HTML Email Body
        $purposeTitle = $purpose === '2fa' ? 'Verifikasi 2FA Login' : ($purpose === 'reset' ? 'Reset Password' : 'Verifikasi Pendaftaran Akun');
        
        $subject = "[$otpCode] Kode " . $purposeTitle . " - MicroClimeet";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f4; padding: 20px; margin: 0; }
                .email-card { max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; box-shadow: 0 8px 30px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
                .email-header { text-align: center; border-bottom: 2px solid #0D5D31; padding-bottom: 16px; margin-bottom: 24px; }
                .email-header h2 { color: #0D5D31; margin: 0; font-size: 22px; font-weight: 800; font-family: sans-serif; }
                .otp-box { background: #f0f7f2; border: 2px dashed #0D5D31; border-radius: 14px; padding: 20px; text-align: center; margin: 24px 0; }
                .otp-code { font-size: 34px; font-weight: 900; letter-spacing: 10px; color: #0D5D31; margin: 0; font-family: monospace; }
                .footer-text { font-size: 12px; color: #64748b; text-align: center; margin-top: 28px; }
            </style>
        </head>
        <body>
            <div class='email-card'>
                <div class='email-header'>
                    <h2>MicroClimeet</h2>
                </div>
                <p>Halo,</p>
                <p>Berikut adalah <strong>Kode OTP 6-Digit</strong> Anda untuk <strong>{$purposeTitle}</strong> di sistem Smart Greenhouse MicroClimeet:</p>
                
                <div class='otp-box'>
                    <p class='otp-code'>{$otpCode}</p>
                </div>
                
                <p style='font-size:13px; color:#475569;'>Kode ini berlaku selama <strong>15 menit</strong>. Jangan bagikan kode ini kepada siapapun demi keamanan akun Anda.</p>
                
                <div class='footer-text'>
                    <p>© " . date('Y') . " MicroClimeet. Hak Cipta Dilindungi.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Send REAL email via Gmail SMTP Sockets
        $sent = sendGmailSMTP($email, $subject, $message);

        $_SESSION['last_sent_otp_email'] = $email;

        if ($sent) {
            return ['success' => true, 'message' => 'Kode OTP 6-Digit telah terkirim secara nyata ke Inbox Gmail Anda.'];
        } else {
            return ['success' => true, 'message' => 'Kode OTP 6-Digit telah dihasilkan.'];
        }

    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Gagal membuat kode OTP: ' . $e->getMessage()];
    }
}

/**
 * Memverifikasi apakah Kode OTP 6-Digit valid dan belum kadaluarsa
 */
function verifyEmailOTP($pdo, $email, $otpCode, $purpose = 'register') {
    $email   = strtolower(trim($email));
    $otpCode = trim($otpCode);

    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM email_verifications
             WHERE LOWER(email) = ? AND otp_code = ? AND (purpose = ? OR purpose IS NULL) AND expires_at >= NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$email, $otpCode, $purpose]);
        $record = $stmt->fetch();

        if ($record) {
            // Delete used OTP
            $del = $pdo->prepare("DELETE FROM email_verifications WHERE id = ?");
            $del->execute([$record['id']]);
            return true;
        }

        return false;
    } catch (PDOException $e) {
        return false;
    }
}
