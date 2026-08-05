<?php
/**
 * admin/thresholds.php — Konfigurasi Threshold Sensor (Global & Per-Perangkat Petani)
 */
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$page_title    = 'Konfigurasi Threshold Perangkat';
$custom_css    = ['dashboard.css'];
$base_url      = '../';
$active_menu   = 'admin_thresholds';
$is_admin_area = true;
$message = $error = '';

// Target Greenhouse selection (0 = Global)
$target_gh_id = isset($_REQUEST['greenhouse_id']) ? (int)$_REQUEST['greenhouse_id'] : 0;

// Fetch all active greenhouses with verified hardware for dropdown selection
$all_greenhouses = [];
try {
    $ghStmt = $pdo->query(
        "SELECT g.id, g.name, g.commodity, g.device_code, u.name AS farmer_name
         FROM greenhouses g
         LEFT JOIN users u ON g.user_id = u.id
         WHERE g.device_code IS NOT NULL AND g.device_code != '' AND g.status = 'active'
         ORDER BY g.name ASC"
    );
    $all_greenhouses = $ghStmt->fetchAll();
} catch (PDOException $e) {}

// Handle form submit update thresholds
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_thresholds'])) {
    try {
        $pdo->beginTransaction();
        $target_id = (int)($_POST['target_greenhouse_id'] ?? 0);
        $sensors   = ['temperature', 'humidity', 'air_quality', 'uv'];

        if ($target_id === 0) {
            // Update Global Thresholds
            foreach ($sensors as $s) {
                $min = (float)$_POST["min_$s"];
                $max = (float)$_POST["max_$s"];
                if ($min >= $max) throw new Exception("Min $s tidak boleh >= Max $s");
                $pdo->prepare("UPDATE global_thresholds SET min_val=?, max_val=? WHERE sensor_type=?")
                    ->execute([$min, $max, $s]);
            }
            $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)")
                ->execute([$_SESSION['user_id'], "Memperbarui Konfigurasi Threshold Global Sensor"]);
            $message = "Threshold Global berhasil diperbarui!";
        } else {
            // Update Per-Greenhouse Thresholds
            foreach ($sensors as $s) {
                $min = (float)$_POST["min_$s"];
                $max = (float)$_POST["max_$s"];
                if ($min >= $max) throw new Exception("Min $s tidak boleh >= Max $s");
                
                $pdo->prepare(
                    "INSERT INTO greenhouse_thresholds (greenhouse_id, sensor_type, min_val, max_val)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE min_val=VALUES(min_val), max_val=VALUES(max_val)"
                )->execute([$target_id, $s, $min, $max]);
            }

            // Get GH Name for logs
            $ghName = "ID $target_id";
            foreach ($all_greenhouses as $gh) {
                if ($gh['id'] == $target_id) { $ghName = $gh['name']; break; }
            }

            $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)")
                ->execute([$_SESSION['user_id'], "Memperbarui Threshold khusus Perangkat Greenhouse '$ghName'"]);
            $message = "Threshold khusus untuk greenhouse \"$ghName\" berhasil disimpan dan aktif!";
        }

        $pdo->commit();
        $target_gh_id = $target_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Gagal menyimpan threshold: " . $e->getMessage();
    }
}

// Fetch current active thresholds for selected target
$thresholds = [
    'temperature' => ['min' => 20.0, 'max' => 30.0],
    'humidity'    => ['min' => 60.0, 'max' => 85.0],
    'air_quality' => ['min' => 0.0,  'max' => 50.0],
    'uv'          => ['min' => 0.0,  'max' => 5.0]
];
$is_custom_set = false;

// 1. Fetch Global defaults
try {
    $rows = $pdo->query("SELECT * FROM global_thresholds")->fetchAll();
    foreach ($rows as $r) {
        $thresholds[$r['sensor_type']] = ['min' => (float)$r['min_val'], 'max' => (float)$r['max_val']];
    }
} catch (PDOException $e) {}

