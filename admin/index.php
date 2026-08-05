<?php
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$page_title = "Overview Admin";
$custom_css = ['dashboard.css'];
$base_url = '../';

$message = '';
$error = '';

// Handle Threshold updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_thresholds'])) {
    try {
        $pdo->beginTransaction();
        
        $sensors = ['temperature', 'humidity', 'air_quality', 'uv'];
        foreach ($sensors as $sensor) {
            $min = (float)$_POST["min_{$sensor}"];
            $max = (float)$_POST["max_{$sensor}"];
            
            $stmt = $pdo->prepare("UPDATE global_thresholds SET min_val = ?, max_val = ? WHERE sensor_type = ?");
            $stmt->execute([$min, $max, $sensor]);
        }
        
        // Log activity
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
        $log_stmt->execute([$_SESSION['user_id'], "Memperbarui konfigurasi ambang batas (threshold) global sensor"]);
        
        $pdo->commit();
        $message = "Ambang batas global berhasil diperbarui dan diterapkan ke seluruh dashboard petani!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Terjadi kesalahan pembaruan: " . $e->getMessage();
    }
}

// Fetch global thresholds
$thresholds = [];
try {
    $stmt = $pdo->query("SELECT * FROM global_thresholds");
    while ($row = $stmt->fetch()) {
        $thresholds[$row['sensor_type']] = [
            'min' => $row['min_val'],
            'max' => $row['max_val']
        ];
    }
} catch (PDOException $e) {
    $error = "Gagal memuat threshold: " . $e->getMessage();
}

