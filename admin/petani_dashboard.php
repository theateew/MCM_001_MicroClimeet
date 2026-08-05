<?php
/**
 * admin/petani_dashboard.php
 * Halaman Admin untuk memantau & mengontrol Dashboard Petani per-Perangkat.
 */
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$page_title    = 'Dashboard Petani (Admin Control)';
$custom_css    = ['dashboard.css'];
$base_url      = '../';
$active_menu   = 'admin_petani_dashboard';
$is_admin_area = true;

// 1. Fetch all greenhouses for selector dropdown
$greenhouses = [];
try {
    $gh_stmt = $pdo->query(
        "SELECT g.*, u.name AS farmer_name, u.email AS farmer_email, u.phone AS farmer_phone
         FROM greenhouses g
         LEFT JOIN users u ON g.user_id = u.id
         WHERE g.device_code IS NOT NULL AND g.device_code != '' AND g.status = 'active'
         ORDER BY g.name ASC"
    );
    $greenhouses = $gh_stmt->fetchAll();
} catch (PDOException $e) {}

// Selected greenhouse ID
$selected_gh_id = isset($_GET['gh_id']) ? (int)$_GET['gh_id'] : (int)($greenhouses[0]['id'] ?? 1);
$current_gh = null;
foreach ($greenhouses as $gh) {
    if ($gh['id'] == $selected_gh_id) {
        $current_gh = $gh;
        break;
    }
}
if (!$current_gh && !empty($greenhouses)) {
    $current_gh = $greenhouses[0];
    $selected_gh_id = (int)$current_gh['id'];
}

// 2. Fetch thresholds for this greenhouse
$thresholds = [
    'temperature' => ['min' => 20, 'max' => 30],
    'humidity'    => ['min' => 60, 'max' => 85],
    'air_quality' => ['min' => 0,  'max' => 50],
    'uv'          => ['min' => 0,  'max' => 5]
];

// Baseline Global
try {
    $globals = $pdo->query("SELECT * FROM global_thresholds")->fetchAll();
    foreach ($globals as $g) {
        $thresholds[$g['sensor_type']] = ['min' => (float)$g['min_val'], 'max' => (float)$g['max_val']];
    }
} catch (PDOException $e) {}

// Greenhouse Override
if ($selected_gh_id > 0) {
    try {
        $customs = $pdo->prepare("SELECT * FROM greenhouse_thresholds WHERE greenhouse_id = ?");
        $customs->execute([$selected_gh_id]);
        foreach ($customs->fetchAll() as $c) {
            $thresholds[$c['sensor_type']] = ['min' => (float)$c['min_val'], 'max' => (float)$c['max_val']];
        }
    } catch (PDOException $e) {}
}

// 3. Fetch recent alerts for selected greenhouse
$recentAlerts = [];
if ($selected_gh_id > 0) {
    try {
        $a_stmt = $pdo->prepare(
            "SELECT * FROM alerts WHERE greenhouse_id = ? ORDER BY triggered_at DESC LIMIT 5"
        );
        $a_stmt->execute([$selected_gh_id]);
        $recentAlerts = $a_stmt->fetchAll();
    } catch (PDOException $e) {}
}

include '../includes/header.php';
?>

