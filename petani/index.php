<?php
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('petani');

$page_title = "Dashboard Petani";
$custom_css = ['dashboard.css'];
$base_url = '../';

// Retrieve global thresholds from database to initialize warning logic
$thresholds = [
    'temperature' => ['min' => 20, 'max' => 30],
    'humidity' => ['min' => 60, 'max' => 85],
    'air_quality' => ['min' => 0, 'max' => 50],
    'uv' => ['min' => 0, 'max' => 5]
];

try {
    $stmt = $pdo->query("SELECT * FROM global_thresholds");
    while ($row = $stmt->fetch()) {
        $thresholds[$row['sensor_type']] = [
            'min' => (float)$row['min_val'],
            'max' => (float)$row['max_val']
        ];
    }
} catch (PDOException $e) {
    // Fallback to defaults if query fails
}

// Handle POST: Claim / Connect Device Code
$claim_msg = '';
$claim_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_device'])) {
    $code_input = trim($_POST['device_code'] ?? '');
    if (!empty($code_input)) {
        try {
            // Check if device_code exists in official Admin devices inventory
            $invCheck = $pdo->prepare("SELECT id FROM devices WHERE device_code = ?");
            $invCheck->execute([$code_input]);
            $isRegisteredByAdmin = $invCheck->fetchColumn();

            if (!$isRegisteredByAdmin) {
                $claim_err = "❌ Kode Akses Perangkat '$code_input' tidak terdaftar / belum didaftarkan oleh Admin! Pastikan Anda memasukkan kode resmi yang diberikan Admin.";
            } else {
                // Check if device_code is already assigned to another farmer
                $dupCheck = $pdo->prepare("SELECT u.name FROM greenhouses g JOIN users u ON g.user_id = u.id WHERE g.device_code = ? AND g.user_id != ?");
                $dupCheck->execute([$code_input, (int)$_SESSION['user_id']]);
                $existingOwner = $dupCheck->fetchColumn();

                if ($existingOwner) {
                    $claim_err = "⚠️ Kode Akses Perangkat '$code_input' sudah terhubung dengan akun petani lain ($existingOwner)! Hubungi Admin.";
                } else {
                    // Find existing greenhouse for this user or create one
                    $c_stmt = $pdo->prepare("SELECT id FROM greenhouses WHERE user_id = ? LIMIT 1");
                    $c_stmt->execute([(int)$_SESSION['user_id']]);
                    $exist_gh_id = $c_stmt->fetchColumn();

                    if ($exist_gh_id) {
                        $pdo->prepare("UPDATE greenhouses SET device_code = ?, status = 'active' WHERE id = ?")
                            ->execute([$code_input, $exist_gh_id]);
                        $linked_gh_id = $exist_gh_id;
                    } else {
                        $pdo->prepare("INSERT INTO greenhouses (user_id, name, commodity, device_code, status) VALUES (?, 'Greenhouse Utama', 'Melon Premium', ?, 'active')")
                            ->execute([(int)$_SESSION['user_id'], $code_input]);
                        $linked_gh_id = $pdo->lastInsertId();
                    }

                    // Update devices inventory table link
                    $pdo->prepare("UPDATE devices SET greenhouse_id = ?, status = 'online' WHERE device_code = ?")
                        ->execute([$linked_gh_id, $code_input]);

                    $claim_msg = "🎉 Perangkat '$code_input' berhasil diverifikasi dan terhubung ke akun Anda!";
                }
            }
        } catch (PDOException $e) {
            $claim_err = "Gagal mengklaim perangkat: " . $e->getMessage();
        }
    } else {
        $claim_err = "Kode Akses Perangkat wajib diisi!";
    }
}

// Fetch active greenhouses with verified hardware devices for current farmer
$user_greenhouses = [];
try {
    $gh_stmt = $pdo->prepare("SELECT * FROM greenhouses WHERE user_id = ? AND device_code IS NOT NULL AND device_code != '' AND status = 'active' ORDER BY id ASC");
    $gh_stmt->execute([(int)$_SESSION['user_id']]);
    $user_greenhouses = $gh_stmt->fetchAll();
} catch (PDOException $e) {
    $user_greenhouses = [];
}

// Selected greenhouse ID from dropdown or default
$selected_gh_id = isset($_GET['gh_id']) ? (int)$_GET['gh_id'] : (int)($user_greenhouses[0]['id'] ?? 0);
$primary_gh = null;
foreach ($user_greenhouses as $gh) {
    if ($gh['id'] == $selected_gh_id) {
        $primary_gh = $gh;
        break;
    }
}
if (!$primary_gh && !empty($user_greenhouses)) {
    $primary_gh = $user_greenhouses[0];
    $selected_gh_id = (int)$primary_gh['id'];
}
$current_gh_id = $selected_gh_id;

// Check if farmer has at least 1 active device assigned
$has_active_device = false;
foreach ($user_greenhouses as $gh) {
    if (!empty($gh['device_code'])) {
        $has_active_device = true;
        break;
    }
}

// Fetch latest unread alerts for dashboard Peringatan Terkini
$recentAlerts = [];
try {
    $r_stmt = $pdo->prepare(
        "SELECT a.*, g.name AS greenhouse_name
         FROM alerts a
         LEFT JOIN greenhouses g ON a.greenhouse_id = g.id
         WHERE a.user_id = ? AND a.is_read = 0
         ORDER BY a.triggered_at DESC
         LIMIT 5"
    );
    $r_stmt->execute([(int)$_SESSION['user_id']]);
    $recentAlerts = $r_stmt->fetchAll();
} catch (PDOException $e) {
    $recentAlerts = [];
}

include '../includes/header.php';
?>

