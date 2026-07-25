<?php
/**
 * MicroClimeet — Sidebar Navigation
 * Mendukung menu petani + admin, dengan badge unread alert
 */
$base   = isset($base_url) ? $base_url : '../';
$active = isset($active_menu) ? $active_menu : 'dashboard';

// Hitung unread alert (hanya untuk petani yang sudah login)
$unread_alerts = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'petani') {
    try {
        require_once __DIR__ . '/notifications.php';
        $unread_alerts = getUnreadAlertCount($pdo, (int)$_SESSION['user_id']);
    } catch (Throwable $e) {
        $unread_alerts = 0;
    }
}
?>

<div class="sidebar glass-panel">
    <!-- Brand Logo Area -->
    <div class="sidebar-brand">
        <a href="<?php echo $base; ?>index.php">
            <img src="<?php echo $base; ?>assets/images/brand/logo.png" alt="MicroClimeet Logo" class="sidebar-logo-full">
            <img src="<?php echo $base; ?>assets/images/brand/mini_logo.png" alt="MicroClimeet Logo" class="sidebar-logo-mini">
        </a>
    </div>

    <!-- Menu Links -->
    <ul class="sidebar-menu">

        <?php if (isset($is_admin_area) && $is_admin_area): ?>
        <!-- ── ADMIN NAVIGATION ─────────────────────── -->

            <li>
                <a href="<?php echo $base; ?>admin/index.php"
                   class="sidebar-item <?php echo $active === 'admin_dashboard' ? 'active' : ''; ?>">
                    <i data-lucide="shield"></i>
                    <span>Overview Admin</span>
                </a>
            </li>

            <li>
                <a href="<?php echo $base; ?>admin/users.php"
                   class="sidebar-item <?php echo $active === 'admin_users' ? 'active' : ''; ?>">
                    <i data-lucide="users"></i>
                    <span>Manajemen Petani</span>
                </a>
            </li>

            <li>
                <a href="<?php echo $base; ?>admin/devices.php"
                   class="sidebar-item <?php echo $active === 'admin_devices' ? 'active' : ''; ?>">
                    <i data-lucide="cpu"></i>
                    <span>Kelola Perangkat</span>
                </a>
            </li>

            <li>
                <a href="<?php echo $base; ?>admin/thresholds.php"
                   class="sidebar-item <?php echo $active === 'admin_thresholds' ? 'active' : ''; ?>">
                    <i data-lucide="sliders"></i>
                    <span>Konfigurasi Threshold</span>
                </a>
            </li>

            <li>
                <a href="<?php echo $base; ?>admin/logs.php"
                   class="sidebar-item <?php echo $active === 'admin_logs' ? 'active' : ''; ?>">
                    <i data-lucide="file-text"></i>
                    <span>Log Aktivitas</span>
                </a>
            </li>

            <li style="margin-top: 16px; border-top: 1px solid rgba(0,0,0,0.06); padding-top: 16px;">
                <a href="<?php echo $base; ?>petani/index.php" class="sidebar-item">
                    <i data-lucide="layout-dashboard" style="color: var(--accent-color);"></i>
                    <span>Dashboard Petani</span>
                </a>
            </li>

        <?php else: ?>
        <!-- ── PETANI NAVIGATION ────────────────────── -->

            <li>
                <a href="<?php echo $base; ?>petani/index.php"
                   class="sidebar-item <?php echo $active === 'dashboard' ? 'active' : ''; ?>">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="<?php echo $base; ?>petani/greenhouse.php"
                   class="sidebar-item <?php echo $active === 'greenhouse' ? 'active' : ''; ?>">
                    <i data-lucide="home"></i>
                    <span>Greenhouse Saya</span>
                </a>
            </li>

            <li>
                <a href="<?php echo $base; ?>petani/history.php"
                   class="sidebar-item <?php echo $active === 'history' ? 'active' : ''; ?>">
                    <i data-lucide="bar-chart-2"></i>
                    <span>Riwayat Data</span>
                </a>
            </li>

            <li>
                <a href="<?php echo $base; ?>petani/alerts.php"
                   class="sidebar-item <?php echo $active === 'alerts' ? 'active' : ''; ?>"
                   style="position:relative;">
                    <i data-lucide="bell"></i>
                    <span>Peringatan</span>
                    <?php if ($unread_alerts > 0): ?>
                    <span class="sidebar-badge"><?php echo $unread_alerts > 99 ? '99+' : $unread_alerts; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="<?php echo $base; ?>petani/settings.php"
                   class="sidebar-item <?php echo $active === 'settings' ? 'active' : ''; ?>">
                    <i data-lucide="settings"></i>
                    <span>Pengaturan Akun</span>
                </a>
            </li>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li style="margin-top: 16px; border-top: 1px solid rgba(0,0,0,0.06); padding-top: 16px;">
                <a href="<?php echo $base; ?>admin/index.php" class="sidebar-item">
                    <i data-lucide="shield-check" style="color: var(--accent-orange);"></i>
                    <span>Menu Admin Area</span>
                </a>
            </li>
            <?php endif; ?>

        <?php endif; ?>
    </ul>

    <!-- Profile & Footer -->
    <div class="sidebar-footer">
        <div style="display:flex; align-items:center; gap:12px; width:100%;">
            <div class="profile-avatar-wrapper">
                <?php
                $avatar = isset($_SESSION['photo']) && !empty($_SESSION['photo'])
                    ? $base . 'assets/uploads/avatars/' . htmlspecialchars($_SESSION['photo'])
                    : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['name'] ?? 'U') . '&background=166534&color=fff&size=80';
                ?>
                <img src="<?php echo $avatar; ?>" alt="Avatar" class="profile-avatar">
            </div>
            <div class="profile-info">
                <span class="profile-name"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Pengguna'; ?></span>
                <span class="profile-role"><?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'Administrator' : 'Petani Premium'; ?></span>
            </div>
        </div>

        <a href="<?php echo $base; ?>logout.php" class="sidebar-item" style="color: var(--danger-color); margin-top: 8px;">
            <i data-lucide="log-out"></i>
            <span>Keluar</span>
        </a>
    </div>
</div>
