<?php
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$page_title = "Persetujuan Petani";
$custom_css = ['dashboard.css'];
$base_url = '../';

$message = '';
$error = '';

// Handle Activation/Deactivation
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $userId = (int)$_GET['id'];
    
    if ($action === 'approve') {
        $status = 'active';
        $log_msg = "Menyetujui pendaftaran petani dengan ID $userId";
    } else {
        $status = 'inactive';
        $log_msg = "Menonaktifkan akses petani dengan ID $userId";
    }
    
    try {
        // Update user status
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'petani'");
        $stmt->execute([$status, $userId]);
        
        if ($stmt->rowCount() > 0) {
            // Log activity
            $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
            $log_stmt->execute([$_SESSION['user_id'], $log_msg]);
            
            $message = "Status petani berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui status. Akun tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan database: " . $e->getMessage();
    }
}

// Fetch all registered farmers
$farmers = [];
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'petani' ORDER BY created_at DESC");
    $farmers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Gagal mengambil data petani: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <?php 
    $active_menu = 'admin_users';
    $is_admin_area = true;
    include '../includes/sidebar.php'; 
    ?>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2>Persetujuan Akun Petani</h2>
                <p>Kelola akses pendaftaran petani baru untuk masuk ke dashboard.</p>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if (!empty($message)): ?>
            <div class="glass-card" style="background: rgba(114, 156, 81, 0.12); border-color: rgba(114, 156, 81, 0.25); color: #55793a; padding: 14px; border-radius: 12px; margin-bottom: 24px; font-size: 0.9rem;">
                <i data-lucide="check-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25); color: #c93535; padding: 14px; border-radius: 12px; margin-bottom: 24px; font-size: 0.9rem;">
                <i data-lucide="alert-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Table Card -->
        <div class="glass-card" style="width: 100%; overflow-x: auto;">
            <h3 style="margin-bottom: 20px;"><i data-lucide="users" style="vertical-align: middle; margin-right: 8px; color: var(--accent-color);"></i> Daftar Petani Terdaftar</h3>
            
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(0,0,0,0.06); color: var(--text-secondary); font-weight: 700;">
                        <th style="padding: 12px;">Nama Lengkap</th>
                        <th style="padding: 12px;">Email</th>
                        <th style="padding: 12px;">No. Telepon / WA</th>
                        <th style="padding: 12px;">Tanggal Daftar</th>
                        <th style="padding: 12px; text-align: center;">Status</th>
                        <th style="padding: 12px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($farmers)): ?>
                        <tr>
                            <td colspan="6" style="padding: 24px; text-align: center; color: var(--text-secondary);">Belum ada petani yang terdaftar di database.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($farmers as $farmer): ?>
                            <tr style="border-bottom: 1px solid rgba(0,0,0,0.03);">
                                <td style="padding: 14px; font-weight: 600;"><?php echo htmlspecialchars($farmer['name']); ?></td>
                                <td style="padding: 14px; color: var(--text-secondary);"><?php echo htmlspecialchars($farmer['email']); ?></td>
                                <td style="padding: 14px; color: var(--text-secondary);"><?php echo htmlspecialchars($farmer['phone']); ?></td>
                                <td style="padding: 14px; color: var(--text-secondary);"><?php echo date('d M Y, H:i', strtotime($farmer['created_at'])); ?></td>
                                <td style="padding: 14px; text-align: center;">
                                    <?php if ($farmer['status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Menunggu</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px; text-align: center; display: flex; gap: 8px; justify-content: center;">
                                    <?php if ($farmer['status'] === 'active'): ?>
                                        <a href="users.php?action=suspend&id=<?php echo $farmer['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; border-color: rgba(235, 80, 17, 0.3); color: var(--accent-orange);">
                                            <i data-lucide="user-x" style="width: 14px; height: 14px;"></i> Nonaktifkan
                                        </a>
                                    <?php else: ?>
                                        <a href="users.php?action=approve&id=<?php echo $farmer['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;">
                                            <i data-lucide="user-check" style="width: 14px; height: 14px;"></i> Setujui Akun
                                        </a>
                                    <?php endif; ?>
                                </td>
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