<!-- Load Firebase SDKs and ApexCharts -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <?php 
    $active_menu = 'dashboard';
    include '../includes/sidebar.php'; 
    ?>
    
    <!-- Main Content Area -->
    <main class="main-content">

        <?php if (!empty($claim_msg)): ?>
        <div style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.3);border-radius:14px;padding:14px 18px;margin-bottom:20px;color:#15803d;font-weight:700;display:flex;align-items:center;gap:10px;">
            ✓ <?php echo htmlspecialchars($claim_msg); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($claim_err)): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:14px;padding:14px 18px;margin-bottom:20px;color:#b91c1c;font-weight:600;display:flex;align-items:center;gap:10px;">
            ⚠️ <?php echo htmlspecialchars($claim_err); ?>
        </div>
        <?php endif; ?>

        <!-- ── Dashboard Top Bar ─────────────────────────── -->
        <div class="dash-topbar">
            <div class="dash-topbar-left">
                <h1 class="dash-title">Dashboard</h1>
                <p class="dash-subtitle">Pantau dan kelola iklim mikro greenhouse tanaman presisi Anda.</p>
                
                <?php if (count($user_greenhouses) > 1): ?>
                <!-- Multi-Device Selector Custom Bootstrap Dropdown -->
                <div style="margin-top:10px;display:flex;align-items:center;gap:8px;">
                    <label style="font-size:0.8rem;font-weight:700;color:var(--text-secondary);">Pilih Perangkat/Greenhouse:</label>
                    <div style="position:relative;display:inline-block;">
                        <button type="button" onclick="togglePetaniGhMenu(event)" style="padding:7px 14px;border-radius:10px;border:1.5px solid var(--accent-color);background:#f0f7f2;font-size:0.85rem;font-weight:700;color:#0D5D31;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                            <i class="bi bi-house-door-fill" style="font-size:1rem;color:#0D5D31;"></i>
                            <span><?php echo htmlspecialchars($primary_gh['name'] ?? 'Greenhouse'); ?> (<?php echo htmlspecialchars($primary_gh['device_code'] ?: 'Belum Diverifikasi Admin'); ?>)</span>
                            <i class="bi bi-chevron-down" style="font-size:0.75rem;margin-left:4px;color:var(--text-secondary);"></i>
                        </button>
                        <div id="petani-gh-menu" style="display:none;position:absolute;top:calc(100% + 6px);left:0;width:320px;background:#fff;border:1.5px solid rgba(13,93,49,0.2);border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.12);z-index:9999;overflow:hidden;padding:6px 0;">
                            <?php foreach ($user_greenhouses as $gh): ?>
                            <div onclick="window.location.href='index.php?gh_id=<?php echo $gh['id']; ?>'" style="padding:9px 16px;font-size:0.84rem;font-weight:700;color:<?php echo $gh['id'] == $selected_gh_id ? '#0D5D31' : '#334155'; ?>;background:<?php echo $gh['id'] == $selected_gh_id ? 'rgba(13,93,49,0.08)' : 'transparent'; ?>;cursor:pointer;display:flex;align-items:center;gap:10px;transition:background 0.15s;" onmouseover="if(<?php echo $gh['id'] == $selected_gh_id ? 'false' : 'true'; ?>) this.style.background='rgba(0,0,0,0.03)'" onmouseout="if(<?php echo $gh['id'] == $selected_gh_id ? 'false' : 'true'; ?>) this.style.background='transparent'">
                                <i class="bi bi-house-door" style="font-size:1.05rem;color:#0D5D31;flex-shrink:0;"></i>
                                <div>
                                    <div><?php echo htmlspecialchars($gh['name']); ?></div>
                                    <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:normal;"><?php echo htmlspecialchars($gh['device_code'] ?: 'Belum Diverifikasi Admin'); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="dash-topbar-right">
                <!-- Connection Status Pill -->
                <span id="status-pill" class="status-pill checking">
                    <span class="indicator-dot"></span>
                    <span id="status-text">Memeriksa Status Perangkat...</span>
                </span>
                <!-- Action Buttons -->
                <a href="../petani/history.php" class="dash-btn dash-btn-outline">
                    <i data-lucide="bar-chart-2" style="width:16px;height:16px;"></i>
                    Lihat Riwayat
                </a>
                <button id="export-btn" class="dash-btn dash-btn-outline" onclick="exportData()">
                    <i data-lucide="download" style="width:16px;height:16px;"></i>
                    Ekspor CSV
                </button>
                <a href="../api/export_pdf_report.php?gh_id=<?php echo (int)($current_gh_id ?? 1); ?>" target="_blank" class="dash-btn dash-btn-primary" style="font-size:0.83rem;">
                    <i data-lucide="file-text" style="width:16px;height:16px;"></i>
                    Cetak Laporan PDF
                </a>
            </div>
        </div>

        <?php if (!$has_active_device): ?>
        <!-- Empty State for Farmers without Assigned Devices -->
        <div class="glass-card" style="padding:36px;border-radius:24px;background:rgba(255,255,255,0.95);box-shadow:0 12px 40px rgba(0,0,0,0.08);margin-bottom:28px;text-align:center;">
            <div style="width:72px;height:72px;border-radius:50%;background:rgba(13,93,49,0.1);color:#0D5D31;display:inline-flex;align-items:center;justify-content:center;margin-bottom:18px;">
                <i data-lucide="cpu" style="width:36px;height:36px;"></i>
            </div>
            <h2 style="font-family:var(--font-heading);font-weight:800;color:#0D5D31;margin:0 0 8px 0;font-size:1.4rem;">
                Selamat Datang di MicroClimeet! 🌱
            </h2>
            <p style="font-size:0.92rem;color:var(--text-secondary);max-width:560px;margin:0 auto 24px auto;line-height:1.6;">
                Akun Anda telah aktif, namun Anda belum memiliki <strong>Perangkat IoT</strong> yang terhubung. Silakan masukkan Kode Akses Perangkat atau hubungi Admin untuk berlangganan.
            </p>

            <!-- Claim Device Form -->
            <div style="max-width:460px;margin:0 auto;background:#f8faf8;border:2px dashed #0D5D31;border-radius:20px;padding:24px;">
                <h4 style="font-weight:800;color:#0D5D31;margin:0 0 8px 0;font-size:1rem;">🔑 Punya Kode Akses Perangkat?</h4>
                <p style="font-size:0.82rem;color:var(--text-secondary);margin:0 0 16px 0;">
                    Jika Anda sudah membeli perangkat dan mendapatkan Kode Akses dari Admin, masukkan di bawah ini:
                </p>

                <?php if (!empty($claim_err)): ?>
                <div style="background:#fef2f2;border:1.5px solid #fca5a5;color:#991b1b;padding:10px 14px;border-radius:12px;font-size:0.85rem;font-weight:700;margin-bottom:14px;text-align:left;">
                    <?php echo htmlspecialchars($claim_err); ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($claim_msg)): ?>
                <div style="background:#f0fdf4;border:1.5px solid #86efac;color:#166534;padding:10px 14px;border-radius:12px;font-size:0.85rem;font-weight:700;margin-bottom:14px;text-align:left;">
                    <?php echo htmlspecialchars($claim_msg); ?>
                </div>
                <?php endif; ?>
                <form method="POST" style="display:flex;gap:8px;">
                    <input type="hidden" name="claim_device" value="1">
                    <input type="text" name="device_code" required placeholder="Contoh: ESP32_GH_001"
                           style="flex:1;padding:12px 14px;border-radius:14px;border:1.5px solid #0D5D31;font-size:0.9rem;font-family:monospace;font-weight:700;background:#fff;">
                    <button type="submit" class="btn btn-primary" style="padding:12px 20px;border-radius:14px;font-weight:800;background:#0D5D31!important;color:#fff!important;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(13,93,49,0.25);">
                        Hubungkan
                    </button>
                </form>
            </div>

            <div style="margin-top:24px;font-size:0.86rem;color:var(--text-secondary);">
                Belum memiliki perangkat IoT? 
                <a href="https://wa.me/6283829634058?text=Halo%20Admin%20MicroClimeet,%20saya%20ingin%20berlangganan/membeli%20perangkat%20IoT%20Smart%20Greenhouse." target="_blank" style="color:#16a34a;font-weight:800;text-decoration:none;">
                    💬 Hubungi Admin via WhatsApp untuk Berlangganan
                </a>
            </div>
        </div>
        <?php else: ?>
        <?php if (empty($primary_gh['device_code'])): ?>
        <!-- Warning Banner for Selected Greenhouse without Device Code -->
        <div style="background: linear-gradient(135deg, #fffbe6, #fff3c4); border: 2px solid #f59e0b; border-radius: 20px; padding: 20px 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; box-shadow: 0 8px 24px rgba(245,158,11,0.15);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; border-radius: 14px; background: #f59e0b; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="shield-alert" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 1.05rem; font-weight: 800; color: #92400e;">Greenhouse "<?php echo htmlspecialchars($primary_gh['name']); ?>" Menunggu Verifikasi Admin</h4>
                    <p style="margin: 0; font-size: 0.88rem; color: #78350f; line-height: 1.5;">
                        Greenhouse ini belum terhubung dengan unit perangkat fisik (ESP32). Sinyal data live belum aktif hingga Admin mengalokasikan Kode Perangkat IoT.
                    </p>
                </div>
            </div>
            <a href="https://wa.me/6283829634058?text=Halo%20Admin%20MicroClimeet,%20saya%20baru%20menambahkan%20Greenhouse%20<?php echo urlencode($primary_gh['name']); ?>.%20Mohon%20alokasikan%20Kode%20Perangkat%20IoT." target="_blank" class="dash-btn" style="background:#d97706;color:#fff;border:none;white-space:nowrap;font-weight:700;padding:10px 18px;border-radius:12px;text-decoration:none;box-shadow:0 4px 12px rgba(217,119,6,0.25);">
                💬 Hubungi Admin
            </a>
        </div>
        <?php endif; ?>
        <div class="stat-cards-row">

            <!-- 1. Suhu Udara -->
            <div class="stat-card stat-card--primary" id="temp-card">
                <div class="stat-card-header">
                    <span class="stat-card-label">
                        <i data-lucide="thermometer" class="stat-card-label-icon"></i>
                        Suhu Udara
                    </span>
                    <button class="stat-card-icon-btn" title="Kalibrasi" onclick="openCalibration('temperature','Suhu Udara','°C')">
                        <i data-lucide="sliders" style="width:16px;height:16px;"></i>
                    </button>
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
                    <button class="stat-card-icon-btn" title="Kalibrasi" onclick="openCalibration('humidity','Kelembapan','% RH')">
                        <i data-lucide="sliders" style="width:16px;height:16px;"></i>
                    </button>
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
                    <button class="stat-card-icon-btn" title="Kalibrasi" onclick="openCalibration('air_quality','Kualitas Udara','PM2.5')">
                        <i data-lucide="sliders" style="width:16px;height:16px;"></i>
                    </button>
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
                    <button class="stat-card-icon-btn" title="Kalibrasi" onclick="openCalibration('uv','Indeks UV','Index')">
                        <i data-lucide="sliders" style="width:16px;height:16px;"></i>
                    </button>
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

        <!-- ── Main Content Grid ─────────────────────────── -->
        <div class="dash-main-grid">

            <!-- LEFT COLUMN: Chart + Health Gauge + Photo & Maps -->
            <div class="dash-col-left">

                <!-- Trend Chart Card -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h3 class="dash-card-title">
                                <i data-lucide="trending-up" style="color:#0D5D31;"></i>
                                <span>Tren Parameter Greenhouse</span>
                            </h3>
                            <p class="dash-card-sub">Data sensor real-time (7 hari terakhir)</p>
                        </div>
                        <div class="chart-filters">
                            <button class="filter-btn active" onclick="changeChartSpan(6)">6 Jam</button>
                            <button class="filter-btn" onclick="changeChartSpan(12)">12 Jam</button>
                            <button class="filter-btn" onclick="changeChartSpan(24)">24 Jam</button>
                        </div>
                    </div>
                    <div id="telemetry-chart"></div>
                </div>

                <!-- Sub-Grid Row: Health Progress + Greenhouse Photo -->
                <div class="dash-health-photo-row">
                    <!-- Progress Kesehatan Tanaman Card -->
                    <div class="dash-card dash-health-card" style="height:100%;">
                        <div class="dash-card-header">
                            <div>
                                <h3 class="dash-card-title">
                                    <i data-lucide="activity" style="color:#22c55e;"></i>
                                    <span>Progress Kesehatan Tanaman</span>
                                </h3>
                                <p class="dash-card-sub">Parameter dalam kondisi optimal</p>
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

                    <!-- Foto Greenhouse Card -->
                    <div class="dash-card dash-photo-card" style="height:100%;">
                        <div class="dash-card-header">
                            <div>
                                <h3 class="dash-card-title">
                                    <i data-lucide="camera" style="color:#0D5D31;"></i>
                                    <span>Foto Greenhouse</span>
                                </h3>
                                <p class="dash-card-sub"><?php echo $primary_gh ? htmlspecialchars($primary_gh['name']) : 'Kondisi Greenhouse'; ?></p>
                            </div>
                            <a href="../petani/greenhouse.php" class="dash-link-btn" style="font-size:0.78rem;">Kelola</a>
                        </div>

                        
                        <?php if ($primary_gh && !empty($primary_gh['photo']) && file_exists(__DIR__ . '/../' . $primary_gh['photo'])): ?>
                        <div class="dash-photo-container">
                            <img src="../<?php echo htmlspecialchars($primary_gh['photo']); ?>" alt="<?php echo htmlspecialchars($primary_gh['name']); ?>" class="dash-photo-img">
                        </div>
                        <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:0.82rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:4px;">
                                <i data-lucide="check-circle-2" style="width:14px;height:14px;color:#22c55e;"></i>
                                <?php echo htmlspecialchars($primary_gh['name']); ?>
                            </span>
                            <span style="font-size:0.75rem;color:var(--text-secondary);">
                                Status: <strong style="color:#166534;"><?php echo ucfirst(htmlspecialchars($primary_gh['status'] ?? 'active')); ?></strong>
                            </span>
                        </div>
                        <?php else: ?>
                        <div class="dash-photo-container">
                            <div class="dash-photo-placeholder">
                                <i data-lucide="camera" style="width:32px;height:32px;opacity:0.6;"></i>
                                <span style="font-size:0.8rem;font-weight:600;">Belum Ada Foto</span>
                                <a href="../petani/greenhouse.php" class="btn btn-primary" style="font-size:0.75rem;padding:4px 14px;margin-top:4px;border-radius:12px;">
                                    + Tambah Foto
                                </a>
                            </div>
                        </div>
                        <div style="margin-top:10px;font-size:0.8rem;color:var(--text-secondary);text-align:center;">
                            Unggah foto greenhouse Anda di menu Greenhouse Saya.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Google Maps Location Card (Below Health & Photo Row) -->
                <div class="dash-card dash-maps-card">
                    <div class="dash-card-header">
                        <div>
                            <h3 class="dash-card-title">
                                <i data-lucide="map-pin" style="color:#0D5D31;"></i>
                                <span>Lokasi Greenhouse (Google Maps)</span>
                            </h3>
                            <p class="dash-card-sub">
                                <?php 
                                    if ($primary_gh && $primary_gh['location']) {
                                        echo htmlspecialchars($primary_gh['location']);
                                    } elseif ($primary_gh && $primary_gh['latitude'] && $primary_gh['longitude']) {
                                        echo 'Koordinat GPS: ' . $primary_gh['latitude'] . ', ' . $primary_gh['longitude'];
                                    } else {
                                        echo 'Lokasi belum dikonfigurasi';
                                    }
                                ?>
                            </p>
                        </div>
                        <?php if ($primary_gh && $primary_gh['latitude'] && $primary_gh['longitude']): ?>
                        <a href="https://maps.google.com/?q=<?php echo $primary_gh['latitude']; ?>,<?php echo $primary_gh['longitude']; ?>" target="_blank" class="dash-btn dash-btn-outline" style="font-size:0.78rem;padding:5px 12px;">
                            <i data-lucide="external-link" style="width:13px;height:13px;"></i> Google Maps
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($primary_gh && $primary_gh['latitude'] && $primary_gh['longitude']): ?>
                    <div class="dash-maps-container">
                        <iframe
                            width="100%"
                            height="200"
                            frameborder="0"
                            style="border:0;display:block;"
                            loading="lazy"
                            allowfullscreen
                            src="https://maps.google.com/maps?q=<?php echo $primary_gh['latitude']; ?>,<?php echo $primary_gh['longitude']; ?>&z=16&output=embed">
                        </iframe>
                    </div>
                    <?php else: ?>
                    <div class="dash-maps-container" style="background:#f8faf8;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--text-secondary);">
                        <i data-lucide="map-pin-off" style="width:36px;height:36px;opacity:0.4;"></i>
                        <p style="font-size:0.85rem;">Koordinat lokasi GPS belum diatur.</p>
                        <a href="../petani/greenhouse.php" class="btn btn-secondary" style="font-size:0.8rem;padding:6px 14px;">
                            <i data-lucide="edit" style="width:14px;height:14px;margin-right:4px;"></i> Atur Lokasi
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- RIGHT COLUMN: Commodity Profile + Alert Timeline -->
            <div class="dash-col-right">

                <!-- Profil Komoditas Card -->
                <div class="dash-card dash-card--green">
                    <div class="dash-card-header">
                        <h3 class="dash-card-title" style="color:#fff;">
                            <i data-lucide="sprout" style="color:#fff;"></i>
                            <span>Profil Komoditas</span>
                        </h3>
                    </div>
                    <?php 
                        $comm_name = ($primary_gh && !empty($primary_gh['commodity'])) ? $primary_gh['commodity'] : 'Melon Premium';
                    ?>
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

                <!-- Alert Timeline Card -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h3 class="dash-card-title">Peringatan Terkini</h3>
                            <p class="dash-card-sub">Log kondisi anomali sensor (Belum dibaca)</p>
                        </div>
                        <a href="../petani/alerts.php" class="dash-link-btn">Lihat Semua</a>
                    </div>
                    <div id="alert-timeline" class="alert-timeline">
                        <?php if (empty($recentAlerts)): ?>
                        <div class="alert-empty">
                            <i data-lucide="check-circle" style="width:32px;height:32px;color:#4ADE80;"></i>
                            <p>Semua parameter dalam kondisi normal</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recentAlerts as $ra): ?>
                        <?php
                            $isAbove = $ra['threshold_type'] === 'above_max';
                            $levelClass = $isAbove ? '' : 'alert-item--warning';
                            $dotClass = $isAbove ? '' : 'alert-dot--warning';
                            $timeStr = date('H:i, d M', strtotime($ra['triggered_at']));
                        ?>
                        <div class="alert-item <?php echo $levelClass; ?>">
                            <div class="alert-dot <?php echo $dotClass; ?>"></div>
                            <div>
                                <div class="alert-msg"><?php echo htmlspecialchars($ra['message']); ?></div>
                                <div class="alert-time"><?php echo $timeStr; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Panduan Kondisi Card -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h3 class="dash-card-title">
                                <i data-lucide="lightbulb" style="color:#F59E0B;"></i>
                                <span>Panduan Kondisi</span>
                            </h3>
                        </div>
                    </div>
                    <div class="tip-list">
                        <div class="tip-item">
                            <div class="tip-icon tip-icon--hot"><i data-lucide="thermometer" style="width:14px;height:14px;"></i></div>
                            <div>
                                <strong>Suhu Terlalu Panas (&gt;30°C)</strong>
                                <p>Aktifkan kipas ventilasi atau shading net untuk mendinginkan mikroklimat.</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-icon tip-icon--humid"><i data-lucide="droplet" style="width:14px;height:14px;"></i></div>
                            <div>
                                <strong>Kelembapan Terlalu Tinggi (&gt;85%)</strong>
                                <p>Aktifkan blower aerasi atau buka ventilasi atas untuk mengurangi kelembapan.</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-icon tip-icon--uv"><i data-lucide="sun-medium" style="width:14px;height:14px;"></i></div>
                            <div>
                                <strong>UV Terlalu Tinggi (&gt;5)</strong>
                                <p>Pasang shading cloth 50–60% untuk melindungi tanaman dari paparan UV berlebih.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- Modal Calibration -->