// 2. Fetch greenhouse-specific thresholds if selected
if ($target_gh_id > 0) {
    try {
        $gh_rows = $pdo->prepare("SELECT * FROM greenhouse_thresholds WHERE greenhouse_id = ?");
        $gh_rows->execute([$target_gh_id]);
        $customs = $gh_rows->fetchAll();
        if (!empty($customs)) {
            $is_custom_set = true;
            foreach ($customs as $r) {
                $thresholds[$r['sensor_type']] = ['min' => (float)$r['min_val'], 'max' => (float)$r['max_val']];
            }
        }
    } catch (PDOException $e) {}
}

// Sensor configs
$sensors = [
    'temperature' => ['label' => 'Suhu Udara',      'unit' => '°C',  'icon' => 'thermometer', 'color' => '#ef4444', 'global_min' => -10,  'global_max' => 60],
    'humidity'    => ['label' => 'Kelembapan',       'unit' => '%',   'icon' => 'droplets',    'color' => '#3b82f6', 'global_min' => 0,    'global_max' => 100],
    'air_quality' => ['label' => 'Kualitas Udara',   'unit' => 'AQI', 'icon' => 'wind',        'color' => '#a855f7', 'global_min' => 0,    'global_max' => 500],
    'uv'          => ['label' => 'Indeks UV',        'unit' => 'UV',  'icon' => 'sun',         'color' => '#f59e0b', 'global_min' => 0,    'global_max' => 12],
];