<!-- Firebase SDKs and ApexCharts -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <!-- Admin Top Selector Bar -->
        <div class="glass-card" style="padding:18px 24px;margin-bottom:24px;background:linear-gradient(135deg, rgba(255,255,255,0.95), rgba(240,244,240,0.85));">
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-space-between;gap:16px;">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:12px;background:rgba(13,93,49,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="layout-dashboard" style="width:22px;height:22px;color:var(--accent-color);"></i>
                    </div>
                    <div>
                        <h2 style="font-family:var(--font-heading);font-size:1.15rem;font-weight:800;margin:0;">Dashboard Petani (Admin Mode)</h2>
                        <p style="font-size:0.8rem;color:var(--text-secondary);margin:2px 0 0 0;">Pantau dan kontrol tampilan live telemetry dari seluruh perangkat petani.</p>
                    </div>
                </div>

                <form method="GET" id="form-admin-petani-dashboard" style="display:flex;align-items:center;gap:10px;">
                    <input type="hidden" name="gh_id" id="selected_admin_gh_id_input" value="<?php echo $selected_gh_id; ?>">
                    <label style="font-size:0.83rem;font-weight:700;color:var(--text-secondary);">Pilih Dashboard Perangkat:</label>
                    
                    <div style="position:relative;display:inline-block;">
                        <button type="button" onclick="toggleAdminPetaniDashboardMenu(event)" style="padding:9px 16px;border-radius:12px;border:1.5px solid var(--accent-color);background:#fff;font-size:0.88rem;font-weight:700;color:var(--accent-dark);cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,0.06);display:inline-flex;align-items:center;gap:8px;">
                            <i class="bi bi-house-door-fill" style="font-size:1.05rem;color:#0D5D31;"></i>
                            <span><?php echo htmlspecialchars($current_gh['name'] ?? 'Pilih Greenhouse'); ?> — <?php echo htmlspecialchars($current_gh['farmer_name'] ?? 'Petani'); ?> (<?php echo htmlspecialchars($current_gh['device_code'] ?? 'MCM-'.$selected_gh_id); ?>)</span>
                            <i class="bi bi-chevron-down" style="font-size:0.75rem;margin-left:4px;color:var(--text-secondary);"></i>
                        </button>

                        <div id="admin-petani-dashboard-menu" style="display:none;position:absolute;top:calc(100% + 6px);right:0;width:380px;background:#fff;border:1.5px solid rgba(13,93,49,0.2);border-radius:14px;box-shadow:0 12px 30px rgba(0,0,0,0.12);z-index:9999;overflow:hidden;padding:6px 0;">
                            <div style="max-height:280px;overflow-y:auto;">
                                <?php foreach ($greenhouses as $gh): ?>
                                <div onclick="selectAdminDashboardTarget(<?php echo $gh['id']; ?>)" style="padding:10px 16px;font-size:0.85rem;font-weight:700;color:<?php echo $selected_gh_id == $gh['id'] ? '#0D5D31' : '#334155'; ?>;background:<?php echo $selected_gh_id == $gh['id'] ? 'rgba(13,93,49,0.08)' : 'transparent'; ?>;cursor:pointer;display:flex;align-items:center;gap:10px;transition:background 0.15s;" onmouseover="if(<?php echo $selected_gh_id == $gh['id'] ? 'false' : 'true'; ?>) this.style.background='rgba(0,0,0,0.03)'" onmouseout="if(<?php echo $selected_gh_id == $gh['id'] ? 'false' : 'true'; ?>) this.style.background='transparent'">
                                    <i class="bi bi-house-door" style="font-size:1.1rem;color:#0D5D31;flex-shrink:0;"></i>
                                    <div>
                                        <div><?php echo htmlspecialchars($gh['name']); ?></div>
                                        <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:normal;"><?php echo htmlspecialchars($gh['farmer_name'] ?: 'Petani'); ?> — Kode: <?php echo htmlspecialchars($gh['device_code'] ?: 'MCM-'.$gh['id']); ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($current_gh): ?>
            <div style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(0,0,0,0.06);display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;font-size:0.82rem;">
                <div style="display:flex;align-items:center;gap:16px;">
                    <span><strong>Pemilik:</strong> <?php echo htmlspecialchars($current_gh['farmer_name'] ?: '-'); ?> (<?php echo htmlspecialchars($current_gh['farmer_phone'] ?: '-'); ?>)</span>
                    <span><strong>Komoditas:</strong> <span style="color:#166534;font-weight:700;"><?php echo htmlspecialchars($current_gh['commodity'] ?: 'Melon Premium'); ?></span></span>
                    <span><strong>Kode IoT:</strong> <code><?php echo htmlspecialchars($current_gh['device_code'] ?: 'MCM-'.$current_gh['id']); ?></code></span>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="thresholds.php?greenhouse_id=<?php echo $selected_gh_id; ?>" class="btn btn-secondary" style="font-size:0.76rem;padding:4px 12px;border-radius:10px;">
                        <i data-lucide="sliders-horizontal" style="width:13px;height:13px;margin-right:4px;"></i> Ubah Threshold Perangkat Ini
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Top Status Bar ── -->
        <div class="dash-topbar" style="margin-bottom:20px;">
            <div class="dash-topbar-left">
                <h1 class="dash-title" style="font-size:1.4rem;">
                    Monitoring Live: <?php echo htmlspecialchars($current_gh['name'] ?? 'Greenhouse'); ?>
                </h1>
                <p class="dash-subtitle">Visualisasi telemetry real-time sebagaimana dilihat oleh petani.</p>
            </div>
            <div class="dash-topbar-right">
                <span id="status-pill" class="status-pill checking">
                    <span class="indicator-dot"></span>
                    <span id="status-text">Memeriksa Status Perangkat...</span>
                </span>
                <a href="../api/export_telemetry.php" class="dash-btn dash-btn-outline" style="font-size:0.8rem;">
                    <i data-lucide="download" style="width:14px;height:14px;"></i> Ekspor CSV
                </a>
                <a href="../api/export_pdf_report.php?gh_id=<?php echo (int)($selected_gh_id ?? 1); ?>" target="_blank" class="dash-btn dash-btn-primary" style="font-size:0.8rem;">
                    <i data-lucide="file-text" style="width:14px;height:14px;"></i> Cetak Laporan PDF
                </a>
            </div>
        </div>

        <!-- ── 4 Hero Stat Cards ── -->
        <div class="stat-cards-row">
            <!-- 1. Suhu Udara -->
            <div class="stat-card stat-card--primary" id="temp-card">
                <div class="stat-card-header">
                    <span class="stat-card-label">
                        <i data-lucide="thermometer" class="stat-card-label-icon"></i>
                        Suhu Udara
                    </span>
                </div>
                <div class="stat-card-value">
                    <span id="temp-val">--.-</span>
                    <span class="stat-card-unit">°C</span>
                </div>
                <div class="stat-card-footer">
                    <span id="temp-status" class="stat-badge stat-badge--normal">Mencari...</span>
                    <span class="stat-range" id="temp-stat-range">Target: <?php echo $thresholds['temperature']['min']; ?>–<?php echo $thresholds['temperature']['max']; ?>°C</span>
                </div>
            </div>

            <!-- 2. Kelembapan -->
            <div class="stat-card" id="hum-card">
                <div class="stat-card-header">
                    <span class="stat-card-label">
                        <i data-lucide="droplets" class="stat-card-label-icon"></i>
                        Kelembapan
                    </span>
                </div>
                <div class="stat-card-value">
                    <span id="hum-val">--.-</span>
                    <span class="stat-card-unit">% RH</span>
                </div>
                <div class="stat-card-footer">
                    <span id="hum-status" class="stat-badge stat-badge--normal">Mencari...</span>
                    <span class="stat-range" id="hum-stat-range">Target: <?php echo $thresholds['humidity']['min']; ?>–<?php echo $thresholds['humidity']['max']; ?>%</span>
                </div>
            </div>

            <!-- 3. Kualitas Udara -->
            <div class="stat-card" id="air-card">
                <div class="stat-card-header">
                    <span class="stat-card-label">
                        <i data-lucide="wind" class="stat-card-label-icon"></i>
                        Kualitas Udara
                    </span>
                </div>
                <div class="stat-card-value">
                    <span id="air-val">---</span>
                    <span class="stat-card-unit">PM2.5</span>
                </div>
                <div class="stat-card-footer">
                    <span id="air-status" class="stat-badge stat-badge--normal">Mencari...</span>
                    <span class="stat-range" id="air-stat-range">Target: <?php echo $thresholds['air_quality']['min']; ?>–<?php echo $thresholds['air_quality']['max']; ?> PM2.5</span>
                </div>
            </div>

            <!-- 4. Indeks UV -->
            <div class="stat-card" id="uv-card">
                <div class="stat-card-header">
                    <span class="stat-card-label">
                        <i data-lucide="sun" class="stat-card-label-icon"></i>
                        Indeks UV
                    </span>
                </div>
                <div class="stat-card-value">
                    <span id="uv-val">--.-</span>
                    <span class="stat-card-unit">UV Index</span>
                </div>
                <div class="stat-card-footer">
                    <span id="uv-status" class="stat-badge stat-badge--normal">Mencari...</span>
                    <span class="stat-range" id="uv-stat-range">Target: <?php echo $thresholds['uv']['min']; ?>–<?php echo $thresholds['uv']['max']; ?> UV</span>
                </div>
            </div>
        </div>

        <!-- ── Crop Cycle Timeline & Nutrients Guidance Realtime Card ── -->
        <div class="dash-card" style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(240,247,240,0.9));">
            <div class="dash-card-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:40px; height:40px; border-radius:12px; background:rgba(13,93,49,0.12); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i data-lucide="sprout" style="width:20px; height:20px; color:var(--accent-color);"></i>
                    </div>
                    <div>
                        <h3 class="dash-card-title" style="margin:0;">Timeline Siklus Tanam & Kebutuhan Nutrisi Realtime</h3>
                        <p class="dash-card-sub" style="margin:2px 0 0 0;">Monitoring fase tumbuh kembang komoditas & panduan nutrisi presisi</p>
                    </div>
                </div>
                <button onclick="openCropCycleModal()" class="dash-btn dash-btn-outline" style="font-size:0.78rem; padding:6px 14px;">
                    <i data-lucide="calendar" style="width:14px; height:14px; margin-right:4px;"></i> Atur Tanggal Tanam
                </button>
            </div>

            <!-- Main Cycle Grid -->
            <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:20px; margin-top:16px;">
                <!-- Left: Visual Timeline Progress Bar & Phase Info -->
                <div style="background:rgba(255,255,255,0.7); border:1px solid rgba(0,0,0,0.06); border-radius:16px; padding:18px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <span id="cc-phase-badge" style="background:#22c55e18; color:#15803d; border:1px solid #22c55e33; border-radius:20px; padding:4px 14px; font-size:0.82rem; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
                            <i data-lucide="leaf" style="width:14px; height:14px;"></i>
                            <span id="cc-phase-name">Fase Vegetatif</span>
                        </span>
                        <span style="font-size:0.83rem; font-weight:700; color:var(--text-secondary);">
                            Hari ke-<strong id="cc-days-active" style="color:var(--accent-dark); font-size:1.05rem;">--</strong> / <span id="cc-total-days">75</span> Hari
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div style="position:relative; height:12px; background:rgba(0,0,0,0.08); border-radius:10px; margin:14px 0 10px 0; overflow:hidden;">
                        <div id="cc-progress-bar" style="position:absolute; top:0; left:0; height:100%; width:0%; background:linear-gradient(90deg, #22c55e, #166534); border-radius:10px; transition:width 0.6s cubic-bezier(0.16,1,0.3,1);"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.76rem; color:var(--text-secondary); margin-bottom:14px;">
                        <span>Tanam: <strong id="cc-plant-date" style="color:var(--text-primary);">-----</strong></span>
                        <span>Progres: <strong id="cc-progress-pct" style="color:var(--accent-color);">0%</strong></span>
                        <span>Estimasi Panen: <strong id="cc-harvest-date" style="color:var(--text-primary);">-----</strong></span>
                    </div>

                    <!-- Langkah Solusi Per-Fase -->
                    <div style="background:rgba(13,93,49,0.05); border-left:3.5px solid var(--accent-color); border-radius:8px; padding:10px 14px;">
                        <strong style="display:block; font-size:0.8rem; color:var(--accent-dark); margin-bottom:2px;"><i data-lucide="lightbulb" style="width:13px; height:13px; display:inline; vertical-align:middle;"></i> Langkah Solusi Fase Saat Ini:</strong>
                        <p id="cc-solution" style="font-size:0.82rem; color:var(--text-primary); margin:0; line-height:1.4;">Memuat panduan fase…</p>
                    </div>
                </div>

                <!-- Right: Target Nutrisi Presisi (EC & pH) -->
                <div style="background:rgba(255,255,255,0.7); border:1px solid rgba(0,0,0,0.06); border-radius:16px; padding:18px; display:flex; flex-direction:column; justify-content:space-between;">
                    <h4 style="font-family:var(--font-heading); font-size:0.92rem; font-weight:700; margin:0 0 12px 0; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                        <i data-lucide="flask-conical" style="width:16px; height:16px; color:#3b82f6;"></i>
                        <span>Target Nutrisi & Kebersihan Air (Fase Aktif)</span>
                    </h4>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <!-- Target EC -->
                        <div style="background:#3b82f60d; border:1px solid #3b82f625; border-radius:12px; padding:12px; text-align:center;">
                            <span style="font-size:0.74rem; font-weight:700; color:#1d4ed8; text-transform:uppercase; display:block;">Target EC Nutrisi</span>
                            <strong id="cc-target-ec" style="font-family:var(--font-heading); font-size:1.2rem; color:#1e40af; display:block; margin-top:2px;">1.5 – 1.8</strong>
                            <span style="font-size:0.71rem; color:var(--text-secondary);">mS/cm (EC Meter)</span>
                        </div>

                        <!-- Target pH -->
                        <div style="background:#8b5cf60d; border:1px solid #8b5cf625; border-radius:12px; padding:12px; text-align:center;">
                            <span style="font-size:0.74rem; font-weight:700; color:#6d28d9; text-transform:uppercase; display:block;">Target pH Air</span>
                            <strong id="cc-target-ph" style="font-family:var(--font-heading); font-size:1.2rem; color:#5b21b6; display:block; margin-top:2px;">6.0 – 6.5</strong>
                            <span style="font-size:0.71rem; color:var(--text-secondary);">Optimal Penyerapan</span>
                        </div>
                    </div>

                    <div style="font-size:0.77rem; color:var(--text-secondary); display:flex; align-items:center; justify-content:space-between;">
                        <span>Fase Selanjutnya: <strong id="cc-next-phase" style="color:var(--accent-dark);">--</strong></span>
                        <span id="cc-days-left-tag" style="background:rgba(0,0,0,0.05); padding:2px 8px; border-radius:10px; font-weight:700;">-- hari lagi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Main Grid ── -->
        <div class="dash-main-grid">
            <!-- Left Column: Chart + Health Gauge + Photo & Location -->
            <div class="dash-col-left">
                <!-- Trend Chart -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h3 class="dash-card-title">
                                <i data-lucide="trending-up" style="color:#0D5D31;"></i>
                                <span>Tren Parameter Greenhouse</span>
                            </h3>
                            <p class="dash-card-sub">Data telemetri sensor real-time</p>
                        </div>
                    </div>
                    <div id="telemetry-chart"></div>
                </div>

                <!-- Sub-Grid Row: Health Progress + Greenhouse Photo -->
                <div class="dash-health-photo-row">
                    <!-- Progress Kesehatan -->
                    <div class="dash-card dash-health-card" style="height:100%;">
                        <div class="dash-card-header">
                            <div>
                                <h3 class="dash-card-title">
                                    <i data-lucide="activity" style="color:#22c55e;"></i>
                                    <span>Kesehatan Mikroklimat</span>
                                </h3>
                            </div>
                        </div>
                        <div class="health-gauge-wrapper">
                            <div id="health-gauge-chart"></div>
                            <div class="health-legend">
                                <div class="health-legend-item">
                                    <span class="health-dot health-dot--optimal"></span>
                                    <span>Optimal</span>
                                    <strong id="health-pct-optimal">--%</strong>
                                </div>
                                <div class="health-legend-item">
                                    <span class="health-dot health-dot--warning"></span>
                                    <span>Peringatan</span>
                                    <strong id="health-pct-warning">--%</strong>
                                </div>
                                <div class="health-legend-item">
                                    <span class="health-dot health-dot--danger"></span>
                                    <span>Kritis</span>
                                    <strong id="health-pct-danger">--%</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Foto Greenhouse -->
                    <div class="dash-card dash-photo-card" style="height:100%;">
                        <div class="dash-card-header">
                            <div>
                                <h3 class="dash-card-title">
                                    <i data-lucide="camera" style="color:#0D5D31;"></i>
                                    <span>Foto Greenhouse</span>
                                </h3>
                                <p class="dash-card-sub"><?php echo htmlspecialchars($current_gh['name'] ?? 'Kondisi Lahan'); ?></p>
                            </div>
                        </div>
                        <?php if ($current_gh && !empty($current_gh['photo']) && file_exists(__DIR__ . '/../' . $current_gh['photo'])): ?>
                        <div class="dash-photo-container">
                            <img src="../<?php echo htmlspecialchars($current_gh['photo']); ?>" alt="Foto Greenhouse" class="dash-photo-img">
                        </div>
                        <?php else: ?>
                        <div class="dash-photo-container">
                            <div class="dash-photo-placeholder">
                                <i data-lucide="camera" style="width:32px;height:32px;opacity:0.5;"></i>
                                <span style="font-size:0.8rem;">Foto Belum Diunggah Petani</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Google Maps Location -->
                <div class="dash-card dash-maps-card">
                    <div class="dash-card-header">
                        <div>
                            <h3 class="dash-card-title">
                                <i data-lucide="map-pin" style="color:#0D5D31;"></i>
                                <span>Lokasi Greenhouse (Google Maps)</span>
                            </h3>
                            <p class="dash-card-sub">
                                <?php 
                                    if ($current_gh && $current_gh['location']) {
                                        echo htmlspecialchars($current_gh['location']);
                                    } elseif ($current_gh && $current_gh['latitude'] && $current_gh['longitude']) {
                                        echo 'GPS: ' . $current_gh['latitude'] . ', ' . $current_gh['longitude'];
                                    } else {
                                        echo 'Lokasi belum diatur oleh petani';
                                    }
                                ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($current_gh && $current_gh['latitude'] && $current_gh['longitude']): ?>
                    <div class="dash-maps-container">
                        <iframe width="100%" height="180" frameborder="0" style="border:0;" loading="lazy"
                            src="https://maps.google.com/maps?q=<?php echo $current_gh['latitude']; ?>,<?php echo $current_gh['longitude']; ?>&z=16&output=embed">
                        </iframe>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Commodity Profile + Alerts -->
            <div class="dash-col-right">
                <!-- Profil Komoditas Card -->
                <div class="dash-card dash-card--green">
                    <div class="dash-card-header">
                        <h3 class="dash-card-title" style="color:#fff;">
                            <i data-lucide="sprout" style="color:#fff;"></i>
                            <span>Profil Komoditas</span>
                        </h3>
                    </div>
                    <?php $comm_name = ($current_gh && !empty($current_gh['commodity'])) ? $current_gh['commodity'] : 'Melon Premium'; ?>
                    <div class="commodity-name">Budidaya <?php echo htmlspecialchars($comm_name); ?></div>
                    <p class="commodity-type">Mikroklimat Optimal: <?php echo htmlspecialchars($comm_name); ?></p>
                    <div class="commodity-params">
                        <div class="param-row">
                            <div class="param-icon"><i data-lucide="thermometer" style="width:14px;height:14px;"></i></div>
                            <span class="param-label">Target Suhu</span>
                            <strong class="param-val" id="comm-temp-target"><?php echo $thresholds['temperature']['min']; ?>° – <?php echo $thresholds['temperature']['max']; ?>°C</strong>
                        </div>
                        <div class="param-row">
                            <div class="param-icon"><i data-lucide="droplet" style="width:14px;height:14px;"></i></div>
                            <span class="param-label">Target Kelembapan</span>
                            <strong class="param-val" id="comm-hum-target"><?php echo $thresholds['humidity']['min']; ?>% – <?php echo $thresholds['humidity']['max']; ?>%</strong>
                        </div>
                        <div class="param-row">
                            <div class="param-icon"><i data-lucide="sun-medium" style="width:14px;height:14px;"></i></div>
                            <span class="param-label">Radiasi Maks.</span>
                            <strong class="param-val" id="comm-uv-target"><?php echo $thresholds['uv']['max']; ?> UV Index</strong>
                        </div>
                        <div class="param-row">
                            <div class="param-icon"><i data-lucide="wind" style="width:14px;height:14px;"></i></div>
                            <span class="param-label">PM2.5 Maks.</span>
                            <strong class="param-val" id="comm-air-target"><?php echo $thresholds['air_quality']['max']; ?> µg/m³</strong>
                        </div>
                    </div>
                </div>

                <!-- Alert Log Card -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h3 class="dash-card-title">Peringatan Terkini</h3>
                            <p class="dash-card-sub">Log anomali sensor perangkat ini</p>
                        </div>
                    </div>
                    <div class="alert-timeline">
                        <?php if (empty($recentAlerts)): ?>
                        <div class="alert-empty">
                            <i data-lucide="check-circle" style="width:28px;height:28px;color:#4ADE80;"></i>
                            <p>Semua parameter dalam kondisi normal</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recentAlerts as $ra): ?>
                        <div class="alert-item <?php echo $ra['threshold_type']==='above_max'?'':'alert-item--warning'; ?>">
                            <div class="alert-dot <?php echo $ra['threshold_type']==='above_max'?'':'alert-dot--warning'; ?>"></div>
                            <div>
                                <div class="alert-msg"><?php echo htmlspecialchars($ra['message']); ?></div>
                                <div class="alert-time"><?php echo date('H:i, d M', strtotime($ra['triggered_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php 
$custom_js = ['firebase-config.js'];
include '../includes/footer.php'; 
?>

<script>
let currentThresholds = <?php echo json_encode($thresholds); ?>;
let selectedGreenhouseId = <?php echo (int)$selected_gh_id; ?>;

// Chart setup
const chartOptions = {
    series: [
        { name: 'Suhu (°C)', data: [] },
        { name: 'Kelembapan (%)', data: [] },
        { name: 'Kualitas Udara (PM)', data: [] },
        { name: 'Indeks UV', data: [] }
    ],
    chart: { height: 260, type: 'area', toolbar: { show: false } },
    colors: ['#E55353', '#4A90E2', '#48BB78', '#E6A23C'],
    stroke: { curve: 'smooth', width: 2 },
    xaxis: { type: 'datetime' },
    tooltip: { x: { format: 'HH:mm:ss' } }
};
const chart = new ApexCharts(document.querySelector("#telemetry-chart"), chartOptions);
chart.render();

// Gauge setup
const gaugeOptions = {
    series: [100],
    chart: { type: 'radialBar', height: 170 },
    plotOptions: {
        radialBar: {
            startAngle: -135, endAngle: 135, hollow: { size: '55%' },
            dataLabels: {
                name: { show: true, fontSize: '11px', color: '#6b7280', offsetY: 18 },
                value: { fontSize: '1.8rem', fontWeight: '800', color: '#0D5D31', offsetY: -10, formatter: v => v + '%' }
            }
        }
    },
    colors: ['#0D5D31']
};
const gaugeChart = new ApexCharts(document.querySelector("#health-gauge-chart"), gaugeOptions);
gaugeChart.render();

let chartHistory = [];
const currentDeviceCode = <?php echo json_encode($current_gh['device_code'] ?? ''); ?>;

function setStatus(mode) {
    const pill = document.getElementById('status-pill');
    const text = document.getElementById('status-text');
    if (!pill || !text) return;
    if (mode === 'live') {
        pill.className = 'status-pill live';
        text.innerText = 'Perangkat Live (ESP32 On)';
    } else if (mode === 'device_offline') {
        pill.className = 'status-pill device-offline';
        text.innerText = 'Perangkat Offline (Mati / Putus)';
    } else if (mode === 'offline') {
        pill.className = 'status-pill offline';
        text.innerText = 'Menghubungkan Firebase...';
    } else {
        pill.className = 'status-pill checking';
        text.innerText = 'Memeriksa Status Perangkat...';
    }
}

function evaluateStatus(type, val) {
    const t = currentThresholds[type];
    if (!t) return { text: 'OPTIMAL', level: 'optimal' };
    if (val < t.min) return { text: 'WASPADALAH (RENDAH)', level: 'warning' };
    if (val > t.max) return { text: 'BAHAYA (TINGGI)', level: 'danger' };
    return { text: 'OPTIMAL', level: 'optimal' };
}

function updateUI(d) {
    const tempVal = parseFloat(d.temperature ?? d.temp ?? 0);
    const humVal  = parseFloat(d.humidity ?? d.hum ?? 0);
    const airVal  = parseFloat(d.air_quality ?? d.pm25 ?? d.pms ?? 0);
    const uvVal   = parseFloat(d.uv ?? d.uv_index ?? 0);

    document.getElementById('temp-val').innerText = tempVal.toFixed(1);
    document.getElementById('hum-val').innerText  = humVal.toFixed(1);
    document.getElementById('air-val').innerText  = airVal.toFixed(0);
    document.getElementById('uv-val').innerText   = uvVal.toFixed(1);

    const statuses = {
        temperature: evaluateStatus('temperature', tempVal),
        humidity: evaluateStatus('humidity', humVal),
        air_quality: evaluateStatus('air_quality', airVal),
        uv: evaluateStatus('uv', uvVal)
    };

    // Update badges
    ['temp-status','hum-status','air-status','uv-status'].forEach(id => {
        const key = id.split('-')[0] === 'temp' ? 'temperature' : (id.split('-')[0] === 'hum' ? 'humidity' : (id.split('-')[0] === 'air' ? 'air_quality' : 'uv'));
        const st = statuses[key] || { text:'OPTIMAL', level:'optimal' };
        const el = document.getElementById(id);
        if (el) {
            el.innerText = st.text;
            el.className = 'stat-badge ' + (st.level==='danger'?'stat-badge--danger':(st.level==='warning'?'stat-badge--warning':'stat-badge--normal'));
        }
    });

    const vals = Object.values(statuses);
    const optimal = vals.filter(s => s.level === 'optimal').length;
    const pct = Math.round((optimal / (vals.length || 1)) * 100);
    gaugeChart.updateSeries([pct]);
    if (document.getElementById('health-pct-optimal')) document.getElementById('health-pct-optimal').innerText = pct + '%';

    const ts = Date.now();
    chartHistory.push({ time: ts, temp: tempVal, hum: humVal, air: airVal, uv: uvVal });
    if (chartHistory.length > 25) chartHistory.shift();

    chart.updateSeries([
        { name: 'Suhu (°C)',         data: chartHistory.map(p => [p.time, p.temp]) },
        { name: 'Kelembapan (%)',     data: chartHistory.map(p => [p.time, p.hum]) },
        { name: 'Kualitas Udara (PM)', data: chartHistory.map(p => [p.time, p.air]) },
        { name: 'Indeks UV',         data: chartHistory.map(p => [p.time, p.uv]) }
    ]);
}

// Attach Firebase Realtime Listener
let lastRxTime = 0;
window.addEventListener('load', function() {
    if (typeof firebase !== 'undefined' && firebase.database && currentDeviceCode) {
        let path = "devices/" + currentDeviceCode + "/telemetry";
        if (currentDeviceCode === 'ESP32_GH_001') {
            path = "telemetry"; // Legacy root node fallback for ESP32_GH_001
        }

        setStatus('checking');

        const initialTimeout = setTimeout(() => {
            if (lastRxTime === 0) {
                setStatus('device_offline');
            }
        }, 4000);

        try {
            firebase.database().ref(path).on('value', (snapshot) => {
                const data = snapshot.val();
                if (data) {
                    lastRxTime = Date.now();
                    clearTimeout(initialTimeout);
                    setStatus('live');
                    updateUI(data);
                } else {
                    clearTimeout(initialTimeout);
                    setStatus('device_offline');
                }
            }, (err) => {
                clearTimeout(initialTimeout);
                setStatus('device_offline');
            });
        } catch (e) {
            clearTimeout(initialTimeout);
            setStatus('device_offline');
        }
    } else {
        setStatus('device_offline');
    }
});

// Real-time threshold sync
function pollThresholds() {
    fetch('../api/get_thresholds.php?gh_id=' + selectedGreenhouseId)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.thresholds) {
                const t = res.thresholds;
                currentThresholds = t;

                if (document.getElementById('temp-stat-range')) document.getElementById('temp-stat-range').innerText = `Target: ${t.temperature.min}–${t.temperature.max}°C`;
                if (document.getElementById('hum-stat-range'))  document.getElementById('hum-stat-range').innerText  = `Target: ${t.humidity.min}–${t.humidity.max}%`;
                if (document.getElementById('air-stat-range'))  document.getElementById('air-stat-range').innerText  = `Target: ${t.air_quality.min}–${t.air_quality.max} PM2.5`;
                if (document.getElementById('uv-stat-range'))   document.getElementById('uv-stat-range').innerText   = `Target: ${t.uv.min}–${t.uv.max} UV`;

                if (document.getElementById('comm-temp-target')) document.getElementById('comm-temp-target').innerText = `${t.temperature.min}° – ${t.temperature.max}°C`;
                if (document.getElementById('comm-hum-target'))  document.getElementById('comm-hum-target').innerText  = `${t.humidity.min}% – ${t.humidity.max}%`;
                if (document.getElementById('comm-uv-target'))   document.getElementById('comm-uv-target').innerText   = `${t.uv.max} UV Index`;
                if (document.getElementById('comm-air-target'))  document.getElementById('comm-air-target').innerText  = `${t.air_quality.max} µg/m³`;
            }
        })
        .catch(err => console.warn('[Threshold Sync Warning]', err));
}

