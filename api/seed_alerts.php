<?php
/**
 * api/seed_alerts.php — Seeder Sampel Data Peringatan Anomali Sensor
 */
header('Content-Type: application/json');
define('AUTH_BASE', '../');
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = (int)($_SESSION['user_id'] ?? 1);

try {
    // Get user's greenhouses
    $ghStmt = $pdo->prepare("SELECT id, name FROM greenhouses WHERE user_id = ? ORDER BY id ASC LIMIT 1");
    $ghStmt->execute([$user_id]);
    $gh = $ghStmt->fetch();
    $gh_id = $gh ? (int)$gh['id'] : 1;
    $gh_name = $gh ? $gh['name'] : 'Greenhouse-PTIK';

    $sampleAlerts = [
        [
            'sensor_type'    => 'temperature',
            'sensor_value'   => 35.4,
            'threshold_type' => 'above_max',
            'message'        => '🚨 Suhu Udara terlalu tinggi: 35.4°C (maks: 30°C)',
            'time'           => date('Y-m-d H:i:s', strtotime('-15 minutes'))
        ],
        [
            'sensor_type'    => 'humidity',
            'sensor_value'   => 48.2,
            'threshold_type' => 'below_min',
            'message'        => '⚠️ Kelembapan terlalu rendah: 48.2% RH (min: 60% RH)',
            'time'           => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'sensor_type'    => 'air_quality',
            'sensor_value'   => 124,
            'threshold_type' => 'above_max',
            'message'        => '🚨 Kualitas Udara buruk (AQI Tinggi): 124 PM2.5 (maks: 50 PM2.5)',
            'time'           => date('Y-m-d H:i:s', strtotime('-5 hours'))
        ],
        [
            'sensor_type'    => 'uv',
            'sensor_value'   => 8.6,
            'threshold_type' => 'above_max',
            'message'        => '⚠️ Radiasi Indeks UV terlalu tinggi: 8.6 UV Index (maks: 5.0 UV Index)',
            'time'           => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'sensor_type'    => 'temperature',
            'sensor_value'   => 18.2,
            'threshold_type' => 'below_min',
            'message'        => '⚠️ Suhu Udara terlalu rendah: 18.2°C (min: 20°C)',
            'time'           => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
    ];

    $ins = $pdo->prepare(
        "INSERT INTO alerts (greenhouse_id, user_id, sensor_type, sensor_value, threshold_type, message, is_read, triggered_at)
         VALUES (?, ?, ?, ?, ?, ?, 0, ?)"
    );

    $count = 0;
    foreach ($sampleAlerts as $a) {
        $ins->execute([
            $gh_id,
            $user_id,
            $a['sensor_type'],
            $a['sensor_value'],
            $a['threshold_type'],
            $a['message'],
            $a['time']
        ]);
        $count++;
    }

    echo json_encode([
        'success'       => true,
        'message'       => "Berhasil membuat $count sampel peringatan sensor",
        'greenhouse_id' => $gh_id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal membuat sampel alert: ' . $e->getMessage()]);
}
