<?php
/**
 * api/save_crop_cycle.php
 * API endpoint untuk memperbarui tanggal tanam & komoditas siklus tanam greenhouse
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$gh_id   = (int)($data['greenhouse_id'] ?? 0);
$crop    = trim($data['crop_name'] ?? 'Melon Premium');
$pDate   = trim($data['planting_date'] ?? date('Y-m-d'));
$hDate   = trim($data['estimated_harvest_date'] ?? '');
$targetEc = (float)($data['target_ec'] ?? 2.0);
$targetPh = (float)($data['target_ph'] ?? 6.5);

if ($gh_id <= 0 || empty($pDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'greenhouse_id & planting_date required']);
    exit;
}

// Auto calculate harvest date if empty (default 75 days)
if (empty($hDate)) {
    $hDate = date('Y-m-d', strtotime($pDate . ' +75 days'));
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO crop_cycles (greenhouse_id, crop_name, planting_date, estimated_harvest_date, target_ec, target_ph)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            crop_name=VALUES(crop_name),
            planting_date=VALUES(planting_date),
            estimated_harvest_date=VALUES(estimated_harvest_date),
            target_ec=VALUES(target_ec),
            target_ph=VALUES(target_ph)"
    );
    $stmt->execute([$gh_id, $crop, $pDate, $hDate, $targetEc, $targetPh]);

    // Also update greenhouses table commodity column
    $pdo->prepare("UPDATE greenhouses SET commodity = ? WHERE id = ?")->execute([$crop, $gh_id]);

    // Log activity
    $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?,?)")
        ->execute([$_SESSION['user_id'], "Memperbarui jadwal siklus tanam '$crop' (Greenhouse #$gh_id)"]);

    echo json_encode(['success' => true, 'message' => 'Siklus tanam berhasil diperbarui!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database update failed: ' . $e->getMessage()]);
}