// Real-time crop cycle sync
function pollCropCycle() {
    fetch('../api/get_crop_cycle.php?gh_id=' + selectedGreenhouseId)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (document.getElementById('cc-crop-name-label')) document.getElementById('cc-crop-name-label').innerText = res.crop_name;
                if (document.getElementById('cc-phase-name'))   document.getElementById('cc-phase-name').innerText   = res.phase_name;
                if (document.getElementById('cc-days-active'))  document.getElementById('cc-days-active').innerText  = res.days_active;
                if (document.getElementById('cc-total-days'))   document.getElementById('cc-total-days').innerText   = res.total_days;
                if (document.getElementById('cc-progress-bar')) document.getElementById('cc-progress-bar').style.width = res.progress_pct + '%';
                if (document.getElementById('cc-plant-date'))   document.getElementById('cc-plant-date').innerText   = res.formatted_planting_date || res.planting_date;
                if (document.getElementById('cc-harvest-date')) document.getElementById('cc-harvest-date').innerText = res.formatted_harvest_date || res.estimated_harvest_date;
                if (document.getElementById('cc-progress-pct')) document.getElementById('cc-progress-pct').innerText = res.progress_pct + '%';
                if (document.getElementById('cc-solution'))     document.getElementById('cc-solution').innerText     = res.solution;
                if (document.getElementById('cc-target-ec'))    document.getElementById('cc-target-ec').innerText    = res.ec_target;
                if (document.getElementById('cc-target-ph'))    document.getElementById('cc-target-ph').innerText    = res.ph_target;
                if (document.getElementById('cc-next-phase'))   document.getElementById('cc-next-phase').innerText   = res.next_phase;
                if (document.getElementById('cc-days-left-tag'))document.getElementById('cc-days-left-tag').innerText= `${res.days_left} hari panen lagi`;

                // Only update modal inputs if modal is NOT open
                const modal = document.getElementById('modal-crop-cycle');
                if (modal && modal.style.display !== 'flex') {
                    setCropCycleModalData(res);
                }
            }
        })
        .catch(err => console.warn('[Crop Cycle Sync Error]', err));
}

