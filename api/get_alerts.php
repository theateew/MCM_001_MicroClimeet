<?php
/**
 * api/get_alerts.php
 * GET endpoint — ambil daftar alert + tandai sudah dibaca
 *
 * Params:
 *   greenhouse_id  INT (optional, jika kosong: semua greenhouse milik user)
 *   unread_only    1|0 (default: 0 = semua)
 *   mark_read      1|0 (default: 0 = tidak menandai)
 *   limit          INT (default: 50)
 *   offset         INT (default: 0)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id     = (int)$_SESSION['user_id'];
$gh_id       = isset($_GET['greenhouse_id']) ? (int)$_GET['greenhouse_id'] : null;
$unread_only = (int)($_GET['unread_only'] ?? 0);
$mark_read   = (int)($_GET['mark_read']   ?? 0);
$limit       = min((int)($_GET['limit']   ?? 50), 200);
$offset      = (int)($_GET['offset'] ?? 0);

// ── Build query ──────────────────────────────────────────────
$conditions = ['a.user_id = ?'];
$params     = [$user_id];

if ($gh_id !== null) {
    $conditions[] = 'a.greenhouse_id = ?';
    $params[]     = $gh_id;
}
if ($unread_only) {
    $conditions[] = 'a.is_read = 0';
}

$where = implode(' AND ', $conditions);

$stmt = $pdo->prepare(
    "SELECT a.*, g.name AS greenhouse_name
     FROM alerts a
     JOIN greenhouses g ON a.greenhouse_id = g.id
     WHERE $where
     ORDER BY a.triggered_at DESC
     LIMIT $limit OFFSET $offset"
);
$stmt->execute($params);
$alerts = $stmt->fetchAll();

// ── Unread count ─────────────────────────────────────────────
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM alerts WHERE user_id = ? AND is_read = 0"
);
$countStmt->execute([$user_id]);
$unreadCount = (int)$countStmt->fetchColumn();

// ── Mark as read ─────────────────────────────────────────────
if ($mark_read) {
    $updateParams = [$user_id];
    $updateWhere  = 'user_id = ?';
    if ($gh_id !== null) {
        $updateWhere .= ' AND greenhouse_id = ?';
        $updateParams[] = $gh_id;
    }
    $pdo->prepare("UPDATE alerts SET is_read = 1 WHERE $updateWhere")
        ->execute($updateParams);
    $unreadCount = 0;
}

echo json_encode([
    'success'      => true,
    'unread_count' => $unreadCount,
    'count'        => count($alerts),
    'alerts'       => $alerts,
]);
