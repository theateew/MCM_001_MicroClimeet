<?php
$base = isset($base_url) ? $base_url : '../';
$active = isset($active_menu) ? $active_menu : 'dashboard';
?>

<div class="sidebar glass-panel">
    <!-- Brand Logo Area (Ref: Hover Transition Specification) -->
    <div class="sidebar-brand">
        <a href="<?php echo $base; ?>index.php">
            <img src="<?php echo $base; ?>assets/images/brand/logo.png" alt="MicroClimeet Logo" class="sidebar-logo-full">
            <img src="<?php echo $base; ?>assets/images/brand/mini_logo.png" alt="MicroClimeet Logo" class="sidebar-logo-mini">
        </a>
    </div>
    
    <!-- Menu Links -->
    <ul class="sidebar-menu">
        <?php if (isset($is_admin_area) && $is_admin_area): ?>
            <!-- Admin Navigation Area -->
            <li>
                <a href="<?php echo $base; ?>admin/index.php" class="sidebar-item <?php echo $active === 'admin_dashboard' ? 'active' : ''; ?>">
                    <i data-lucide="shield"></i>
                    <span>Overview Admin</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base; ?>admin/users.php" class="sidebar-item <?php echo $active === 'admin_users' ? 'active' : ''; ?>">
                    <i data-lucide="users"></i>
                    <span>Approval Petani</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base; ?>admin/logs.php" class="sidebar-item <?php echo $active === 'admin_logs' ? 'active' : ''; ?>">
                    <i data-lucide="file-text"></i>
                    <span>Log Aktivitas</span>
                </a>
            </li>
            <li style="margin-top: 16px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 16px;">
                <a href="<?php echo $base; ?>petani/index.php" class="sidebar-item">
                    <i data-lucide="layout-dashboard" style="color: var(--accent-color);"></i>
                    <span>Dashboard Petani</span>
                </a>
            </li>
        <?php else: ?>
            <!-- Farmer Navigation Area -->
            <li>
                <a href="<?php echo $base; ?>petani/index.php" class="sidebar-item <?php echo $active === 'dashboard' ? 'active' : ''; ?>">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base; ?>petani/settings.php" class="sidebar-item <?php echo $active === 'settings' ? 'active' : ''; ?>">
                    <i data-lucide="sliders"></i>
                    <span>Kalibrasi & Akun</span>
                </a>
            </li>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li style="margin-top: 16px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 16px;">
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
        <div style="display: flex; align-items: center; gap: 12px; width: 100%;">
            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?fit=crop&w=80&h=80" alt="Avatar" class="profile-avatar">
            <div class="profile-info">
                <span class="profile-name"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Petani'; ?></span>
                <span class="profile-role"><?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'Administrator' : 'Petani Premium'; ?></span>
            </div>
        </div>
        
        <a href="<?php echo $base; ?>logout.php" class="sidebar-item" style="color: var(--danger-color); margin-top: 8px;">
            <i data-lucide="log-out"></i>
            <span>Keluar</span>
        </a>
    </div>
</div>
