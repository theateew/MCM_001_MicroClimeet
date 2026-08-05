<?php
/**
 * api/get_thresholds.php
 * Endpoint REST API untuk mengambil threshold aktif (Per-Greenhouse / Per-Perangkat dengan Fallback ke Global Thresholds)
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$gh_id = isset($_GET['gh_id']) ? (int)$_GET['gh_id'] : 0;
$user_id = $_SESSION['user_id'] ?? 0;

if ($gh_id <= 0 && $user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM greenhouses WHERE user_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$user_id]);
        $gh_id = (int)($stmt->fetchColumn() ?: 0);
    } catch (PDOException $e) {}
}

if ($gh_id <= 0) {
    try {
        $gh_id = (int)($pdo->query("SELECT id FROM greenhouses ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);
    } catch (PDOException $e) {
        $gh_id = 1;
    }
}

// 1. Fetch global thresholds as baseline
$thresholds = [
    'temperature' => ['min' => 20.0, 'max' => 30.0],
    'humidity'    => ['min' => 60.0, 'max' => 85.0],
    'air_quality' => ['min' => 0.0,  'max' => 50.0],
    'uv'          => ['min' => 0.0,  'max' => 5.0]
];

try {
    $globals = $pdo->query("SELECT * FROM global_thresholds")->fetchAll();
    foreach ($globals as $g) {
        $thresholds[$g['sensor_type']] = [
            'min' => (float)$g['min_val'],
            'max' => (float)$g['max_val']
        ];
    }
} catch (PDOException $e) {}

// 2. Fetch specific greenhouse thresholds if configured
$is_custom = false;
if ($gh_id > 0) {
    try {
        $gh_stmt = $pdo->prepare("SELECT * FROM greenhouse_thresholds WHERE greenhouse_id = ?");
        $gh_stmt->execute([$gh_id]);
        $customs = $gh_stmt->fetchAll();
        if (!empty($customs)) {
            $is_custom = true;
            foreach ($customs as $c) {
                $thresholds[$c['sensor_type']] = [
                    'min' => (float)$c['min_val'],
                    'max' => (float)$c['max_val']
                ];
            }
        }
    } catch (PDOException $e) {}
}

echo json_encode([
    'success'       => true,
    'greenhouse_id' => $gh_id,
    'is_custom'     => $is_custom,
    'thresholds'    => $thresholds
]);
