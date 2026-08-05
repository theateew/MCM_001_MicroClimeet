<?php
/**
 * api/save_telemetry.php
 * REST API untuk menyimpan data telemetri dari ESP32 / Firebase Sync ke MySQL (telemetry_logs)
 * Dan secara otomatis mengecek threshold anomali untuk membuat alert peringatan.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notifications.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    $data = $_POST;
}

$user_id = $_SESSION['user_id'] ?? null;

// Ambil greenhouse_id
$greenhouse_id = (int)($data['greenhouse_id'] ?? 0);

if ($greenhouse_id <= 0 && $user_id) {
    $ghStmt = $pdo->prepare("SELECT id FROM greenhouses WHERE user_id = ? AND status = 'active' ORDER BY id ASC LIMIT 1");
    $ghStmt->execute([$user_id]);
    $gh = $ghStmt->fetch();
    if ($gh) {
        $greenhouse_id = (int)$gh['id'];
    }
}

if ($greenhouse_id <= 0) {
    // Default fallback ke Greenhouse-PTIK pertama (id=1)
    $ghStmt = $pdo->query("SELECT id FROM greenhouses ORDER BY id ASC LIMIT 1");
    $gh = $ghStmt->fetch();
    if ($gh) {
        $greenhouse_id = (int)$gh['id'];
    }
}

if ($greenhouse_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid greenhouse_id required']);
    exit;
}

$temperature = isset($data['temperature']) ? (float)$data['temperature'] : null;
$humidity    = isset($data['humidity'])    ? (float)$data['humidity']    : null;
$air_quality = isset($data['air_quality']) ? (float)$data['air_quality'] : null;
$uv          = isset($data['uv'])          ? (float)$data['uv']          : null;

// Find or create device for greenhouse
$device_id = null;
try {
    $devStmt = $pdo->prepare("SELECT id FROM devices WHERE greenhouse_id = ? LIMIT 1");
    $devStmt->execute([$greenhouse_id]);
    $device_id = $devStmt->fetchColumn();

    if (!$device_id) {
        $insDev = $pdo->prepare("INSERT INTO devices (greenhouse_id, device_code, status) VALUES (?, ?, 'online')");
        $insDev->execute([$greenhouse_id, 'ESP32_GH_00' . $greenhouse_id]);
        $device_id = $pdo->lastInsertId();
    }
} catch (Exception $e) {
    $device_id = 4; // Fallback default device
}

$status_flag = 'NORMAL';
if ($temperature > 35 || $temperature < 15 || $humidity > 90 || $humidity < 40 || $air_quality > 150 || $uv > 9) {
    $status_flag = 'BAHAYA';
} elseif ($temperature > 30 || $humidity > 80 || $air_quality > 100 || $uv > 7) {
    $status_flag = 'WASPADA';
}

try {
    $ins = $pdo->prepare(
        "INSERT INTO telemetry_logs (device_id, temperature, humidity, air_quality, uv_index, status_flag, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())"
    );
    $ins->execute([$device_id, $temperature, $humidity, $air_quality, $uv, $status_flag]);
    $telemetry_id = $pdo->lastInsertId();

    // Check thresholds & generate alerts if abnormal
    $alertCount = 0;
    try {
        $telemetry_data = [
            'temperature' => $temperature,
            'humidity'    => $humidity,
            'air_quality' => $air_quality,
            'uv'          => $uv
        ];
        $alertCount = checkAndCreateAlerts($pdo, $greenhouse_id, $telemetry_data);
    } catch (Exception $e) {
        // Silently log alert creation exception
        error_log("[CheckAndCreateAlerts Exception] " . $e->getMessage());
    }

    echo json_encode([
        'success'       => true,
        'message'       => 'Telemetry logged successfully to MySQL',
        'telemetry_id'  => $telemetry_id,
        'greenhouse_id' => $greenhouse_id,
        'alerts_created' => $alertCount
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database insert failed: ' . $e->getMessage()]);
}
