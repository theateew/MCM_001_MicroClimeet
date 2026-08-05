<?php
/**
 * api/push_subscribe.php
 * POST endpoint — simpan atau hapus Web Push subscription ke DB
 *
 * Body JSON:
 *   action      "subscribe" | "unsubscribe"
 *   endpoint    string
 *   p256dh      string
 *   auth        string
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$raw     = file_get_contents('php://input');
$body    = json_decode($raw, true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

$action   = $body['action']   ?? 'subscribe';
$endpoint = $body['endpoint'] ?? '';
$p256dh   = $body['p256dh']   ?? '';
$authKey  = $body['auth']     ?? '';

if ($action === 'unsubscribe') {
    $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
    $stmt->execute([$user_id, $endpoint]);
    echo json_encode(['success' => true, 'action' => 'unsubscribed']);
    exit;
}

if ($action === 'test') {
    require_once __DIR__ . '/../includes/notifications.php';
    sendWebPush(
        $pdo,
        $user_id,
        '🔔 Uji Web Push MicroClimeet',
        'Notifikasi browser berfungsi dengan baik! Sistem siap menerima peringatan sensor.',
        '/petani/alerts.php'
    );
    echo json_encode(['success' => true, 'message' => 'Notifikasi uji coba berhasil diproses!']);
    exit;
}

// Subscribe: upsert (jika endpoint sudah ada, update)
if (empty($endpoint) || empty($p256dh) || empty($authKey)) {
    http_response_code(400);
    echo json_encode(['error' => 'endpoint, p256dh, and auth are required']);
    exit;
}

// Hapus subscription lama dengan endpoint yang sama (jika ada)
$pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?")->execute([$endpoint]);

// Insert baru
$ins = $pdo->prepare(
    "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth_key) VALUES (?, ?, ?, ?)"
);
$ins->execute([$user_id, $endpoint, $p256dh, $authKey]);

echo json_encode(['success' => true, 'action' => 'subscribed']);
