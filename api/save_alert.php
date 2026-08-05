<?php
/**
 * api/save_alert.php — Endpoint REST API untuk menyimpan alert sensor realtime ke MySQL & memicu Web Push / WhatsApp
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notifications.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = $_SESSION['user_id'] ?? null;
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) $data = $_POST;

$sensor_type    = $data['sensor_type']    ?? null;
$sensor_value   = isset($data['sensor_value']) ? (float)$data['sensor_value'] : null;
$threshold_type = $data['threshold_type'] ?? 'above_max';
$message        = $data['message']        ?? null;
$greenhouse_id  = (int)($data['greenhouse_id'] ?? 0);

if (!$user_id && $greenhouse_id > 0) {
    $uStmt = $pdo->prepare("SELECT user_id FROM greenhouses WHERE id = ?");
    $uStmt->execute([$greenhouse_id]);
    $user_id = $uStmt->fetchColumn();
}
if (!$user_id) $user_id = 1;

if ($greenhouse_id <= 0) {
    $ghStmt = $pdo->prepare("SELECT id FROM greenhouses WHERE user_id = ? ORDER BY id ASC LIMIT 1");
    $ghStmt->execute([$user_id]);
    $gh = $ghStmt->fetch();
    $greenhouse_id = $gh ? (int)$gh['id'] : 1;
}

if (!$sensor_type || $sensor_value === null || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required alert fields']);
    exit;
}

try {
    // Anti-spam check: check if same alert type was created in last 5 minutes for this user
    $checkStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM alerts
         WHERE user_id = ? AND sensor_type = ? AND threshold_type = ?
           AND triggered_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
    );
    $checkStmt->execute([$user_id, $sensor_type, $threshold_type]);
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => true, 'message' => 'Duplicate alert skipped (anti-spam 5 min)']);
        exit;
    }

    $ins = $pdo->prepare(
        "INSERT INTO alerts (greenhouse_id, user_id, sensor_type, sensor_value, threshold_type, message, is_read, triggered_at)
         VALUES (?, ?, ?, ?, ?, ?, 0, NOW())"
    );
    $ins->execute([$greenhouse_id, $user_id, $sensor_type, $sensor_value, $threshold_type, $message]);
    $alert_id = $pdo->lastInsertId();

    // Trigger Web Push Notification
    sendWebPush($pdo, $user_id, '🚨 Peringatan Sensor MicroClimeet', $message, '/petani/alerts.php');

    echo json_encode([
        'success'   => true,
        'alert_id'  => $alert_id,
        'message'   => 'Realtime alert saved & Web Push triggered successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save alert: ' . $e->getMessage()]);
}
