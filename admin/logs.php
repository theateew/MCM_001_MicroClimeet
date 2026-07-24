<?php
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$page_title = "Log Aktivitas Petani";
$custom_css = ['dashboard.css'];
$base_url = '../';

$error = '';
$logs = [];

try {
    // Fetch logs with user details
    $stmt = $pdo->query("
        SELECT logs.*, users.name, users.email, users.role 
        FROM activity_logs logs 
        JOIN users ON logs.user_id = users.id 
        ORDER BY logs.accessed_at DESC 
        LIMIT 100
    ");
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Gagal memuat log aktivitas: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <?php 
    $active_menu = 'admin_logs';
    $is_admin_area = true;
    include '../includes/sidebar.php'; 
    ?>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2>Audit Log Aktivitas</h2>
                <p>Pantau rekam jejak aktivitas masuk sistem dan pembaruan pengaturan dari seluruh pengguna.</p>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25); color: #c93535; padding: 14px; border-radius: 12px; margin-bottom: 24px; font-size: 0.9rem;">
                <i data-lucide="alert-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Table Card -->
        <div class="glass-card" style="width: 100%; overflow-x: auto;">
            <h3 style="margin-bottom: 20px;"><i data-lucide="file-text" style="vertical-align: middle; margin-right: 8px; color: var(--accent-color);"></i> Log Aktivitas Terkini (Maks. 100 log)</h3>
            
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(0,0,0,0.06); color: var(--text-secondary); font-weight: 700;">
                        <th style="padding: 12px;">Tanggal & Waktu</th>
                        <th style="padding: 12px;">Nama Pengguna</th>
                        <th style="padding: 12px;">Email</th>
                        <th style="padding: 12px; text-align: center;">Peran (Role)</th>
                        <th style="padding: 12px;">Aktivitas Kegiatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" style="padding: 24px; text-align: center; color: var(--text-secondary);">Belum ada log aktivitas yang tercatat.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr style="border-bottom: 1px solid rgba(0,0,0,0.03);">
                                <td style="padding: 14px; color: var(--text-secondary); white-space: nowrap;"><?php echo date('d M Y, H:i:s', strtotime($log['accessed_at'])); ?></td>
                                <td style="padding: 14px; font-weight: 600;"><?php echo htmlspecialchars($log['name']); ?></td>
                                <td style="padding: 14px; color: var(--text-secondary);"><?php echo htmlspecialchars($log['email']); ?></td>
                                <td style="padding: 14px; text-align: center;">
                                    <?php if ($log['role'] === 'admin'): ?>
                                        <span class="badge" style="background: rgba(235, 80, 17, 0.1); color: #b83d0c; border: 1px solid rgba(235, 80, 17, 0.15);">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Petani</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px; font-weight: 500;"><?php echo htmlspecialchars($log['activity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php 
$custom_js = [];
include '../includes/footer.php'; 
?>