<div class="modal-overlay" id="calibration-modal" style="display:none;" onclick="closeCalibration()">
    <div class="modal-box glass-card" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 id="modal-title">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(13,93,49,0.12);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="sliders" style="width:20px;height:20px;color:#0D5D31;"></i>
                </div>
                <span>Kalibrasi Sensor</span>
            </h3>
            <button type="button" onclick="closeCalibration()" class="modal-close" title="Tutup">✕</button>
        </div>
        <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 18px; line-height: 1.4;">
            Atur nilai offset untuk menyesuaikan pembacaan mentah sensor jika ada perbedaan dengan alat ukur kalibrasi eksternal.
        </p>
        <form id="calibration-form" onsubmit="saveCalibration(event)">
            <input type="hidden" id="cal-sensor-type">
            <div style="margin-bottom:18px;">
                <label for="cal-offset-input" class="form-label">Nilai Offset (<span id="modal-unit"></span>)</label>
                <input type="number" step="0.1" id="cal-offset-input" class="form-input" placeholder="0.0" required>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeCalibration()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$custom_js = ['firebase-config.js', 'db-simulator.js'];
include '../includes/footer.php'; 
?>


<!-- Dashboard Logic Integration -->
<script>
    // Load database thresholds from PHP
    const thresholds = <?php echo json_encode($thresholds); ?>;
    
    // Calibration offsets storage
    const offsets = {
        temperature: parseFloat(localStorage.getItem('cal_offset_temperature') || 0.0),
        humidity: parseFloat(localStorage.getItem('cal_offset_humidity') || 0.0),
        air_quality: parseFloat(localStorage.getItem('cal_offset_air_quality') || 0.0),
        uv: parseFloat(localStorage.getItem('cal_offset_uv') || 0.0)
    };

    // ── Trend Chart (ApexCharts - UI/UX Pro Max Gradient Area Style) ─────
    const chartOptions = {
        series: [
            { name: 'Suhu (°C)', data: [] },
            { name: 'Kelembapan (%)', data: [] },
            { name: 'Kualitas Udara (PM)', data: [] },
            { name: 'Indeks UV', data: [] }
        ],
        chart: {
            height: 280,
            type: 'area',
            background: 'transparent',
            toolbar: { show: false },
            dropShadow: {
                enabled: true,
                enabledOnSeries: [0, 1, 2, 3],
                top: 3,
                left: 0,
                blur: 5,
                color: ['#E55353', '#4A90E2', '#48BB78', '#E6A23C'],
                opacity: 0.08
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: { enabled: true, delay: 150 },
                dynamicAnimation: { enabled: true, speed: 400 }
            }
        },
        colors: ['#E55353', '#4A90E2', '#48BB78', '#E6A23C'],
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.3,
                gradientToColors: ['#FFCDD2', '#BBDEFB', '#C8E6C9', '#FFE082'],
                inverseColors: false,
                opacityFrom: 0.3,
                opacityTo: 0.02,
                stops: [0, 85, 100]
            }
        },
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 2.5,
            lineCap: 'round'
        },
        markers: {
            size: 0,
            hover: {
                size: 5,
                sizeOffset: 2
            }
        },
        xaxis: {
            type: 'datetime',
            labels: {
                style: { colors: '#334155', fontSize: '11px', fontWeight: 600 },
                datetimeUTC: false
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: [
            {
                seriesName: 'Suhu (°C)',
                title: { text: 'Suhu (°C)', style: { color: '#1E293B', fontSize: '11px', fontWeight: 700 } },
                labels: { style: { colors: '#334155', fontSize: '11px', fontWeight: 600 }, formatter: v => typeof v === 'number' ? v.toFixed(1) + '°C' : v }
            },
            {
                seriesName: 'Kelembapan (%)',
                opposite: true,
                title: { text: 'Kelembapan (%)', style: { color: '#1E293B', fontSize: '11px', fontWeight: 700 } },
                labels: { style: { colors: '#334155', fontSize: '11px', fontWeight: 600 }, formatter: v => typeof v === 'number' ? v.toFixed(1) + '%' : v }
            },
            { seriesName: 'Kualitas Udara (PM)', show: false },
            { seriesName: 'Indeks UV', show: false }
        ],
        grid: {
            borderColor: 'rgba(226, 232, 240, 0.6)',
            strokeDashArray: 5,
            padding: { top: 10, right: 15, bottom: 0, left: 15 }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center',
            fontSize: '13px',
            fontWeight: 600,
            labels: { colors: '#334155' },
            markers: { width: 10, height: 10, radius: 12 },
            itemMargin: { horizontal: 12, vertical: 5 }
        },
        tooltip: {
            theme: 'light',
            shared: true,
            intersect: false,
            x: { format: 'HH:mm:ss' },
            style: { fontSize: '12px' }
        }
    };
    const chart = new ApexCharts(document.querySelector("#telemetry-chart"), chartOptions);
    chart.render();

    // ── Health Gauge (ApexCharts Radial) ──────────────────────
    const gaugeOptions = {
        series: [0],
        chart: {
            type: 'radialBar',
            height: 180,
            background: 'transparent',
            toolbar: { show: false }
        },
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 135,
                hollow: { size: '55%' },
                track: { background: 'rgba(0,0,0,0.06)', strokeWidth: '100%' },
                dataLabels: {
                    name: { show: true, fontSize: '12px', color: '#6b7280', offsetY: 20, formatter: () => 'Kesehatan' },
                    value: { fontSize: '2rem', fontWeight: '800', color: '#0D5D31', offsetY: -12, formatter: v => v + '%' }
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: { shade: 'dark', type: 'horizontal', gradientToColors: ['#4ADE80'], stops: [0, 100] }
        },
        colors: ['#0D5D31'],
        stroke: { lineCap: 'round' },
        labels: ['Optimal'],
    };
    const gaugeChart = new ApexCharts(document.querySelector("#health-gauge-chart"), gaugeOptions);
    gaugeChart.render();

    // ── Alert Timeline ────────────────────────────────────────
    const alertLog = [];

    // ── Consecutive Abnormal Reading Tracker (Requirements 3 & 4) ──
    const consecutiveTracker = {
        temperature: { count: 0, values: [] },
        humidity:    { count: 0, values: [] },
        air_quality: { count: 0, values: [] },
        uv:          { count: 0, values: [] }
    };

    function processConsecutiveAlerts(key, status, val) {
        const tracker = consecutiveTracker[key];
        const labels  = { temperature: 'Suhu Udara', humidity: 'Kelembapan', air_quality: 'Kualitas Udara', uv: 'Indeks UV' };
        const label   = labels[key] || key;

        if (status.level !== 'optimal') {
            tracker.count++;
            tracker.values.push(val);

            // Requirement 3: 3x Consecutive Abnormal Checks -> Save Alert to Database & Display in Dashboard
            if (tracker.count === 3) {
                const sum = tracker.values.reduce((a, b) => a + b, 0);
                const avgVal = parseFloat((sum / 3).toFixed(1));
                const tType = status.text.includes('Tinggi') ? 'above_max' : 'below_min';
                const alertMsg = `${label}: ${status.text} (Rerata 3x Cek: ${avgVal})`;

                pushAlertToDBAndTimeline(alertMsg, status.level, key, avgVal, tType);
            }

            // Requirement 4: 5x Consecutive Abnormal Checks -> Trigger OS / Web Push Notification
            if (tracker.count === 5) {
                const sum = tracker.values.reduce((a, b) => a + b, 0);
                const avgVal = parseFloat((sum / 5).toFixed(1));
                const alertMsg = `🚨 ${label}: Terdeteksi Bahaya 5x Berturut-turut! (Rerata 5x Cek: ${avgVal})`;

                triggerWebPushNotification(alertMsg, key);
            }
        } else {
            // Reset consecutive tracker when status returns to optimal
            tracker.count = 0;
            tracker.values = [];
        }
    }

    function pushAlertToDBAndTimeline(message, level = 'danger', sensorType = '', avgValue = 0, threshType = 'above_max') {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        
        alertLog.unshift({ message, level, time: timeStr });
        if (alertLog.length > 5) alertLog.pop();
        
        const timeline = document.getElementById('alert-timeline');
        if (timeline) {
            timeline.innerHTML = alertLog.map(a => `
                <div class="alert-item ${a.level === 'warning' ? 'alert-item--warning' : ''}">
                    <div class="alert-dot ${a.level === 'warning' ? 'alert-dot--warning' : ''}"></div>
                    <div>
                        <div class="alert-msg">${a.message}</div>
                        <div class="alert-time">${a.time}</div>
                    </div>
                </div>
            `).join('');
        }

        // Persist Alert to MySQL Realtime DB (Requirement 2 & 3)
        if (sensorType && avgValue !== null) {
            const payload = {
                sensor_type: sensorType,
                sensor_value: avgValue,
                threshold_type: threshType,
                message: message,
                greenhouse_id: <?php echo $current_gh_id; ?>
            };
            fetch('../api/save_alert.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(res => {
                console.log("[3x Consecutive Alert Saved to MySQL]", res);
            }).catch(err => console.warn("[Save Alert Error]", err));
        }
    }

    function triggerWebPushNotification(message, sensorType = '') {
        const isEnabled = localStorage.getItem('web_push_enabled') !== 'false';
        if (!isEnabled) {
            console.log("[Web Push Suppressed] User disabled notifications via slide toggle switch.");
            return;
        }

        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                    navigator.serviceWorker.ready.then(reg => {
                        reg.showNotification('🚨 Peringatan Sensor MicroClimeet (5x Cek)', {
                            body: message,
                            icon: '../assets/images/brand/mini_logo.png',
                            badge: '../assets/images/brand/mini_logo.png',
                            vibrate: [300, 100, 300, 100, 300],
                            tag: 'sensor-alert-5x-' + sensorType,
                            renotify: true
                        });
                    });
                } else {
                    new Notification('🚨 Peringatan Sensor MicroClimeet (5x Cek)', {
                        body: message,
                        icon: '../assets/images/brand/mini_logo.png'
                    });
                }
            } catch(e) {
                console.warn("[Web Push Exception]", e);
            }
        }
    }

    function clearAlerts() {
        const timeline = document.getElementById('alert-timeline');
        if (timeline) {
            timeline.innerHTML = `
                <div class="alert-empty">
                    <i data-lucide="check-circle" style="width:32px;height:32px;color:#4ADE80;"></i>
                    <p>Semua parameter dalam kondisi normal</p>
                </div>`;
            if (window.lucide) lucide.createIcons();
        }
    }

    // ── Update Dashboard UI ───────────────────────────────────
    let chartHistory = [];

    function updateDashboardUI(rawData) {
        if (!rawData) return;

        // Extract values with fallbacks to avoid NaN
        const tempRaw = typeof rawData.temperature === 'number' ? rawData.temperature : (parseFloat(rawData.temperature) || 0);
        const humRaw  = typeof rawData.humidity === 'number' ? rawData.humidity : (parseFloat(rawData.humidity) || 0);
        const airRaw  = typeof rawData.air_quality === 'number' ? rawData.air_quality : (parseFloat(rawData.air_quality) || 0);
        const uvRaw   = typeof rawData.uv === 'number' ? rawData.uv : (parseFloat(rawData.uv) || 0);

        // Apply calibration offsets
        const d = {
            temperature: parseFloat((tempRaw + (offsets.temperature || 0)).toFixed(1)),
            humidity:    parseFloat((humRaw + (offsets.humidity || 0)).toFixed(1)),
            air_quality: parseFloat((airRaw + (offsets.air_quality || 0)).toFixed(1)),
            uv:          parseFloat((uvRaw + (offsets.uv || 0)).toFixed(1))
        };

        // Re-evaluate statuses post-calibration
        const statuses = {};
        for (const [key, value] of Object.entries(d)) {
            statuses[key] = simulatorInstance.evaluateStatus(key, value);
        }

        // Update stat card values
        document.getElementById('temp-val').innerText = d.temperature.toFixed(1);
        document.getElementById('hum-val').innerText  = d.humidity.toFixed(1);
        document.getElementById('air-val').innerText  = d.air_quality.toFixed(0);
        document.getElementById('uv-val').innerText   = d.uv.toFixed(1);

        // Update status badges and card health level classes first
        updateStatBadge('temp-status', statuses.temperature);
        updateStatBadge('hum-status',  statuses.humidity);
        updateStatBadge('air-status',  statuses.air_quality);
        updateStatBadge('uv-status',   statuses.uv);

        updateCardHealth('temp-card', statuses.temperature);
        updateCardHealth('hum-card',  statuses.humidity);
        updateCardHealth('air-card',  statuses.air_quality);
        updateCardHealth('uv-card',   statuses.uv);

        // Flash each card to signal new data arrived (pulses in optimal/warning/danger color)
        ['temp-card','hum-card','air-card','uv-card'].forEach(id => flashCard(id));

        // Determine health percentages
        const vals = Object.values(statuses);
        const optimal = vals.filter(s => s.level === 'optimal' || s.class === 'badge-success').length;
        const warning = vals.filter(s => s.level === 'warning' || s.class === 'badge-warning').length;
        const danger  = vals.filter(s => s.level === 'danger'  || s.class === 'badge-danger').length;
        const total   = vals.length || 1;
        
        const pctOptimal = Math.round((optimal / total) * 100);
        const pctWarning = Math.round((warning / total) * 100);
        const pctDanger  = Math.round((danger  / total) * 100);

        gaugeChart.updateSeries([pctOptimal]);
        document.getElementById('health-pct-optimal').innerText = pctOptimal + '%';
        document.getElementById('health-pct-warning').innerText = pctWarning + '%';
        document.getElementById('health-pct-danger').innerText  = pctDanger + '%';

        // Evaluate consecutive abnormal readings (Requirements 3 & 4)
        let hasAlert = false;
        for (const [key, status] of Object.entries(statuses)) {
            const val = d[key];
            processConsecutiveAlerts(key, status, val);
            if (status.level !== 'optimal') {
                hasAlert = true;
            }
        }
        // If all back to optimal and no alerts, clear alert timeline
        if (!hasAlert && pctOptimal === 100 && alertLog.length === 0) {
            clearAlerts();
        }
        lastStatuses = Object.fromEntries(Object.entries(statuses).map(([k, v]) => [k, v.level]));

        // Chart history
        const timestamp = new Date().getTime();
        chartHistory.push({ time: timestamp, temp: d.temperature, hum: d.humidity, air: d.air_quality, uv: d.uv });
        if (chartHistory.length > 30) chartHistory.shift();
        chart.updateSeries([
            { name: 'Suhu (°C)',         data: chartHistory.map(p => [p.time, p.temp]) },
            { name: 'Kelembapan (%)',     data: chartHistory.map(p => [p.time, p.hum]) },
            { name: 'Kualitas Udara (PM)', data: chartHistory.map(p => [p.time, p.air]) },
            { name: 'Indeks UV',         data: chartHistory.map(p => [p.time, p.uv]) }
        ]);
    }

    function updateStatBadge(id, status) {
        const el = document.getElementById(id);
        el.innerText = status.text;
        const map = { optimal: 'stat-badge--normal', warning: 'stat-badge--warning', danger: 'stat-badge--danger' };
        el.className = 'stat-badge ' + (map[status.level] || 'stat-badge--normal');
    }

    function updateCardHealth(cardId, status) {
        const card = document.getElementById(cardId);
        if (!card) return;
        card.classList.remove('stat-card--optimal', 'stat-card--warning', 'stat-card--danger');
        card.style.borderColor = '';
        card.style.boxShadow   = '';
        if (status.level === 'warning') {
            card.classList.add('stat-card--warning');
            card.style.borderColor = 'rgba(245,158,11,0.4)';
            card.style.boxShadow   = '0 4px 16px rgba(245,158,11,0.1)';
        } else if (status.level === 'danger') {
            card.classList.add('stat-card--danger');
            card.style.borderColor = 'rgba(239,68,68,0.4)';
            card.style.boxShadow   = '0 4px 16px rgba(239,68,68,0.1)';
        } else {
            card.classList.add('stat-card--optimal');
        }
    }

    // ── Card Data-Update Flash ────────────────────────────────
    function flashCard(cardId) {
        const card = document.getElementById(cardId);
        if (!card) return;
        // Remove then re-add class to retrigger animation even if already playing
        card.classList.remove('stat-card--updated');
        // Force reflow so the browser registers the removal
        void card.offsetWidth;
        card.classList.add('stat-card--updated');
        // Clean up after animation ends (1.6s)
        setTimeout(() => card.classList.remove('stat-card--updated'), 1650);
    }


    // ── Chart Span Filter ─────────────────────────────────────
    function changeChartSpan(hours) {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        // Keep last N points based on span (approx 1 point/5s)
        const maxPoints = (hours * 3600) / 5;
        chartHistory = chartHistory.slice(-maxPoints);
    }

    // ── Export CSV Professional Layout ────────────────────────────────────
    function exportData() {
        window.location.href = '../api/export_telemetry.php';
    }

    // ── Calibration Modal ─────────────────────────────────────
    function openCalibration(sensorType, sensorName, unit) {
        document.getElementById('cal-sensor-type').value  = sensorType;
        document.getElementById('modal-title').innerText  = 'Kalibrasi ' + sensorName;
        document.getElementById('modal-unit').innerText   = unit;
        document.getElementById('cal-offset-input').value = offsets[sensorType];
        document.getElementById('calibration-modal').style.display = 'flex';
    }

    function closeCalibration() {
        document.getElementById('calibration-modal').style.display = 'none';
    }

    function saveCalibration(event) {
        event.preventDefault();
        const sensorType = document.getElementById('cal-sensor-type').value;
        const val = parseFloat(document.getElementById('cal-offset-input').value) || 0.0;
        offsets[sensorType] = val;
        localStorage.setItem('cal_offset_' + sensorType, val);
        closeCalibration();
        if (!firebaseActive) simulatorInstance.tick();
    }

    // ── Simulator / Firebase controller ───────────────────────
    const simulatorInstance = new TelemetrySimulator(thresholds, updateDashboardUI);
    let simulatorActive = false;

    let lastTelemetryRxTime = 0;
    let hardwareTimeoutTimer = null;

    function setStatus(mode) {
        const pill = document.getElementById('status-pill');
        const text = document.getElementById('status-text');
        const btn  = document.getElementById('toggle-sim-btn');
        if (mode === 'unverified') {
            if (pill) pill.className = 'status-pill device-offline';
            if (text) text.innerText = 'Perangkat Belum Diverifikasi Admin';
            if (btn) btn.style.display = 'none';
        } else if (mode === 'checking') {
            if (pill) pill.className = 'status-pill checking';
            if (text) text.innerText = 'Memeriksa Status Perangkat...';
            if (btn) btn.style.display = 'none';
        } else if (mode === 'live') {
            if (pill) pill.className = 'status-pill live';
            if (text) text.innerText = 'Perangkat Live (ESP32 On)';
            if (btn) btn.style.display = 'none';
        } else if (mode === 'device_offline') {
            if (pill) pill.className = 'status-pill device-offline';
            if (text) text.innerText = 'Perangkat Offline (Mati / Putus)';
            if (btn) btn.style.display = 'none';
        } else if (mode === 'offline') {
            if (pill) pill.className = 'status-pill offline';
            if (text) text.innerText = 'Menghubungkan Firebase...';
            if (btn) btn.style.display = 'none';
        } else {
            if (pill) pill.className = 'status-pill simulator';
            if (text) text.innerText = 'Simulator Aktif';
            if (btn) {
                btn.style.display = 'inline-flex';
                btn.innerHTML  = '<i data-lucide="square" style="width:16px;height:16px;"></i> Matikan Simulator';
                btn.className  = 'dash-btn dash-btn-primary';
            }
            if (window.lucide) lucide.createIcons();
        }
    }

    const currentDeviceCode = <?php echo json_encode($primary_gh['device_code'] ?? ''); ?>;

    window.onload = function() {
        if (!currentDeviceCode) {
            console.log("[Dashboard] Selected greenhouse has no device code yet (Unverified by Admin).");
            setStatus('unverified');
            updateDashboardUI({
                temp: '--',
                hum: '--',
                pm25: '--',
                uv_index: '--'
            });
            return;
        }

        if (firebaseActive && currentDeviceCode) {
            // Bind telemetry reference strictly to current farmer's device code node
            if (currentDeviceCode === 'ESP32_GH_001') {
                telemetryRef = firebase.database().ref("telemetry");
            } else {
                telemetryRef = firebase.database().ref("devices/" + currentDeviceCode + "/telemetry");
            }

            setStatus('checking');

            // Initial 4-second checking window to evaluate real hardware device status
            const initialCheckTimeout = setTimeout(() => {
                if (lastTelemetryRxTime === 0) {
                    console.warn("[Initial Hardware Check] No telemetry received from ESP32 within 4s.");
                    setStatus('device_offline');
                }
            }, 4000);

            try {
                firebase.database().ref('.info/connected').on('value', (snap) => {
                    if (snap.val() === false) {
                        setStatus('offline');
                    }
                });
            } catch (e) {
                console.warn("[Firebase Connected Check Exception]", e);
            }

            // Hardware heartbeat timer: Check every 3 seconds if device has stopped sending packets (>15s)
            if (!hardwareTimeoutTimer) {
                hardwareTimeoutTimer = setInterval(() => {
                    if (firebaseActive && lastTelemetryRxTime > 0) {
                        const ageInSec = (Date.now() - lastTelemetryRxTime) / 1000;
                        if (ageInSec > 15) {
                            setStatus('device_offline');
                        }
                    }
                }, 3000);
            }

            let lastSyncTime = 0;
            function autoSyncTelemetryToMySQL(data) {
                const now = Date.now();
                if (now - lastSyncTime < 60000) return; // Sync at most once per minute
                lastSyncTime = now;

                const payload = {
                    greenhouse_id: <?php echo (int)($greenhouses[0]['id'] ?? 1); ?>,
                    temperature: data.temperature ?? data.temp,
                    humidity: data.humidity ?? data.hum,
                    air_quality: data.air_quality ?? data.pm25 ?? data.pms,
                    uv: data.uv ?? data.uv_index
                };

                fetch('../api/save_telemetry.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                }).catch(err => console.warn('[AutoSync MySQL Error]', err));
            }

            let isInitialFirebaseLoad = true;

            telemetryRef.on('value', (snapshot) => {
                const data = snapshot.val();
                console.log("[Firebase Telemetry Received]", data);
                if (data) {
                    updateDashboardUI(data);

                    // Extract packet timestamp if available
                    const rawTs = data.timestamp || data.updated_at || data.time || data.ts || 0;
                    const packetTime = (rawTs > 1e11) ? rawTs : (rawTs > 1e8 ? rawTs * 1000 : 0);
                    const now = Date.now();

                    if (packetTime > 0) {
                        const packetAgeSec = (now - packetTime) / 1000;
                        if (packetAgeSec > 20) {
                            console.warn(`[Stale Telemetry Data] Packet age is ${packetAgeSec.toFixed(1)}s > 20s. ESP32 hardware is OFFLINE.`);
                            clearTimeout(initialCheckTimeout);
                            setStatus('device_offline');
                            isInitialFirebaseLoad = false;
                            return;
                        }
                    }

                    if (isInitialFirebaseLoad) {
                        isInitialFirebaseLoad = false;
                        if (packetTime > 0 && (now - packetTime <= 20000)) {
                            lastTelemetryRxTime = now;
                            clearTimeout(initialCheckTimeout);
                            setStatus('live');
                            autoSyncTelemetryToMySQL(data);
                        } else {
                            console.log("[Initial Snapshot Cached] Waiting for initialCheckTimeout (4s) to verify fresh hardware push...");
                        }
                    } else {
                        // Live update pushed from active ESP32
                        lastTelemetryRxTime = now;
                        clearTimeout(initialCheckTimeout);
                        setStatus('live');
                        autoSyncTelemetryToMySQL(data);
                    }
                } else {
                    console.warn("[Firebase Snapshot Empty] Device has no telemetry data yet.");
                    clearTimeout(initialCheckTimeout);
                    setStatus('device_offline');
                }
            }, (error) => {
                console.error("[Firebase Listener Error]", error);
                clearTimeout(initialCheckTimeout);
                setStatus('sim');
                simulatorInstance.start();
                simulatorActive = true;
            });
        } else {
            setStatus('sim');
            simulatorInstance.start();
            simulatorActive = true;
        }
    };

    // ── Real-Time Threshold Sync from Admin ──────────────────────
    function pollThresholds() {
        const ghId = <?php echo (int)($current_gh_id ?? 1); ?>;
        fetch('../api/get_thresholds.php?gh_id=' + ghId)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.thresholds) {
                    const t = res.thresholds;
                    simulatorInstance.thresholds = t;

                    // Update Hero Stat Card Range Labels
                    const tempRange = document.getElementById('temp-stat-range');
                    const humRange  = document.getElementById('hum-stat-range');
                    const airRange  = document.getElementById('air-stat-range');
                    const uvRange   = document.getElementById('uv-stat-range');

                    if (tempRange) tempRange.innerText = `Target: ${t.temperature.min}–${t.temperature.max}°C`;
                    if (humRange)  humRange.innerText  = `Target: ${t.humidity.min}–${t.humidity.max}%`;
                    if (airRange)  airRange.innerText  = `Target: ${t.air_quality.min}–${t.air_quality.max} PM2.5`;
                    if (uvRange)   uvRange.innerText   = `Target: ${t.uv.min}–${t.uv.max} UV`;

                    // Update Commodity Profile Card Target Labels
                    const commTemp = document.getElementById('comm-temp-target');
                    const commHum  = document.getElementById('comm-hum-target');
                    const commUv   = document.getElementById('comm-uv-target');
                    const commAir  = document.getElementById('comm-air-target');

                    if (commTemp) commTemp.innerText = `${t.temperature.min}° – ${t.temperature.max}°C`;
                    if (commHum)  commHum.innerText  = `${t.humidity.min}% – ${t.humidity.max}%`;
                    if (commUv)   commUv.innerText   = `${t.uv.max} UV Index`;
                    if (commAir)  commAir.innerText  = `${t.air_quality.max} µg/m³`;
                }
            })
            .catch(err => console.warn('[Real-time Threshold Sync Warning]', err));
    }

    // ── Real-Time Crop Cycle & Nutrients Sync ────────────────────
    function pollCropCycle() {
        const ghId = <?php echo (int)($current_gh_id ?? 1); ?>;
        fetch('../api/get_crop_cycle.php?gh_id=' + ghId)
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

    setInterval(pollCropCycle, 4000);
    pollCropCycle();

    const simBtn = document.getElementById('toggle-sim-btn');
    if (simBtn) {
        simBtn.addEventListener('click', function() {
            if (simulatorActive) {
                simulatorInstance.stop();
                simulatorActive = false;
                this.innerHTML = '<i data-lucide="play" style="width:16px;height:16px;"></i> Aktifkan Simulator';
                this.className = 'dash-btn dash-btn-outline';
                if (document.getElementById('status-text')) document.getElementById('status-text').innerText = 'Simulator Berhenti';
                if (document.getElementById('status-pill')) document.getElementById('status-pill').className = 'status-pill';
                ['temp-val','hum-val','uv-val'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.innerText = '--.-';
                });
                const airEl = document.getElementById('air-val');
                if (airEl) airEl.innerText = '---';
            } else {
                setStatus('sim');
                simulatorInstance.start();
                simulatorActive = true;
            }
            if (window.lucide) lucide.createIcons();
        });
    }

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
            greenhouse_id: <?php echo (int)($current_gh_id ?? 1); ?>,
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
</script>

<!-- Modal Edit Crop Cycle -->
<div id="modal-crop-cycle" class="modal-overlay" style="display:none;" onclick="closeCropCycleModal()">
    <div class="modal-box glass-card" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3>
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(13,93,49,0.12);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="calendar" style="width:20px;height:20px;color:#0D5D31;"></i>
                </div>
                <span>Pengaturan Siklus Tanam</span>
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
function togglePetaniGhMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('petani-gh-menu');
    if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', (e) => {
    const menu = document.getElementById('petani-gh-menu');
    if (menu && !menu.contains(e.target)) menu.style.display = 'none';
});
</script>
