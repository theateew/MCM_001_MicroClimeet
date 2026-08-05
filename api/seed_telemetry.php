<?php
/**
 * api/seed_telemetry.php
 * Script generator data sampel riwayat telemetri sensor (7 hari terakhir).
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = $_SESSION['user_id'] ?? null;

// Ambil greenhouse milik user (atau default pertama)
$gh = null;
if ($user_id) {
    $ghStmt = $pdo->prepare("SELECT id, name FROM greenhouses WHERE user_id = ? ORDER BY id ASC LIMIT 1");
    $ghStmt->execute([$user_id]);
    $gh = $ghStmt->fetch();
}

if (empty($gh)) {
    $ghStmt = $pdo->query("SELECT id, name FROM greenhouses ORDER BY id ASC LIMIT 1");
    $gh = $ghStmt->fetch();
}

if (empty($gh)) {
    $insGh = $pdo->prepare("INSERT INTO greenhouses (user_id, name, commodity, location, status) VALUES (?, 'Greenhouse-PTIK', 'Melon Premium', 'Fakultas Teknik UNS', 'active')");
    $insGh->execute([$user_id ?: 1]);
    $gh_id = $pdo->lastInsertId();
    $gh_name = 'Greenhouse-PTIK';
} else {
    $gh_id = (int)$gh['id'];
    $gh_name = $gh['name'];
}

// Generate data 7 hari ke belakang sampai HARI INI (per 2 jam)
$now = time();
$sevenDaysAgo = $now - (7 * 86400);

// Check column names in telemetry_logs for schema flexibility
$cols = $pdo->query("DESCRIBE telemetry_logs")->fetchAll(PDO::FETCH_COLUMN);
$hasDeviceId  = in_array('device_id', $cols);
$hasUvIndex   = in_array('uv_index', $cols);
$hasCreatedAt = in_array('created_at', $cols);
$hasFlag      = in_array('status_flag', $cols);

$idField   = $hasDeviceId ? 'device_id' : 'greenhouse_id';
$uvField   = $hasUvIndex ? 'uv_index' : 'uv';
$timeField = $hasCreatedAt ? 'created_at' : 'recorded_at';

if ($hasFlag) {
    $insSql = "INSERT INTO telemetry_logs ($idField, temperature, humidity, air_quality, $uvField, status_flag, $timeField) VALUES (?, ?, ?, ?, ?, ?, ?)";
} else {
    $insSql = "INSERT INTO telemetry_logs ($idField, temperature, humidity, air_quality, $uvField, $timeField) VALUES (?, ?, ?, ?, ?, ?)";
}

$insStmt = $pdo->prepare($insSql);

$count = 0;
for ($t = $sevenDaysAgo; $t <= $now; $t += 7200) { // Setiap 2 jam
    $dateStr = date('Y-m-d H:i:s', $t);
    $hour = (int)date('H', $t);
    
    if ($hour >= 6 && $hour <= 18) {
        $temp = round(28.0 + (sin(($hour - 6) / 12 * M_PI) * 6.5) + (mt_rand(-10, 10) / 10), 1);
        $hum  = round(75.0 - (sin(($hour - 6) / 12 * M_PI) * 20.0) + (mt_rand(-15, 15) / 10), 1);
        $uv   = round(sin(($hour - 6) / 12 * M_PI) * 8.5 + (mt_rand(-5, 5) / 10), 1);
    } else {
        $temp = round(23.5 + (mt_rand(-8, 8) / 10), 1);
        $hum  = round(82.0 + (mt_rand(-10, 10) / 10), 1);
        $uv   = 0.0;
    }
    
    $aqi = round(32.0 + (mt_rand(-5, 12)), 1);
    $flag = ($temp > 33 || $hum > 85) ? 'WASPADA' : (($temp > 37) ? 'BAHAYA' : 'NORMAL');

    try {
        if ($hasFlag) {
            $insStmt->execute([$gh_id, $temp, $hum, $aqi, $uv, $flag, $dateStr]);
        } else {
            $insStmt->execute([$gh_id, $temp, $hum, $aqi, $uv, $dateStr]);
        }
        $count++;
    } catch (PDOException $e) {
        // Skip duplicate errors
    }
}

echo json_encode([
    'success' => true,
    'message' => "Berhasil menggenerasi {$count} record riwayat data sampel telemetri untuk {$gh_name}.",
    'greenhouse_id' => $gh_id,
    'records_created' => $count
]);
