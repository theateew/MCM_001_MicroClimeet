<?php
/**
 * petani/history.php — Riwayat Data Sensor + Grafik + Export CSV
 */
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('petani');

$page_title  = 'Riwayat Data Sensor';
$custom_css  = ['dashboard.css'];
$base_url    = '../';
$active_menu = 'history';
$user_id     = (int)$_SESSION['user_id'];

// Get greenhouses milik user
$ghStmt = $pdo->prepare("SELECT id, name FROM greenhouses WHERE user_id = ? AND status = 'active' ORDER BY name");
$ghStmt->execute([$user_id]);
$greenhouses = $ghStmt->fetchAll();

$gh_id    = (int)($_GET['gh']       ?? ($greenhouses[0]['id'] ?? 0));
$sensor   = $_GET['sensor']          ?? 'temperature';
$from     = $_GET['from']            ?? date('Y-m-d', strtotime('-7 days'));
$to       = $_GET['to']              ?? date('Y-m-d');
$group_by = in_array($_GET['group_by'] ?? '', ['hour','day']) ? $_GET['group_by'] : 'hour';

$allowedSensors = ['temperature','humidity','air_quality','uv'];
if (!in_array($sensor, $allowedSensors)) $sensor = 'temperature';

$sensorLabels = [
    'temperature' => 'Suhu Udara (°C)',
    'humidity'    => 'Kelembapan (%)',
    'air_quality' => 'Kualitas Udara (AQI)',
    'uv'          => 'Indeks UV',
];
$sensorColors = [
    'temperature' => '#ef4444',
    'humidity'    => '#3b82f6',
    'air_quality' => '#a855f7',
    'uv'          => '#f59e0b',
];

// Fetch history data
$historyData = [];
if ($gh_id > 0) {
    // Verify ownership
    $checkStmt = $pdo->prepare("SELECT id FROM greenhouses WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$gh_id, $user_id]);
    if ($checkStmt->fetch()) {
        $timeFormat = $group_by === 'day'
            ? "DATE_FORMAT(recorded_at, '%Y-%m-%d')"
            : "DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00')";

        $sql = "SELECT {$timeFormat} AS period, ROUND(AVG($sensor), 2) AS value
                FROM telemetry_logs
                WHERE greenhouse_id = ?
                  AND recorded_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
                GROUP BY period
                ORDER BY period ASC
                LIMIT 500";
        $histStmt = $pdo->prepare($sql);
        $histStmt->execute([$gh_id, $from, $to]);
        $historyData = $histStmt->fetchAll();
    }
}

// CSV Export
if (isset($_GET['export']) && $gh_id > 0) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="riwayat_sensor_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Periode', $sensorLabels[$sensor]]);
    foreach ($historyData as $row) {
        fputcsv($out, [$row['period'], $row['value']]);
    }
    fclose($out);
    exit;
}

$chartLabels = json_encode(array_column($historyData, 'period'));
$chartValues = json_encode(array_column($historyData, 'value'));
$hasData     = !empty($historyData);