// Fetch stats
$stats = ['active_farmers' => 0, 'pending_farmers' => 0, 'total_devices' => 0, 'total_logs' => 0];
try {
    $stats['active_farmers']  = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'petani' AND status = 'active'")->fetchColumn();
    $stats['pending_farmers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'petani' AND status = 'inactive'")->fetchColumn();
    $stats['total_devices']   = $pdo->query("SELECT COUNT(*) FROM greenhouses WHERE device_code IS NOT NULL AND device_code != ''")->fetchColumn();
    $stats['total_logs']      = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
} catch (PDOException $e) {}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <?php 
    $active_menu = 'admin_dashboard';
    $is_admin_area = true;
    include '../includes/sidebar.php'; 
    ?>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2><i data-lucide="shield-check" style="color:#0D5D31;"></i> Panel Kontrol Admin</h2>
                <p>Kelola konfigurasi sistem global, setujui akun petani, dan pantau log aktivitas.</p>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="telemetry-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 32px;">
            <div class="glass-card" style="display:flex; align-items:center; gap: 16px;">
                <div class="widget-icon" style="background: rgba(114, 156, 81, 0.12); color: var(--accent-color); width: 48px; height: 48px; border-radius: 14px;">
                    <i data-lucide="user-check"></i>
                </div>
                <div>
                    <span style="display:block; font-size: 0.78rem; color: var(--text-secondary); text-transform:uppercase; font-weight:700;">Petani Aktif</span>
                    <strong style="font-size: 1.8rem; font-family: var(--font-heading);"><?php echo $stats['active_farmers']; ?></strong>
                </div>
            </div>
            
            <div class="glass-card" style="display:flex; align-items:center; gap: 16px;">
                <div class="widget-icon" style="background: rgba(235, 80, 17, 0.12); color: var(--accent-orange); width: 48px; height: 48px; border-radius: 14px;">
                    <i data-lucide="user-plus"></i>
                </div>
                <div>
                    <span style="display:block; font-size: 0.78rem; color: var(--text-secondary); text-transform:uppercase; font-weight:700;">Approval</span>
                    <strong style="font-size: 1.8rem; font-family: var(--font-heading);"><?php echo $stats['pending_farmers']; ?></strong>
                </div>
            </div>

            <div class="glass-card" style="display:flex; align-items:center; gap: 16px;">
                <div class="widget-icon" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6; width: 48px; height: 48px; border-radius: 14px;">
                    <i data-lucide="cpu"></i>
                </div>
                <div>
                    <span style="display:block; font-size: 0.78rem; color: var(--text-secondary); text-transform:uppercase; font-weight:700;">Total Perangkat</span>
                    <strong style="font-size: 1.8rem; font-family: var(--font-heading);"><?php echo $stats['total_devices']; ?></strong>
                </div>
            </div>
            
            <div class="glass-card" style="display:flex; align-items:center; gap: 16px;">
                <div class="widget-icon" style="background: rgba(0, 0, 0, 0.05); color: var(--text-primary); width: 48px; height: 48px; border-radius: 14px;">
                    <i data-lucide="activity"></i>
                </div>
                <div>
                    <span style="display:block; font-size: 0.78rem; color: var(--text-secondary); text-transform:uppercase; font-weight:700;">Total Log</span>
                    <strong style="font-size: 1.8rem; font-family: var(--font-heading);"><?php echo $stats['total_logs']; ?></strong>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <!-- Left Column: Form Thresholds -->
            <div class="left-column">
                <div class="glass-card">
                    <!-- One-row Icon & Title Card Header (Req 4) -->
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(13,93,49,0.12); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="sliders-horizontal" style="width:20px; height:20px; color:var(--accent-color);"></i>
                        </div>
                        <div>
                            <h3 style="font-family:var(--font-heading); font-size:1.08rem; font-weight:700; margin:0; color:var(--text-primary);">Ambang Batas Sensor Global</h3>
                            <span style="font-size:0.78rem; color:var(--text-secondary);">Konfigurasi batas nilai ideal parameter mikroklimat default</span>
                        </div>
                    </div>

                    <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 20px; line-height:1.5;">
                        Tentukan rentang nilai ideal (minimum & maksimum) untuk masing-masing parameter. Sistem akan menandai status "NORMAL", "WASPADA", atau "BAHAYA" pada dashboard petani sesuai dengan rentang ini.
                    </p>

                    <div style="margin-bottom:20px; padding:10px 14px; background:rgba(13,93,49,0.06); border-radius:12px; display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size:0.83rem; font-weight:600; color:var(--accent-dark);">Ingin atur threshold khusus per-perangkat?</span>
                        <a href="thresholds.php" class="btn btn-primary" style="font-size:0.78rem; padding:6px 14px; border-radius:10px;">
                            <i data-lucide="settings-2" style="width:14px; height:14px; margin-right:4px;"></i> Kelola Per-Perangkat
                        </a>
                    </div>
                    
                    <?php if (!empty($message)): ?>
                        <div class="glass-card" style="background: rgba(114, 156, 81, 0.12); border-color: rgba(114, 156, 81, 0.25); color: #55793a; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                            <i data-lucide="check-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25); color: #c93535; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                            <i data-lucide="alert-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="index.php" method="POST">
                        <input type="hidden" name="update_thresholds" value="1">
                        
                        <!-- Temperature -->
                        <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 20px; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 16px;">
                            <div>
                                <strong style="display:block; font-size: 0.95rem;">Suhu Udara (°C)</strong>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">Sensor BME680</span>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="margin-bottom:4px;">Min Ideal</label>
                                <input type="number" step="0.1" name="min_temperature" class="form-control" value="<?php echo $thresholds['temperature']['min'] ?? 20.0; ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="margin-bottom:4px;">Max Ideal</label>
                                <input type="number" step="0.1" name="max_temperature" class="form-control" value="<?php echo $thresholds['temperature']['max'] ?? 30.0; ?>" required>
                            </div>
                        </div>

                        <!-- Humidity -->
                        <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 20px; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 16px;">
                            <div>
                                <strong style="display:block; font-size: 0.95rem;">Kelembapan (% RH)</strong>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">Sensor BME680</span>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="margin-bottom:4px;">Min Ideal</label>
                                <input type="number" step="0.1" name="min_humidity" class="form-control" value="<?php echo $thresholds['humidity']['min'] ?? 60.0; ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="margin-bottom:4px;">Max Ideal</label>
                                <input type="number" step="0.1" name="max_humidity" class="form-control" value="<?php echo $thresholds['humidity']['max'] ?? 85.0; ?>" required>
                            </div>
                        </div>

                        <!-- Air Quality -->
                        <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 20px; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 16px;">
                            <div>
                                <strong style="display:block; font-size: 0.95rem;">Kualitas Udara (PM2.5)</strong>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">Sensor PMS5003</span>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="margin-bottom:4px;">Min Ideal</label>
                                <input type="number" step="0.1" name="min_air_quality" class="form-control" value="<?php echo $thresholds['air_quality']['min'] ?? 0.0; ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="margin-bottom:4px;">Max Ideal</label>
                                <input type="number" step="0.1" name="max_air_quality" class="form-control" value="<?php echo $thresholds['air_quality']['max'] ?? 50.0; ?>" required>
                            </div>
                        </div>

                        <!-- UV Index -->
                        <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 20px; align-items: center; margin-bottom: 20px; padding-bottom: 16px;">
                            <div>
                                <strong style="display:block; font-size: 0.95rem;">Indeks UV (Index)</strong>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">Sensor GUVA-S12SD</span>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="margin-bottom:4px;">Min Ideal</label>
                                <input type="number" step="0.1" name="min_uv" class="form-control" value="<?php echo $thresholds['uv']['min'] ?? 0.0; ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="margin-bottom:4px;">Max Ideal</label>
                                <input type="number" step="0.1" name="max_uv" class="form-control" value="<?php echo $thresholds['uv']['max'] ?? 5.0; ?>" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 12px; padding: 14px 20px;">
                            <i data-lucide="save"></i> Terapkan Ambang Batas Global Baru
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Right Column: Quick Links -->
            <div class="right-column">
                <div class="glass-card">
                    <!-- One-row Icon & Title Card Header (Req 4) -->
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(235,80,17,0.12); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-lucide="help-circle" style="width:20px; height:20px; color:var(--accent-orange);"></i>
                        </div>
                        <div>
                            <h3 style="font-family:var(--font-heading); font-size:1.08rem; font-weight:700; margin:0; color:var(--text-primary);">Bantuan Panel</h3>
                            <span style="font-size:0.78rem; color:var(--text-secondary);">Panduan navigasi administrator</span>
                        </div>
                    </div>

                    <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6;">
                        Gunakan menu sebelah kiri untuk navigasi cepat:
                        <br><br>
                        1. <strong>Overview Admin</strong>: Mengelola ambang batas parameter sensor global.
                        <br>
                        2. <strong>Manajemen Petani</strong>: Memberikan persetujuan (*approval*) dan mengelola akun petani.
                        <br>
                        3. <strong>Kelola Perangkat</strong>: Menautkan kode perangkat ESP32 dan key Firebase.
                        <br>
                        4. <strong>Konfigurasi Threshold</strong>: Mengatur ambang batas spesifik per-perangkat petani.
                        <br>
                        5. <strong>Dashboard Petani</strong>: Memantau dan mengontrol tampilan live dashboard petani secara langsung.
                    </p>
                </div>
            </div>
        </div>
    </main>
</div>

<?php 
$custom_js = [];
include '../includes/footer.php'; 
?>
