<?php
/**
 * petani/alerts.php — Daftar Peringatan Sensor
 */
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/notifications.php';
check_auth('petani');

$page_title  = 'Peringatan Sensor';
$custom_css  = ['dashboard.css'];
$base_url    = '../';
$active_menu = 'alerts';
$user_id     = (int)$_SESSION['user_id'];

// Mark all as read jika diminta
if (isset($_GET['mark_all_read'])) {
    $pdo->prepare("UPDATE alerts SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    header("Location: alerts.php");
    exit;
}

// Mark single as read
if (isset($_GET['mark_read'])) {
    $pdo->prepare("UPDATE alerts SET is_read = 1 WHERE id = ? AND user_id = ?")
        ->execute([(int)$_GET['mark_read'], $user_id]);
}

// Filter
$filter    = in_array($_GET['filter'] ?? '', ['unread', 'all']) ? ($_GET['filter'] ?? 'all') : 'all';
$gh_filter = (int)($_GET['gh'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;

// Get greenhouses for filter dropdown
$greenhouses = $pdo->prepare("SELECT id, name FROM greenhouses WHERE user_id = ? ORDER BY name");
$greenhouses->execute([$user_id]);
$greenhouses = $greenhouses->fetchAll();

// Build query
$conditions = ['a.user_id = ?'];
$params     = [$user_id];
if ($filter === 'unread') { $conditions[] = 'a.is_read = 0'; }
if ($gh_filter > 0) { $conditions[] = 'a.greenhouse_id = ?'; $params[] = $gh_filter; }
$where = implode(' AND ', $conditions);

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM alerts a WHERE $where");
$countStmt->execute($params);
$total    = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// Fetch alerts
$alertStmt = $pdo->prepare(
    "SELECT a.*, g.name AS greenhouse_name
     FROM alerts a
     LEFT JOIN greenhouses g ON a.greenhouse_id = g.id
     WHERE $where
     ORDER BY a.triggered_at DESC
     LIMIT $perPage OFFSET $offset"
);
$alertStmt->execute($params);
$alerts = $alertStmt->fetchAll();

$unread_count = getUnreadAlertCount($pdo, $user_id);

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2>🔔 Peringatan Sensor</h2>
                <p>Monitor dan kelola peringatan ambang batas sensor greenhouse Anda.</p>
            </div>
            <div class="header-controls">
                <?php if ($unread_count > 0): ?>
                <a href="alerts.php?mark_all_read=1" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">
                    <i data-lucide="check-check" style="width:15px;height:15px;"></i>
                    Tandai Semua Dibaca (<?php echo $unread_count; ?>)
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="glass-card" style="padding:14px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
            <span style="font-weight:600;font-size:0.85rem;color:var(--text-secondary);">Filter:</span>
            <div style="display:flex;gap:6px;">
                <a href="?filter=all&gh=<?php echo $gh_filter; ?>"
                   class="filter-chip <?php echo $filter==='all'?'active':''; ?>">Semua</a>
                <a href="?filter=unread&gh=<?php echo $gh_filter; ?>"
                   class="filter-chip <?php echo $filter==='unread'?'active':''; ?>">
                    Belum Dibaca <?php if($unread_count>0): ?><span class="chip-badge"><?php echo $unread_count; ?></span><?php endif; ?>
                </a>
            </div>
            <?php if (!empty($greenhouses)): ?>
            <select onchange="location.href='?filter=<?php echo $filter; ?>&gh='+this.value"
                    style="padding:6px 12px;border-radius:20px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.83rem;background:rgba(255,255,255,0.7);">
                <option value="0">Semua Greenhouse</option>
                <?php foreach ($greenhouses as $g): ?>
                <option value="<?php echo $g['id']; ?>" <?php echo $gh_filter==$g['id']?'selected':''; ?>>
                    <?php echo htmlspecialchars($g['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <span style="margin-left:auto;font-size:0.82rem;color:var(--text-secondary);"><?php echo $total; ?> peringatan</span>
        </div>

        <!-- Alerts List -->
        <?php if (empty($alerts)): ?>
        <div class="glass-card" style="text-align:center;padding:50px 24px;">
            <i data-lucide="bell-off" style="width:48px;height:48px;color:var(--text-secondary);margin-bottom:12px;"></i>
            <h3 style="font-family:var(--font-heading);margin-bottom:6px;">Tidak Ada Peringatan</h3>
            <p style="color:var(--text-secondary);font-size:0.9rem;">Semua sensor dalam kondisi normal. 🌿</p>
        </div>
        <?php else: ?>
        <div class="alerts-list" style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($alerts as $a): ?>
            <?php
                $isUnread = !$a['is_read'];
                $sensorIcons = ['temperature'=>'thermometer','humidity'=>'droplets','air_quality'=>'wind','uv'=>'sun'];
                $sensorLabels = ['temperature'=>'Suhu Udara','humidity'=>'Kelembapan','air_quality'=>'Kualitas Udara','uv'=>'Indeks UV'];
                $icon = $sensorIcons[$a['sensor_type']] ?? 'alert-circle';
                $label = $sensorLabels[$a['sensor_type']] ?? $a['sensor_type'];
                $isAbove = $a['threshold_type'] === 'above_max';
                $cardBg = $isUnread ? ($isAbove ? 'rgba(239,68,68,0.06)' : 'rgba(251,191,36,0.06)') : 'rgba(0,0,0,0.02)';
                $accentColor = $isAbove ? '#ef4444' : '#f59e0b';
            ?>
            <div style="background:<?php echo $cardBg; ?>;border:1px solid <?php echo $isUnread ? ($isAbove?'rgba(239,68,68,0.2)':'rgba(251,191,36,0.2)') : 'rgba(0,0,0,0.07)'; ?>;border-radius:14px;padding:14px 18px;display:flex;align-items:flex-start;gap:14px;<?php echo $isUnread?'':'opacity:0.7;'; ?>">
                <div style="width:40px;height:40px;border-radius:50%;background:<?php echo $isUnread?($isAbove?'rgba(239,68,68,0.12)':'rgba(251,191,36,0.12)'):'rgba(0,0,0,0.06)'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="<?php echo $icon; ?>" style="width:18px;height:18px;color:<?php echo $accentColor; ?>;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;flex-wrap:wrap;">
                        <span style="font-weight:700;font-size:0.88rem;"><?php echo htmlspecialchars($a['message']); ?></span>
                        <?php if ($isUnread): ?>
                        <span style="background:<?php echo $accentColor; ?>;color:#fff;border-radius:10px;padding:1px 7px;font-size:0.7rem;font-weight:700;">BARU</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;font-size:0.78rem;color:var(--text-secondary);">
                        <span>📍 <?php echo htmlspecialchars($a['greenhouse_name'] ?? '-'); ?></span>
                        <span>🕐 <?php echo date('d M Y, H:i', strtotime($a['triggered_at'])); ?></span>
                    </div>
                </div>
                <?php if ($isUnread): ?>
                <a href="?mark_read=<?php echo $a['id']; ?>&filter=<?php echo $filter; ?>&gh=<?php echo $gh_filter; ?>"
                   title="Tandai sudah dibaca"
                   style="color:var(--accent-color);text-decoration:none;flex-shrink:0;padding:4px;">
                    <i data-lucide="check-circle-2" style="width:18px;height:18px;"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div style="display:flex;justify-content:center;gap:6px;margin-top:24px;">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?filter=<?php echo $filter; ?>&gh=<?php echo $gh_filter; ?>&page=<?php echo $p; ?>"
               style="padding:7px 13px;border-radius:var(--radius-pill);border:1.5px solid <?php echo $p==$page?'var(--accent-color)':'rgba(0,0,0,0.1)'; ?>;background:<?php echo $p==$page?'var(--accent-color)':'transparent'; ?>;color:<?php echo $p==$page?'#fff':'var(--text-secondary)'; ?>;font-size:0.83rem;font-weight:600;text-decoration:none;">
                <?php echo $p; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<style>
.filter-chip {
    padding: 6px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600;
    border: 1.5px solid rgba(0,0,0,0.1); color: var(--text-secondary);
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
    transition: all 0.2s;
}
.filter-chip.active { background: var(--accent-color); color: #fff; border-color: transparent; }
.filter-chip:hover { border-color: var(--accent-color); color: var(--accent-dark); }
.chip-badge { background: #ef4444; color: #fff; border-radius: 10px; padding: 1px 6px; font-size: 0.7rem; }
</style>

<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
<?php include '../includes/footer.php'; ?>