include '../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2>📊 Riwayat Data Sensor</h2>
                <p>Analisis tren data sensor greenhouse Anda dalam rentang waktu tertentu.</p>
            </div>
            <?php if ($hasData): ?>
            <div class="header-controls">
                <a href="?gh=<?php echo $gh_id; ?>&sensor=<?php echo $sensor; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>&group_by=<?php echo $group_by; ?>&export=1"
                   class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;gap:6px;display:flex;align-items:center;">
                    <i data-lucide="download" style="width:15px;height:15px;"></i>
                    Export CSV
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filter Panel -->
        <div class="glass-card" style="padding:20px;margin-bottom:20px;">
            <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;align-items:end;">
                <div>
                    <label class="form-label">Greenhouse</label>
                    <select name="gh" class="form-input" onchange="this.form.submit()">
                        <?php foreach ($greenhouses as $g): ?>
                        <option value="<?php echo $g['id']; ?>" <?php echo $gh_id==$g['id']?'selected':''; ?>>
                            <?php echo htmlspecialchars($g['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Sensor</label>
                    <select name="sensor" class="form-input">
                        <?php foreach ($sensorLabels as $key => $lbl): ?>
                        <option value="<?php echo $key; ?>" <?php echo $sensor===$key?'selected':''; ?>><?php echo $lbl; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="from" class="form-input" value="<?php echo $from; ?>" max="<?php echo $to; ?>">
                </div>
                <div>
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-input" value="<?php echo $to; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label class="form-label">Granularitas</label>
                    <select name="group_by" class="form-input">
                        <option value="hour" <?php echo $group_by==='hour'?'selected':''; ?>>Per Jam</option>
                        <option value="day"  <?php echo $group_by==='day' ?'selected':''; ?>>Per Hari</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                        <i data-lucide="search" style="width:15px;height:15px;"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>

        <?php if (empty($greenhouses)): ?>
        <div class="glass-card" style="text-align:center;padding:50px;">
            <i data-lucide="home" style="width:48px;height:48px;color:var(--accent-color);margin-bottom:12px;"></i>
            <p>Anda belum memiliki greenhouse. <a href="greenhouse.php" style="color:var(--accent-color);">Tambah sekarang →</a></p>
        </div>
        <?php elseif (!$hasData): ?>
        <div class="glass-card" style="text-align:center;padding:50px;">
            <i data-lucide="bar-chart-2" style="width:48px;height:48px;color:var(--text-secondary);margin-bottom:12px;"></i>
            <h3 style="font-family:var(--font-heading);margin-bottom:8px;">Belum Ada Data</h3>
            <p style="color:var(--text-secondary);font-size:0.9rem;">Tidak ada riwayat sensor pada rentang tanggal yang dipilih.</p>
        </div>
        <?php else: ?>
        <!-- Summary Stats -->
        <?php
            $values  = array_column($historyData, 'value');
            $min_val = round(min($values), 2);
            $max_val = round(max($values), 2);
            $avg_val = round(array_sum($values) / count($values), 2);
        ?>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
            <?php foreach ([['Min', $min_val], ['Rata-rata', $avg_val], ['Maks', $max_val]] as [$lbl, $val]): ?>
            <div class="glass-card" style="padding:18px;text-align:center;">
                <p style="color:var(--text-secondary);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;"><?php echo $lbl; ?></p>
                <p style="font-size:1.8rem;font-family:var(--font-heading);font-weight:800;color:<?php echo $sensorColors[$sensor]; ?>;"><?php echo $val; ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Chart -->
        <div class="glass-card" style="padding:24px;margin-bottom:20px;">
            <div id="history-chart"></div>
        </div>

        <!-- Data Table -->
        <div class="glass-card" style="padding:0;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:space-between;">
                <h4 style="font-family:var(--font-heading);font-size:1rem;font-weight:700;">Data Riwayat</h4>
                <span style="font-size:0.82rem;color:var(--text-secondary);"><?php echo count($historyData); ?> baris</span>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:rgba(0,0,0,0.03);">
                            <th style="text-align:left;padding:10px 20px;font-size:0.82rem;font-weight:600;color:var(--text-secondary);">Periode</th>
                            <th style="text-align:right;padding:10px 20px;font-size:0.82rem;font-weight:600;color:var(--text-secondary);"><?php echo htmlspecialchars($sensorLabels[$sensor]); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($historyData) as $row): ?>
                        <tr style="border-top:1px solid rgba(0,0,0,0.05);">
                            <td style="padding:10px 20px;font-size:0.85rem;"><?php echo htmlspecialchars($row['period']); ?></td>
                            <td style="padding:10px 20px;font-size:0.85rem;text-align:right;font-weight:600;color:<?php echo $sensorColors[$sensor]; ?>;"><?php echo $row['value']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<style>
.form-label { display:block; font-weight:600; font-size:0.82rem; color:var(--text-secondary); margin-bottom:4px; }
.form-input { width:100%; padding:8px 12px; border-radius:10px; border:1.5px solid rgba(0,0,0,0.1); background:rgba(255,255,255,0.7); font-size:0.88rem; box-sizing:border-box; }
.form-input:focus { border-color:var(--accent-color); outline:none; }
</style>

<?php if ($hasData): ?>
<script>
const labels = <?php echo $chartLabels; ?>;
const values = <?php echo $chartValues; ?>;
const chart = new ApexCharts(document.getElementById('history-chart'), {
    chart: { type: 'area', height: 320, toolbar: { show: true }, zoom: { enabled: true }, animations: { enabled: true, speed: 600 } },
    series: [{ name: '<?php echo addslashes($sensorLabels[$sensor]); ?>', data: values }],
    xaxis: { categories: labels, labels: { rotate: -35, style: { fontSize: '11px' } }, tickAmount: 12 },
    yaxis: { labels: { formatter: (v) => v?.toFixed(1) } },
    colors: ['<?php echo $sensorColors[$sensor]; ?>'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
    stroke: { curve: 'smooth', width: 2.5 },
    grid: { borderColor: 'rgba(0,0,0,0.06)', strokeDashArray: 4 },
    tooltip: { x: { format: 'dd MMM HH:mm' } },
    dataLabels: { enabled: false },
    markers: { size: labels.length < 30 ? 4 : 0 },
});
chart.render();
</script>
<?php endif; ?>

<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
<?php include '../includes/footer.php'; ?>
