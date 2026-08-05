<?php
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$page_title    = "Audit Log Aktivitas";
$custom_css    = ['dashboard.css'];
$base_url      = '../';
$active_menu   = 'admin_logs';
$is_admin_area = true;

$error = '';
$logs  = [];

// ── GET Parameters for Filter, Search & Pagination ──
$search      = trim($_GET['q'] ?? '');
$roleFilter  = in_array($_GET['role'] ?? '', ['admin', 'petani']) ? $_GET['role'] : '';
$sortOrder   = ($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$limitInput = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
$perPage    = in_array($limitInput, [10, 25, 50, 100], true) ? $limitInput : 25;
$page        = max(1, (int)($_GET['page'] ?? 1));
$dateFrom    = trim($_GET['date_from'] ?? '');
$dateTo      = trim($_GET['date_to'] ?? '');

// ── Build Query Conditions ──
$conditions = ["1=1"];
$params     = [];

if ($search !== '') {
    $conditions[] = "(users.name LIKE ? OR users.email LIKE ? OR logs.activity LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($roleFilter !== '') {
    $conditions[] = "users.role = ?";
    $params[] = $roleFilter;
}

if ($dateFrom !== '') {
    $conditions[] = "DATE(logs.accessed_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $conditions[] = "DATE(logs.accessed_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = implode(' AND ', $conditions);

// ── Total Logs Count Query ──
$totalLogs = 0;
try {
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM activity_logs logs 
        JOIN users ON logs.user_id = users.id 
        WHERE $whereClause
    ");
    $countStmt->execute($params);
    $totalLogs = (int)$countStmt->fetchColumn();
} catch (PDOException $e) {
    $error = "Gagal menghitung log aktivitas: " . $e->getMessage();
}

// ── Stat Summary Counts ──
$stats = ['total' => $totalLogs, 'today' => 0, 'admin' => 0, 'petani' => 0];
try {
    $stats['today']  = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(accessed_at) = CURDATE()")->fetchColumn();
    $stats['admin']  = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs l JOIN users u ON l.user_id=u.id WHERE u.role='admin'")->fetchColumn();
    $stats['petani'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs l JOIN users u ON l.user_id=u.id WHERE u.role='petani'")->fetchColumn();
} catch (PDOException $e) {}

// ── Pagination Calculation ──
$totalPages = max(1, (int)ceil($totalLogs / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// ── Fetch Log Records ──
try {
    $sql = "
        SELECT logs.*, users.name, users.email, users.role 
        FROM activity_logs logs 
        JOIN users ON logs.user_id = users.id 
        WHERE $whereClause 
        ORDER BY logs.accessed_at " . strtoupper($sortOrder) . " 
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Gagal memuat log aktivitas: " . $e->getMessage();
}

// Helper to build pagination URL
function buildPaginationUrl($pageNum) {
    $queryParams = $_GET;
    $queryParams['page'] = $pageNum;
    return 'logs.php?' . http_build_query($queryParams);
}

include '../includes/header.php';
?>

<style>
.page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 8px;
    font-size: 0.83rem;
    font-weight: 700;
    text-decoration: none;
    color: var(--text-primary, #1e293b);
    background: rgba(255,255,255,0.85);
    border: 1px solid rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}
.page-link:hover:not(.disabled):not(.active) {
    background: #0D5D31;
    color: #fff;
    border-color: #0D5D31;
}
.page-link.active {
    background: #0D5D31;
    color: #fff;
    border-color: #0D5D31;
    box-shadow: 0 4px 10px rgba(13,93,49,0.25);
}
.page-link.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: rgba(0,0,0,0.03);
}
.page-dots {
    padding: 0 4px;
    color: var(--text-secondary);
    font-weight: bold;
}
</style>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <div class="dashboard-header" style="margin-bottom:20px;">
            <div class="welcome-msg">
                <h2><i data-lucide="file-text" style="color:#0D5D31;"></i> Audit Log Aktivitas</h2>
                <p>Pantau rekam jejak aktivitas masuk sistem, pembaruan data, dan perubahan konfigurasi dari seluruh pengguna.</p>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25); color: #c93535; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                <i data-lucide="alert-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Summary Stat Cards -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:14px;margin-bottom:20px;">
            <div class="glass-card" style="padding:14px 18px;display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(13,93,49,0.1);color:#0D5D31;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="database" style="width:20px;height:20px;"></i>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;line-height:1;"><?php echo number_format($totalLogs); ?></div>
                    <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px;">Total Ditemukan</div>
                </div>
            </div>

            <div class="glass-card" style="padding:14px 18px;display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(59,130,246,0.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="calendar" style="width:20px;height:20px;"></i>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;line-height:1;"><?php echo number_format($stats['today']); ?></div>
                    <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px;">Aktivitas Hari Ini</div>
                </div>
            </div>

            <div class="glass-card" style="padding:14px 18px;display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(235,80,17,0.1);color:#eb5011;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="shield" style="width:20px;height:20px;"></i>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;line-height:1;"><?php echo number_format($stats['admin']); ?></div>
                    <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px;">Aktivitas Admin</div>
                </div>
            </div>

            <div class="glass-card" style="padding:14px 18px;display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(34,197,94,0.1);color:#16a34a;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="sprout" style="width:20px;height:20px;"></i>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;line-height:1;"><?php echo number_format($stats['petani']); ?></div>
                    <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px;">Aktivitas Petani</div>
                </div>
            </div>
        </div>

        <!-- Filter & Sort Bar Card -->
        <div class="glass-card" style="padding:18px 22px;margin-bottom:20px;">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                <!-- Search Input -->
                <div style="flex:2;min-width:200px;position:relative;">
                    <i data-lucide="search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--text-secondary);"></i>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama, email, kegiatan..."
                           style="width:100%;padding:9px 12px 9px 36px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;box-sizing:border-box;">
                </div>

                <!-- Role Filter -->
                <div style="flex:1;min-width:130px;">
                    <select name="role" style="width:100%;padding:9px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;box-sizing:border-box;">
                        <option value="">Semua Peran (Role)</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="petani" <?php echo $roleFilter === 'petani' ? 'selected' : ''; ?>>Petani</option>
                    </select>
                </div>

                <!-- Date Range From -->
                <div style="min-width:130px;">
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" title="Dari Tanggal"
                           style="width:100%;padding:9px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;box-sizing:border-box;">
                </div>

                <!-- Date Range To -->
                <div style="min-width:130px;">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" title="Sampai Tanggal"
                           style="width:100%;padding:9px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;box-sizing:border-box;">
                </div>

                <!-- Sort Order -->
                <div style="min-width:120px;">
                    <select name="sort" style="width:100%;padding:9px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;box-sizing:border-box;">
                        <option value="desc" <?php echo $sortOrder === 'desc' ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="asc" <?php echo $sortOrder === 'asc' ? 'selected' : ''; ?>>Terlama</option>
                    </select>
                </div>

                <!-- Per Page Limit -->
                <div style="min-width:110px;">
                    <select name="limit" style="width:100%;padding:9px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;box-sizing:border-box;">
                        <option value="10" <?php echo $perPage === 10 ? 'selected' : ''; ?>>10 / hal</option>
                        <option value="25" <?php echo $perPage === 25 ? 'selected' : ''; ?>>25 / hal</option>
                        <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50 / hal</option>
                        <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100 / hal</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" style="padding:9px 18px;font-weight:700;border-radius:10px;display:inline-flex;align-items:center;gap:6px;background:#0D5D31;border:none;color:#fff;cursor:pointer;">
                    <i data-lucide="filter" style="width:15px;height:15px;"></i> Filter
                </button>

                <!-- Reset Button -->
                <?php if ($search !== '' || $roleFilter !== '' || $dateFrom !== '' || $dateTo !== '' || $sortOrder !== 'desc' || $perPage !== 25): ?>
                <a href="logs.php" style="padding:9px 14px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.15);font-size:0.85rem;color:var(--text-secondary);text-decoration:none;display:inline-flex;align-items:center;gap:4px;background:#fff;">
                    <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i> Reset
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table Card -->
        <div class="glass-card" style="width: 100%; overflow-x: auto; padding: 20px 24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
                <h3 class="dash-card-title" style="margin:0;"><i data-lucide="list-tree" style="color:#0D5D31;"></i> <span>Log Aktivitas Sistem</span></h3>
                <span style="font-size:0.8rem;color:var(--text-secondary);font-weight:600;">Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?></span>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(0,0,0,0.06); color: var(--text-secondary); font-weight: 700;">
                        <th style="padding: 12px 14px;">Tanggal & Waktu</th>
                        <th style="padding: 12px 14px;">Nama Pengguna</th>
                        <th style="padding: 12px 14px;">Email</th>
                        <th style="padding: 12px 14px; text-align: center;">Peran (Role)</th>
                        <th style="padding: 12px 14px;">Aktivitas Kegiatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" style="padding: 32px; text-align: center; color: var(--text-secondary);">
                                <i data-lucide="inbox" style="width:36px;height:36px;color:#94a3b8;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"></i>
                                Tidak ditemukan log aktivitas yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);">
                                <td style="padding: 12px 14px; color: var(--text-secondary); white-space: nowrap; font-family:monospace; font-size:0.83rem;">
                                    <?php echo date('d M Y, H:i:s', strtotime($log['accessed_at'])); ?>
                                </td>
                                <td style="padding: 12px 14px; font-weight: 700; color:var(--text-primary);">
                                    <?php echo htmlspecialchars($log['name']); ?>
                                </td>
                                <td style="padding: 12px 14px; color: var(--text-secondary); font-size:0.84rem;">
                                    <?php echo htmlspecialchars($log['email']); ?>
                                </td>
                                <td style="padding: 12px 14px; text-align: center;">
                                    <?php if ($log['role'] === 'admin'): ?>
                                        <span class="badge" style="background: rgba(235, 80, 17, 0.1); color: #b83d0c; border: 1px solid rgba(235, 80, 17, 0.2); font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                                            <i data-lucide="shield-check" style="width:13px;height:13px;"></i> Admin
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background: rgba(13, 93, 49, 0.1); color: #0D5D31; border: 1px solid rgba(13, 93, 49, 0.2); font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                                            <i data-lucide="sprout" style="width:13px;height:13px;"></i> Petani
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 14px; font-weight: 500; color: #334155;">
                                    <?php echo htmlspecialchars($log['activity']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ── Pagination Bar UI/UX Pro Max ── -->
            <?php if ($totalLogs > 0): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-top:20px;padding-top:16px;border-top:1px solid rgba(0,0,0,0.06);">
                <div style="font-size:0.83rem;color:var(--text-secondary);">
                    Menampilkan <strong><?php echo min($totalLogs, $offset + 1); ?>–<?php echo min($totalLogs, $offset + count($logs)); ?></strong> dari <strong><?php echo number_format($totalLogs); ?></strong> log aktivitas
                </div>

                <?php if ($totalPages > 1): ?>
                <div style="display:flex;align-items:center;gap:5px;">
                    <!-- First Page -->
                    <?php if ($page > 1): ?>
                        <a href="<?php echo buildPaginationUrl(1); ?>" class="page-link" title="Halaman Pertama">«</a>
                        <a href="<?php echo buildPaginationUrl($page - 1); ?>" class="page-link" title="Halaman Sebelumnya">‹ Prev</a>
                    <?php else: ?>
                        <span class="page-link disabled">«</span>
                        <span class="page-link disabled">‹ Prev</span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php
                    $range = 2;
                    $start = max(1, $page - $range);
                    $end   = min($totalPages, $page + $range);

                    if ($start > 1) echo '<span class="page-dots">...</span>';

                    for ($p = $start; $p <= $end; $p++):
                    ?>
                        <?php if ($p == $page): ?>
                            <span class="page-link active"><?php echo $p; ?></span>
                        <?php else: ?>
                            <a href="<?php echo buildPaginationUrl($p); ?>" class="page-link"><?php echo $p; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end < $totalPages) echo '<span class="page-dots">...</span>'; ?>

                    <!-- Next / Last Page -->
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo buildPaginationUrl($page + 1); ?>" class="page-link" title="Halaman Selanjutnya">Next ›</a>
                        <a href="<?php echo buildPaginationUrl($totalPages); ?>" class="page-link" title="Halaman Terakhir">»</a>
                    <?php else: ?>
                        <span class="page-link disabled">Next ›</span>
                        <span class="page-link disabled">»</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php 
$custom_js = [];
include '../includes/footer.php'; 
?>
