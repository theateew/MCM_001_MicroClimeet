<?php
/**
 * api/firebase_webhook.php
 * Menerima POST dari Firebase Cloud Function
 * dan menyimpan data ke MySQL (telemetry_logs + alert check)
 *
 * Firebase Cloud Function memanggil URL ini setiap kali ada write di node sensor.
 * Contoh payload JSON dari Firebase CF:
 * {
 *   "secret":        "WEBHOOK_SECRET_HERE",
 *   "greenhouse_id": 1,
 *   "temperature":   27.5,
 *   "humidity":      72.0,
 *   "air_quality":   35.0,
 *   "uv":            3.2
 * }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/push_config.php';
require_once __DIR__ . '/../includes/notifications.php';

// ── Keamanan: verifikasi secret ─────────────────────────────
define('WEBHOOK_SECRET', 'mcm_firebase_secret_2026'); // Ganti dengan secret acak yang kuat

$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!$payload || ($payload['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ── Validasi field ───────────────────────────────────────────
$greenhouse_id = (int)($payload['greenhouse_id'] ?? 0);
if ($greenhouse_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'greenhouse_id required']);
    exit;
}

$temperature = isset($payload['temperature']) ? (float)$payload['temperature'] : null;
$humidity    = isset($payload['humidity'])    ? (float)$payload['humidity']    : null;
$air_quality = isset($payload['air_quality']) ? (float)$payload['air_quality'] : null;
$uv          = isset($payload['uv'])          ? (float)$payload['uv']          : null;

// ── Simpan ke telemetry_logs ─────────────────────────────────
try {
    $cols = $pdo->query("DESCRIBE telemetry_logs")->fetchAll(PDO::FETCH_COLUMN);
    $hasDeviceId = in_array('device_id', $cols);
    $hasUvIndex  = in_array('uv_index', $cols);

    $idField = $hasDeviceId ? 'device_id' : 'greenhouse_id';
    $uvField = $hasUvIndex ? 'uv_index' : 'uv';

    $ins = $pdo->prepare(
        "INSERT INTO telemetry_logs ($idField, temperature, humidity, air_quality, $uvField)
         VALUES (?, ?, ?, ?, ?)"
    );
    $ins->execute([$greenhouse_id, $temperature, $humidity, $air_quality, $uv]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
    exit;
}

// ── Ambil data user & greenhouse ─────────────────────────────
$ghStmt = $pdo->prepare(
    "SELECT g.user_id, u.phone, u.name
     FROM greenhouses g
     JOIN users u ON g.user_id = u.id
     WHERE g.id = ?"
);
$ghStmt->execute([$greenhouse_id]);
$ghInfo = $ghStmt->fetch();

if (!$ghInfo) {
    echo json_encode(['success' => true, 'alerts' => 0, 'warning' => 'greenhouse not found']);
    exit;
}

$user_id = (int)$ghInfo['user_id'];

// ── Ambil threshold ──────────────────────────────────────────
$thresholds = [];
$thStmt = $pdo->query("SELECT * FROM global_thresholds");
while ($row = $thStmt->fetch()) {
    $thresholds[$row['sensor_type']] = [
        'min' => (float)$row['min_val'],
        'max' => (float)$row['max_val'],
    ];
}

// ── Cek & trigger alert ──────────────────────────────────────
$data = array_filter([
    'temperature' => $temperature,
    'humidity'    => $humidity,
    'air_quality' => $air_quality,
    'uv'          => $uv,
], fn($v) => $v !== null);

$alertCount = checkAndTriggerAlerts(
    $pdo,
    $greenhouse_id,
    $user_id,
    $data,
    $thresholds,
    ['phone' => $ghInfo['phone'], 'name' => $ghInfo['name']]
);

// ── Update status greenhouse jika ada alert ──────────────────
if ($alertCount > 0) {
    $pdo->prepare("UPDATE greenhouses SET status = 'warning' WHERE id = ?")
        ->execute([$greenhouse_id]);
} else {
    // Auto-recover ke 'active' jika sebelumnya 'warning' dan sekarang semua normal
    $pdo->prepare(
        "UPDATE greenhouses SET status = 'active' 
         WHERE id = ? AND status = 'warning'"
    )->execute([$greenhouse_id]);
}

echo json_encode([
    'success'        => true,
    'greenhouse_id'  => $greenhouse_id,
    'alerts_created' => $alertCount,
    'recorded_at'    => date('Y-m-d H:i:s'),
]);
