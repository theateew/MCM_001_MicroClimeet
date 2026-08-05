<?php
/**
 * api/check_approval_status.php
 * Endpoint AJAX untuk mengecek persetujuan (approval) Admin secara realtime.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$email   = strtolower(trim($_GET['email'] ?? ($_SESSION['last_sent_otp_email'] ?? '')));
$user_id = (int)($_SESSION['pending_signup_user_id'] ?? 0);

if (empty($email) && $user_id === 0) {
    echo json_encode(['approved' => false, 'error' => 'User identity missing']);
    exit;
}

try {
    if ($user_id > 0) {
        $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE LOWER(email) = ?");
        $stmt->execute([$email]);
    }

    $user = $stmt->fetch();

    if ($user && $user['status'] === 'active') {
        // Set user session automatically upon approval
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        // Clear pending signup session
        unset($_SESSION['pending_signup_user_id']);

        // Log approval activity
        try {
            $log = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
            $log->execute([$user['id'], "Akun disetujui Admin, otomatis masuk ke sistem"]);
        } catch (PDOException $e) {}

        $redirectUrl = ($user['role'] === 'admin') ? 'admin/index.php' : 'petani/index.php';

        echo json_encode([
            'approved' => true,
            'message'  => 'Akun Anda telah disetujui oleh Admin!',
            'redirect' => $redirectUrl
        ]);
        exit;
    }

    echo json_encode([
        'approved' => false,
        'status'   => $user['status'] ?? 'inactive'
    ]);

} catch (PDOException $e) {
    echo json_encode(['approved' => false, 'error' => $e->getMessage()]);
}
