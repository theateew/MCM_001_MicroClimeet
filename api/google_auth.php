<?php
/**
 * api/google_auth.php
 * Endpoint REST API untuk memverifikasi Google Identity Services Token & Sign In / Sign Up
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail_helper.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$google_id = '';
$email     = '';
$name      = '';
$picture   = '';

if (!empty($data['email'])) {
    // Direct payload from Firebase Auth Google Popup
    $email     = strtolower(trim($data['email']));
    $name      = trim($data['name'] ?? 'Petani Google');
    $picture   = trim($data['picture'] ?? '');
    $google_id = trim($data['google_id'] ?? $data['uid'] ?? '');
} elseif (!empty($data['credential'])) {
    // Google GSI JWT Token
    function decodeGoogleJWT($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;
        $payload = $parts[1];
        $substitutions = ['-' => '+', '_' => '/'];
        $padded = strtr($payload, $substitutions);
        $decoded = base64_decode($padded);
        return json_decode($decoded, true);
    }

    $payload = decodeGoogleJWT($data['credential']);
    if ($payload && !empty($payload['email'])) {
        $google_id = $payload['sub'] ?? '';
        $email     = strtolower(trim($payload['email']));
        $name      = trim($payload['name'] ?? 'Petani Google');
        $picture   = trim($payload['picture'] ?? '');
    }
}

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Kredensial atau Email Google tidak valid.']);
    exit;
}

try {
    // 1. Search existing user by google_id or email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$google_id, $email]);
    $user = $stmt->fetch();

    $isAdminEmail = (strtolower($email) === 'microclimeet123@gmail.com');
    $userRole     = $isAdminEmail ? 'admin' : 'petani';

    if ($user) {
        // User Exists
        if ($user['status'] === 'suspended') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => '🚫 Akun Anda telah ditangguhkan/diblokir oleh Administrator. Silakan hubungi admin.']);
            exit;
        }

        if ($user['status'] === 'inactive' && strtolower($email) !== 'microclimeet123@gmail.com') {
            echo json_encode([
                'success' => true,
                'redirect' => 'pending_approval.php?email=' . urlencode($user['email']),
                'message' => 'Akun Anda sedang menunggu persetujuan (approval) Admin MicroClimeet.'
            ]);
            exit;
        }

        // Ensure microclimeet123@gmail.com always has admin role and active status
        if ($isAdminEmail) {
            $pdo->prepare("UPDATE users SET role = 'admin', status = 'active' WHERE id = ?")->execute([$user['id']]);
            $user['role']   = 'admin';
            $user['status'] = 'active';
        }

        // Update google_id and photo if empty
        if (empty($user['google_id']) || empty($user['photo'])) {
            $up = $pdo->prepare("UPDATE users SET google_id = COALESCE(NULLIF(google_id, ''), ?), photo = COALESCE(NULLIF(photo, ''), ?) WHERE id = ?");
            $up->execute([$google_id, $picture, $user['id']]);
        }

        // Check if 2FA via Email is enabled
        if (!empty($user['two_factor_enabled'])) {
            // Generate OTP for 2FA login
            $otpRes = generateAndSendEmailOTP($pdo, $email, '2fa');
            $_SESSION['pending_2fa_user_id'] = $user['id'];
            $_SESSION['pending_2fa_email']   = $email;

            echo json_encode([
                'success' => true,
                'requires_2fa' => true,
                'redirect' => 'verify_email_otp.php?purpose=2fa',
                'message' => 'Kode OTP 2FA telah dikirim ke email Gmail Anda.'
            ]);
            exit;
        }

        // Standard direct login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['photo']   = !empty($user['photo']) ? $user['photo'] : $picture;

        // Log activity
        $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)")
            ->execute([$user['id'], "Login ke sistem via Google Sign-In"]);

        $targetRedirect = ($user['role'] === 'admin') ? 'admin/index.php' : 'petani/index.php';
        echo json_encode([
            'success' => true,
            'requires_2fa' => false,
            'redirect' => $targetRedirect,
            'message' => 'Login via Google berhasil!'
        ]);
        exit;
    } else {
        // New User
        if ($isAdminEmail) {
            // Instantly register microclimeet123@gmail.com as ADMIN
            $pass = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, phone, role, status, google_id, photo, email_verified_at)
                 VALUES (?, ?, ?, '083829634058', 'admin', 'active', ?, ?, NOW())"
            );
            $stmt->execute([$name, $email, $pass, $google_id, $picture]);
            $new_admin_id = $pdo->lastInsertId();

            $_SESSION['user_id'] = $new_admin_id;
            $_SESSION['role']    = 'admin';
            $_SESSION['name']    = $name;
            $_SESSION['photo']   = $picture;

            $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)")
                ->execute([$new_admin_id, "Mendaftar & masuk sebagai Administrator Utama via Google Sign-In"]);

            echo json_encode([
                'success' => true,
                'is_new_user' => true,
                'redirect' => 'admin/index.php',
                'message' => 'Selamat datang Admin MicroClimeet!'
            ]);
            exit;
        } else {
            // New Farmer -> Alihkan ke Form Complete Profile Setup (Komoditas & No. HP)
            $_SESSION['temp_google_signup'] = [
                'google_id' => $google_id,
                'email'     => $email,
                'name'      => $name,
                'picture'   => $picture
            ];

            echo json_encode([
                'success' => true,
                'is_new_user' => true,
                'redirect' => 'complete_google_signup.php',
                'message' => 'Google terhubung! Silakan lengkapi profil komoditas & nomor HP Anda.'
            ]);
            exit;
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