// Recent changes log
$recentChanges = [];
try {
    $recentChanges = $pdo->query(
        "SELECT al.*, u.name FROM activity_logs al
         JOIN users u ON al.user_id = u.id
         WHERE al.activity LIKE '%threshold%' OR al.activity LIKE '%Threshold%'
         ORDER BY al.accessed_at DESC LIMIT 8"
    )->fetchAll();
} catch (PDOException $e) {}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2><i data-lucide="sliders-horizontal" style="color:#0D5D31;"></i> Konfigurasi Threshold Perangkat</h2>
                <p>Atur nilai ambang batas sensor baik secara Global maupun Spesifik per Perangkat Greenhouse Petani.</p>
            </div>
        </div>

        <?php if ($message): ?>
        <div style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.3);border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#166534;display:flex;align-items:center;gap:10px;">
            <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#b91c1c;display:flex;align-items:center;gap:10px;">
            <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <!-- Device Selector Card -->
        <div class="glass-card" style="padding:18px 22px;margin-bottom:24px;background:linear-gradient(135deg, rgba(255,255,255,0.9), rgba(240,244,240,0.8));">
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:42px;height:42px;border-radius:12px;background:rgba(13,93,49,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="cpu" style="width:22px;height:22px;color:var(--accent-color);"></i>
                    </div>
                    <div>
                        <h3 style="font-family:var(--font-heading);font-size:1.05rem;font-weight:700;margin:0;">Pilih Perangkat / Target Threshold</h3>
                        <p style="font-size:0.8rem;color:var(--text-secondary);margin:2px 0 0 0;">Pilih apakah Anda ingin mengubah batas sensor Global atau khusus 1 Perangkat tertentu.</p>
                    </div>
                </div>

                <form method="GET" id="form-threshold-select" style="display:flex;align-items:center;gap:10px;">
                    <input type="hidden" name="greenhouse_id" id="target_gh_id_input" value="<?php echo $target_gh_id; ?>">
                    
                    <div style="position:relative;display:inline-block;">
                        <button type="button" onclick="toggleGhThresholdMenu(event)" style="padding:9px 16px;border-radius:12px;border:1.5px solid var(--accent-color);background:#fff;font-size:0.88rem;font-weight:700;color:var(--text-primary);cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,0.06);display:inline-flex;align-items:center;gap:8px;">
                            <?php if ($target_gh_id === 0): ?>
                                <i class="bi bi-globe" style="font-size:1.05rem;color:#0D5D31;"></i>
                                <span>Global (Semua Perangkat Default)</span>
                            <?php else: ?>
                                <?php
                                $selected_gh_item = null;
                                foreach ($all_greenhouses as $g) {
                                    if ($g['id'] == $target_gh_id) { $selected_gh_item = $g; break; }
                                }
                                ?>
                                <i class="bi bi-house-door-fill" style="font-size:1.05rem;color:#0D5D31;"></i>
                                <span><?php echo htmlspecialchars($selected_gh_item['name'] ?? 'Greenhouse #'.$target_gh_id); ?> (<?php echo htmlspecialchars($selected_gh_item['farmer_name'] ?? 'Petani'); ?> - Kode: <?php echo htmlspecialchars($selected_gh_item['device_code'] ?? 'MCM-'.$target_gh_id); ?>)</span>
                            <?php endif; ?>
                            <i class="bi bi-chevron-down" style="font-size:0.75rem;margin-left:4px;color:var(--text-secondary);"></i>
                        </button>

                        <div id="gh-threshold-menu" style="display:none;position:absolute;top:calc(100% + 6px);right:0;width:360px;background:#fff;border:1.5px solid rgba(13,93,49,0.2);border-radius:14px;box-shadow:0 12px 30px rgba(0,0,0,0.12);z-index:9999;overflow:hidden;padding:6px 0;">
                            <div onclick="selectThresholdTarget(0)" style="padding:10px 16px;font-size:0.85rem;font-weight:700;color:<?php echo $target_gh_id === 0 ? '#0D5D31' : '#334155'; ?>;background:<?php echo $target_gh_id === 0 ? 'rgba(13,93,49,0.08)' : 'transparent'; ?>;cursor:pointer;display:flex;align-items:center;gap:10px;transition:background 0.15s;" onmouseover="if(<?php echo $target_gh_id === 0 ? 'false' : 'true'; ?>) this.style.background='rgba(0,0,0,0.03)'" onmouseout="if(<?php echo $target_gh_id === 0 ? 'false' : 'true'; ?>) this.style.background='transparent'">
                                <i class="bi bi-globe" style="font-size:1.1rem;color:#0D5D31;flex-shrink:0;"></i>
                                <div>
                                    <div>Global (Semua Perangkat Default)</div>
                                    <div style="font-size:0.72rem;color:var(--text-secondary);font-weight:normal;">Batas ambang berlaku secara sistemik</div>
                                </div>
                            </div>
                            <div style="border-top:1px solid rgba(0,0,0,0.06);margin:4px 0;"></div>
                            <div style="max-height:260px;overflow-y:auto;">
                                <?php foreach ($all_greenhouses as $gh): ?>
                                <div onclick="selectThresholdTarget(<?php echo $gh['id']; ?>)" style="padding:9px 16px;font-size:0.85rem;font-weight:700;color:<?php echo $target_gh_id == $gh['id'] ? '#0D5D31' : '#334155'; ?>;background:<?php echo $target_gh_id == $gh['id'] ? 'rgba(13,93,49,0.08)' : 'transparent'; ?>;cursor:pointer;display:flex;align-items:center;gap:10px;transition:background 0.15s;" onmouseover="if(<?php echo $target_gh_id == $gh['id'] ? 'false' : 'true'; ?>) this.style.background='rgba(0,0,0,0.03)'" onmouseout="if(<?php echo $target_gh_id == $gh['id'] ? 'false' : 'true'; ?>) this.style.background='transparent'">
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

            <?php if ($target_gh_id > 0): ?>
            <div style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:space-between;font-size:0.84rem;">
                <span style="color:var(--accent-dark);font-weight:600;display:flex;align-items:center;gap:6px;">
                    <i data-lucide="info" style="width:15px;height:15px;"></i>
                    <?php if ($is_custom_set): ?>
                    <span style="color:#15803d;background:#dcfce7;padding:2px 10px;border-radius:20px;font-weight:700;">Status: Threshold Khusus Perangkat Aktif</span>
                    <?php else: ?>
                    <span style="color:#b45309;background:#fef3c7;padding:2px 10px;border-radius:20px;font-weight:700;">Status: Menggunakan Default Global (Belum ada override)</span>
                    <?php endif; ?>
                </span>
                <span style="color:var(--text-secondary);">Target Perangkat ID: #<?php echo $target_gh_id; ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick Commodity Presets -->
        <div style="margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:0.83rem;font-weight:700;color:var(--text-secondary);"><i data-lucide="zap" style="width:14px;height:14px;display:inline;vertical-align:middle;"></i> Preset Cepat Komoditas:</span>
            <button type="button" onclick="applyPreset(20, 30, 60, 85, 0, 50, 0, 5)" class="preset-btn">Melon Premium</button>
            <button type="button" onclick="applyPreset(18, 28, 65, 80, 0, 45, 0, 4.5)" class="preset-btn">Tomat Ceri</button>
            <button type="button" onclick="applyPreset(22, 32, 55, 75, 0, 60, 0, 6)" class="preset-btn">Cabai Paprika</button>
            <button type="button" onclick="applyPreset(16, 25, 70, 90, 0, 35, 0, 4)" class="preset-btn">Sayur Hydroponic</button>
        </div>

        <form method="POST">
            <input type="hidden" name="update_thresholds" value="1">
            <input type="hidden" name="target_greenhouse_id" value="<?php echo $target_gh_id; ?>">

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-bottom:24px;">
                <?php foreach ($sensors as $key => $cfg): ?>
                <?php
                    $min = (float)($thresholds[$key]['min'] ?? 0);
                    $max = (float)($thresholds[$key]['max'] ?? 100);
                    $gMin = $cfg['global_min'];
                    $gMax = $cfg['global_max'];
                    $rangeWidth = max(1, $gMax - $gMin);
                    $minPct = max(0, min(100, (($min - $gMin) / $rangeWidth) * 100));
                    $maxPct = max(0, min(100, (($max - $gMin) / $rangeWidth) * 100));
                ?>
                <div class="glass-card" style="padding:22px;">
                    <!-- Card Header in 1 Row -->
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                        <div style="width:40px;height:40px;border-radius:12px;background:<?php echo $cfg['color']; ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="<?php echo $cfg['icon']; ?>" style="width:20px;height:20px;color:<?php echo $cfg['color']; ?>;"></i>
                        </div>
                        <div>
                            <h4 style="font-family:var(--font-heading);font-size:0.98rem;font-weight:700;margin:0;"><?php echo $cfg['label']; ?></h4>
                            <span style="font-size:0.75rem;color:var(--text-secondary);">Satuan: <?php echo $cfg['unit']; ?></span>
                        </div>
                    </div>

                    <!-- Visual Range Bar -->
                    <div style="position:relative;height:10px;background:rgba(0,0,0,0.08);border-radius:10px;margin-bottom:14px;overflow:hidden;">
                        <div id="bar_<?php echo $key; ?>" style="position:absolute;top:0;height:100%;left:<?php echo $minPct; ?>%;width:<?php echo ($maxPct - $minPct); ?>%;background:<?php echo $cfg['color']; ?>;border-radius:10px;opacity:0.75;transition:all 0.3s ease;"></div>
                    </div>
                    <div style="display:flex;justify-space-between;font-size:0.75rem;color:var(--text-secondary);margin-bottom:16px;">
                        <span>Min: <?php echo $gMin; ?> <?php echo $cfg['unit']; ?></span>
                        <span id="range_txt_<?php echo $key; ?>" style="font-weight:700;color:<?php echo $cfg['color']; ?>"><?php echo $min; ?> – <?php echo $max; ?> <?php echo $cfg['unit']; ?></span>
                        <span>Maks: <?php echo $gMax; ?> <?php echo $cfg['unit']; ?></span>
                    </div>

                    <!-- Inputs -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label class="form-label">Nilai Min Ideal</label>
                            <div style="display:flex;align-items:center;gap:4px;">
                                <input type="number" id="min_<?php echo $key; ?>" name="min_<?php echo $key; ?>" value="<?php echo $min; ?>"
                                    step="0.1" class="form-input"
                                    oninput="updateBar('<?php echo $key; ?>', <?php echo $gMin; ?>, <?php echo $gMax; ?>)">
                                <span style="font-size:0.8rem;color:var(--text-secondary);white-space:nowrap;"><?php echo $cfg['unit']; ?></span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Nilai Max Ideal</label>
                            <div style="display:flex;align-items:center;gap:4px;">
                                <input type="number" id="max_<?php echo $key; ?>" name="max_<?php echo $key; ?>" value="<?php echo $max; ?>"
                                    step="0.1" class="form-input"
                                    oninput="updateBar('<?php echo $key; ?>', <?php echo $gMin; ?>, <?php echo $gMax; ?>)">
                                <span style="font-size:0.8rem;color:var(--text-secondary);white-space:nowrap;"><?php echo $cfg['unit']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary" style="padding:12px 34px;font-size:0.95rem;box-shadow:0 4px 14px rgba(13,93,49,0.25);">
                    <i data-lucide="save" style="width:18px;height:18px;"></i>
                    Simpan Threshold <?php echo $target_gh_id > 0 ? 'Perangkat Ini' : 'Global'; ?>
                </button>
            </div>
        </form>

        <!-- Recent changes log -->
        <?php if (!empty($recentChanges)): ?>
        <div class="glass-card" style="padding:0;overflow:hidden;margin-top:28px;">
            <div style="padding:14px 20px;border-bottom:1px solid rgba(0,0,0,0.06);display:flex;align-items:center;gap:10px;">
                <i data-lucide="clock" style="width:18px;height:18px;color:var(--accent-color);"></i>
                <h4 style="font-family:var(--font-heading);font-size:0.95rem;font-weight:700;margin:0;">Riwayat Perubahan Threshold</h4>
            </div>
            <?php foreach ($recentChanges as $log): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-top:1px solid rgba(0,0,0,0.04);">
                <i data-lucide="check-circle-2" style="width:15px;height:15px;color:#22c55e;flex-shrink:0;"></i>
                <span style="font-size:0.84rem;flex:1;"><?php echo htmlspecialchars($log['activity']); ?></span>
                <span style="font-size:0.78rem;color:var(--text-secondary);"><?php echo htmlspecialchars($log['name']); ?> · <?php echo date('d M Y H:i', strtotime($log['accessed_at'])); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<style>
.form-label { display:block;font-weight:600;font-size:0.8rem;color:var(--text-secondary);margin-bottom:4px; }
.form-input { width:100%;padding:8px 10px;border-radius:9px;border:1.5px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.85);font-size:0.88rem;box-sizing:border-box; }
.form-input:focus { border-color:var(--accent-color);outline:none;background:#fff; }
.preset-btn { background:rgba(255,255,255,0.9);border:1px solid rgba(13,93,49,0.3);color:var(--accent-dark);padding:5px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;cursor:pointer;transition:all 0.2s ease; }
.preset-btn:hover { background:var(--accent-color);color:#fff;border-color:var(--accent-color); }
</style>

<script>
function updateBar(key, gMin, gMax) {
    const minEl = document.getElementById('min_' + key);
    const maxEl = document.getElementById('max_' + key);
    const barEl = document.getElementById('bar_' + key);
    const txtEl = document.getElementById('range_txt_' + key);
    if (!minEl || !maxEl || !barEl) return;

    const min = parseFloat(minEl.value) || gMin;
    const max = parseFloat(maxEl.value) || gMax;
    const width = Math.max(1, gMax - gMin);

    const minPct = Math.max(0, Math.min(100, ((min - gMin) / width) * 100));
    const maxPct = Math.max(0, Math.min(100, ((max - gMin) / width) * 100));

    barEl.style.left = minPct + '%';
    barEl.style.width = Math.max(2, maxPct - minPct) + '%';
    if (txtEl) txtEl.innerText = `${min} – ${max}`;
}

function applyPreset(tempMin, tempMax, humMin, humMax, airMin, airMax, uvMin, uvMax) {
    document.getElementById('min_temperature').value = tempMin;
    document.getElementById('max_temperature').value = tempMax;
    document.getElementById('min_humidity').value = humMin;
    document.getElementById('max_humidity').value = humMax;
    document.getElementById('min_air_quality').value = airMin;
    document.getElementById('max_air_quality').value = airMax;
    document.getElementById('min_uv').value = uvMin;
    document.getElementById('max_uv').value = uvMax;

    updateBar('temperature', -10, 60);
    updateBar('humidity', 0, 100);
    updateBar('air_quality', 0, 500);
    updateBar('uv', 0, 12);
}

function toggleGhThresholdMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('gh-threshold-menu');
    if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}
function selectThresholdTarget(id) {
    document.getElementById('target_gh_id_input').value = id;
    document.getElementById('form-threshold-select').submit();
}
document.addEventListener('click', (e) => {
    const menu = document.getElementById('gh-threshold-menu');
    if (menu && !menu.contains(e.target)) menu.style.display = 'none';
});

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
<?php include '../includes/footer.php'; ?>
