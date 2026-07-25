<?php
/**
 * admin/thresholds.php — Konfigurasi Threshold Sensor Dedicated
 */
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$page_title    = 'Konfigurasi Threshold';
$custom_css    = ['dashboard.css'];
$base_url      = '../';
$active_menu   = 'admin_thresholds';
$is_admin_area = true;
$message = $error = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_thresholds'])) {
    try {
        $pdo->beginTransaction();
        $sensors = ['temperature', 'humidity', 'air_quality', 'uv'];
        foreach ($sensors as $s) {
            $min = (float)$_POST["min_$s"];
            $max = (float)$_POST["max_$s"];
            if ($min >= $max) throw new Exception("Min $s tidak boleh >= Max $s");
            $pdo->prepare("UPDATE global_thresholds SET min_val=?, max_val=? WHERE sensor_type=?")
                ->execute([$min, $max, $s]);
        }
        $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)")
            ->execute([$_SESSION['user_id'], "Memperbarui konfigurasi threshold sensor global"]);
        $pdo->commit();
        $message = "Threshold berhasil diperbarui dan diterapkan ke seluruh dashboard!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Gagal menyimpan: " . $e->getMessage();
    }
}

// Fetch current thresholds
$thresholds = [];
$rows = $pdo->query("SELECT * FROM global_thresholds")->fetchAll();
foreach ($rows as $r) {
    $thresholds[$r['sensor_type']] = ['min' => $r['min_val'], 'max' => $r['max_val']];
}

// Sensor config
$sensors = [
    'temperature' => ['label' => 'Suhu Udara',      'unit' => '°C',  'icon' => 'thermometer', 'color' => '#ef4444', 'global_min' => -10,  'global_max' => 60],
    'humidity'    => ['label' => 'Kelembapan',       'unit' => '%',   'icon' => 'droplets',    'color' => '#3b82f6', 'global_min' => 0,    'global_max' => 100],
    'air_quality' => ['label' => 'Kualitas Udara',   'unit' => 'AQI', 'icon' => 'wind',        'color' => '#a855f7', 'global_min' => 0,    'global_max' => 500],
    'uv'          => ['label' => 'Indeks UV',        'unit' => 'UV',  'icon' => 'sun',         'color' => '#f59e0b', 'global_min' => 0,    'global_max' => 12],
];

// Recent threshold changes from logs
$recentChanges = $pdo->query(
    "SELECT al.*, u.name FROM activity_logs al
     JOIN users u ON al.user_id = u.id
     WHERE al.activity LIKE '%threshold%'
     ORDER BY al.accessed_at DESC LIMIT 10"
)->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2>⚙️ Konfigurasi Threshold</h2>
                <p>Atur batas nilai normal untuk setiap sensor. Nilai di luar batas akan memicu peringatan.</p>
            </div>
        </div>

        <?php if ($message): ?><div style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.3);border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#166534;display:flex;align-items:center;gap:10px;"><i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#b91c1c;display:flex;align-items:center;gap:10px;"><i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0;"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST">
            <input type="hidden" name="update_thresholds" value="1">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-bottom:24px;">
                <?php foreach ($sensors as $key => $cfg): ?>
                <?php
                    $min = (float)($thresholds[$key]['min'] ?? 0);
                    $max = (float)($thresholds[$key]['max'] ?? 100);
                    $gMin = $cfg['global_min'];
                    $gMax = $cfg['global_max'];
                    $rangeWidth = $gMax - $gMin;
                    $minPct = max(0, min(100, (($min - $gMin) / $rangeWidth) * 100));
                    $maxPct = max(0, min(100, (($max - $gMin) / $rangeWidth) * 100));
                ?>
                <div class="glass-card" style="padding:24px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
                        <div style="width:40px;height:40px;border-radius:50%;background:<?php echo $cfg['color']; ?>18;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="<?php echo $cfg['icon']; ?>" style="width:20px;height:20px;color:<?php echo $cfg['color']; ?>;"></i>
                        </div>
                        <div>
                            <h4 style="font-family:var(--font-heading);font-size:0.95rem;font-weight:700;"><?php echo $cfg['label']; ?></h4>
                            <p style="font-size:0.75rem;color:var(--text-secondary);">Satuan: <?php echo $cfg['unit']; ?></p>
                        </div>
                    </div>

                    <!-- Visual Range Bar -->
                    <div style="position:relative;height:10px;background:rgba(0,0,0,0.08);border-radius:10px;margin-bottom:16px;overflow:hidden;">
                        <div style="position:absolute;top:0;height:100%;left:<?php echo $minPct; ?>%;width:<?php echo ($maxPct - $minPct); ?>%;background:<?php echo $cfg['color']; ?>;border-radius:10px;opacity:0.7;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:0.72rem;color:var(--text-secondary);margin-bottom:16px;">
                        <span><?php echo $gMin; ?> <?php echo $cfg['unit']; ?></span>
                        <span style="font-weight:700;color:<?php echo $cfg['color']; ?>"><?php echo $min; ?> – <?php echo $max; ?> <?php echo $cfg['unit']; ?></span>
                        <span><?php echo $gMax; ?> <?php echo $cfg['unit']; ?></span>
                    </div>

                    <!-- Inputs -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label class="form-label">Nilai Minimum</label>
                            <div style="display:flex;align-items:center;gap:4px;">
                                <input type="number" name="min_<?php echo $key; ?>" value="<?php echo $min; ?>"
                                    step="0.1" class="form-input"
                                    oninput="updateBar('<?php echo $key; ?>', <?php echo $gMin; ?>, <?php echo $gMax; ?>)">
                                <span style="font-size:0.8rem;color:var(--text-secondary);white-space:nowrap;"><?php echo $cfg['unit']; ?></span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Nilai Maksimum</label>
                            <div style="display:flex;align-items:center;gap:4px;">
                                <input type="number" name="max_<?php echo $key; ?>" value="<?php echo $max; ?>"
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
                <button type="submit" class="btn btn-primary" style="padding:11px 32px;font-size:0.95rem;">
                    <i data-lucide="save" style="width:16px;height:16px;"></i>
                    Simpan Semua Threshold
                </button>
            </div>
        </form>

        <!-- Recent changes log -->
        <?php if (!empty($recentChanges)): ?>
        <div class="glass-card" style="padding:0;overflow:hidden;margin-top:28px;">
            <div style="padding:14px 20px;border-bottom:1px solid rgba(0,0,0,0.06);">
                <h4 style="font-family:var(--font-heading);font-size:0.95rem;font-weight:700;">Riwayat Perubahan Threshold</h4>
            </div>
            <?php foreach ($recentChanges as $log): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:11px 20px;border-top:1px solid rgba(0,0,0,0.04);">
                <i data-lucide="clock" style="width:15px;height:15px;color:var(--text-secondary);flex-shrink:0;"></i>
                <span style="font-size:0.84rem;flex:1;"><?php echo htmlspecialchars($log['activity']); ?></span>
                <span style="font-size:0.78rem;color:var(--text-secondary);"><?php echo $log['name']; ?> · <?php echo date('d M Y H:i', strtotime($log['accessed_at'])); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<style>
.form-label { display:block;font-weight:600;font-size:0.8rem;color:var(--text-secondary);margin-bottom:4px; }
.form-input { width:100%;padding:8px 10px;border-radius:9px;border:1.5px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);font-size:0.88rem;box-sizing:border-box; }
.form-input:focus { border-color:var(--accent-color);outline:none; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
<?php include '../includes/footer.php'; ?>
