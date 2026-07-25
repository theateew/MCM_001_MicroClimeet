<?php
/**
 * api/get_history.php
 * GET endpoint — ambil riwayat telemetri sensor dengan filter
 *
 * Params:
 *   greenhouse_id  INT (wajib)
 *   sensor         string: temperature|humidity|air_quality|uv|all (default: all)
 *   from           YYYY-MM-DD (default: 7 hari lalu)
 *   to             YYYY-MM-DD (default: hari ini)
 *   group_by       hour|day (default: hour)
 *   limit          INT maks rows (default: 500)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Auth check (bisa petani atau admin)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id      = (int)$_SESSION['user_id'];
$role         = $_SESSION['role'];
$gh_id        = (int)($_GET['greenhouse_id'] ?? 0);
$sensor       = $_GET['sensor'] ?? 'all';
$from         = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to           = $_GET['to']   ?? date('Y-m-d');
$group_by     = in_array($_GET['group_by'] ?? '', ['hour', 'day']) ? $_GET['group_by'] : 'hour';
$limit        = min((int)($_GET['limit'] ?? 500), 2000);

if ($gh_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'greenhouse_id required']);
    exit;
}

// Verifikasi kepemilikan greenhouse (petani hanya bisa akses miliknya)
if ($role !== 'admin') {
    $check = $pdo->prepare("SELECT id FROM greenhouses WHERE id = ? AND user_id = ?");
    $check->execute([$gh_id, $user_id]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
}

// Bangun SELECT berdasarkan group_by
$timeFormat = $group_by === 'day'
    ? "DATE_FORMAT(recorded_at, '%Y-%m-%d')"
    : "DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00')";

$allowedSensors = ['temperature', 'humidity', 'air_quality', 'uv'];

if ($sensor === 'all') {
    $selectCols = implode(', ', array_map(fn($s) => "ROUND(AVG($s), 2) AS $s", $allowedSensors));
} elseif (in_array($sensor, $allowedSensors)) {
    $selectCols = "ROUND(AVG($sensor), 2) AS $sensor";
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid sensor']);
    exit;
}

$sql = "SELECT {$timeFormat} AS period, $selectCols
        FROM telemetry_logs
        WHERE greenhouse_id = ?
          AND recorded_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
        GROUP BY period
        ORDER BY period ASC
        LIMIT $limit";

$stmt = $pdo->prepare($sql);
$stmt->execute([$gh_id, $from, $to]);
$rows = $stmt->fetchAll();

echo json_encode([
    'success'      => true,
    'greenhouse_id'=> $gh_id,
    'sensor'       => $sensor,
    'group_by'     => $group_by,
    'from'         => $from,
    'to'           => $to,
    'count'        => count($rows),
    'data'         => $rows,
]);