function setCropCycleModalData(res) {
    const presets = ['Melon Premium', 'Tomat Ceri', 'Paprika', 'Stroberi'];
    const select = document.getElementById('cc-modal-crop-select');
    const custom = document.getElementById('cc-modal-crop-custom');
    const customWrap = document.getElementById('cc-modal-crop-custom-wrap');

    if (presets.includes(res.crop_name)) {
        if (select) select.value = res.crop_name;
        if (custom) custom.value = '';
        if (customWrap) customWrap.style.display = 'none';
    } else {
        if (select) select.value = 'other';
        if (custom) custom.value = res.crop_name;
        if (customWrap) customWrap.style.display = 'block';
    }

    if (document.getElementById('cc-modal-pdate')) document.getElementById('cc-modal-pdate').value = res.planting_date;
    if (document.getElementById('cc-modal-hdate')) document.getElementById('cc-modal-hdate').value = res.estimated_harvest_date;
}

function handleCropSelectChange(select) {
    const customWrap = document.getElementById('cc-modal-crop-custom-wrap');
    const customInput = document.getElementById('cc-modal-crop-custom');
    if (select.value === 'other') {
        if (customWrap) customWrap.style.display = 'block';
        if (customInput) customInput.focus();
    } else {
        if (customWrap) customWrap.style.display = 'none';
    }
}

