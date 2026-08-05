<?php
/**
 * api/get_user_logs.php
 * Endpoint API untuk mengambil log aktivitas per-petani tertentu (Admin Only)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_auth('admin');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid user_id required']);
    exit;
}

try {
    $uStmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE id = ?");
    $uStmt->execute([$user_id]);
    $user = $uStmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $lStmt = $pdo->prepare(
        "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY accessed_at DESC LIMIT 50"
    );
    $lStmt->execute([$user_id]);
    $logs = $lStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'user'    => $user,
        'logs'    => $logs
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}