function handlePlantingDateChange(input) {
    const pDate = input.value;
    if (!pDate) return;
    const hInput = document.getElementById('cc-modal-hdate');
    if (hInput && (!hInput.value || hInput.dataset.autoCalculated === 'true')) {
        const d = new Date(pDate);
        d.setDate(d.getDate() + 75);
        hInput.value = d.toISOString().split('T')[0];
        hInput.dataset.autoCalculated = 'true';
    }
}

setInterval(pollThresholds, 4000);
setInterval(pollCropCycle, 4000);
pollCropCycle();

function openCropCycleModal() {
    document.getElementById('modal-crop-cycle').style.display = 'flex';
    if (window.lucide) lucide.createIcons();
}
function closeCropCycleModal() {
    document.getElementById('modal-crop-cycle').style.display = 'none';
}
function saveCropCycle(e) {
    e.preventDefault();
    const selVal = document.getElementById('cc-modal-crop-select').value;
    const customVal = document.getElementById('cc-modal-crop-custom').value.trim();
    const finalCrop = (selVal === 'other') ? customVal : selVal;

    if (!finalCrop) {
        alert('Silakan isi nama komoditas budidaya.');
        return;
    }

    const payload = {
        greenhouse_id: selectedGreenhouseId,
        crop_name: finalCrop,
        planting_date: document.getElementById('cc-modal-pdate').value,
        estimated_harvest_date: document.getElementById('cc-modal-hdate').value
    };
    fetch('../api/save_crop_cycle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(r => r.json()).then(res => {
        closeCropCycleModal();
        pollCropCycle();
    }).catch(err => alert('Gagal menyimpan siklus tanam'));
}

document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>

<!-- Modal Edit Crop Cycle -->
<div id="modal-crop-cycle" class="modal-overlay" style="display:none;" onclick="closeCropCycleModal()">
    <div class="modal-box glass-card" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3>
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(13,93,49,0.12);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="calendar" style="width:20px;height:20px;color:#0D5D31;"></i>
                </div>
                <span>Pengaturan Siklus Tanam (Admin Mode)</span>
            </h3>
            <button type="button" onclick="closeCropCycleModal()" class="modal-close" title="Tutup">✕</button>
        </div>
        <form onsubmit="saveCropCycle(event)">
            <div style="margin-bottom:14px;">
                <label class="form-label">Komoditas Budidaya</label>
                <select id="cc-modal-crop-select" class="form-input" onchange="handleCropSelectChange(this)">
                    <option value="Melon Premium">Melon Premium</option>
                    <option value="Tomat Ceri">Tomat Ceri</option>
                    <option value="Paprika">Paprika</option>
                    <option value="Stroberi">Stroberi</option>
                    <option value="other">Lainnya (Isi Manual)</option>
                </select>
            </div>
            <div id="cc-modal-crop-custom-wrap" style="display:none;margin-bottom:14px;">
                <label class="form-label" style="font-size:0.8rem;">Nama Komoditas Custom</label>
                <input type="text" id="cc-modal-crop-custom" class="form-input" placeholder="mis. Buah Naga / Cabai Hydroponic">
            </div>

            <div style="margin-bottom:14px;">
                <label class="form-label">Tanggal Mulai Tanam</label>
                <input type="date" id="cc-modal-pdate" class="form-input" required onchange="handlePlantingDateChange(this)">
            </div>

            <div style="margin-bottom:18px;">
                <label class="form-label">Perkiraan Tanggal Panen</label>
                <input type="date" id="cc-modal-hdate" class="form-input">
                <small style="color:var(--text-secondary);font-size:0.75rem;display:block;margin-top:4px;">
                    Otomatis dihitung +75 hari dari tanggal tanam jika tidak diubah manual.
                </small>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeCropCycleModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i> Simpan Siklus Tanam
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAdminPetaniDashboardMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('admin-petani-dashboard-menu');
    if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}
function selectAdminDashboardTarget(id) {
    document.getElementById('selected_admin_gh_id_input').value = id;
    document.getElementById('form-admin-petani-dashboard').submit();
}
document.addEventListener('click', (e) => {
    const menu = document.getElementById('admin-petani-dashboard-menu');
    if (menu && !menu.contains(e.target)) menu.style.display = 'none';
});
</script>
