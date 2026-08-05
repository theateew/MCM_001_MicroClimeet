<?php
/**
 * petani/history.php — Riwayat Data Sensor + Grafik + Export CSV/Excel + UI/UX Pro Max Interactive Sort Chips
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

// === Check if required tables exist ===
$tablesExist = false;
$dbError     = null;
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'greenhouses'")->fetch();
    $tablesExist = !empty($tableCheck);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

// Get greenhouses milik user (only if tables exist)
$greenhouses = [];
if ($tablesExist) {
    try {
        $ghStmt = $pdo->prepare("SELECT id, name FROM greenhouses WHERE user_id = ? AND status = 'active' ORDER BY name");
        $ghStmt->execute([$user_id]);
        $greenhouses = $ghStmt->fetchAll();
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}

$gh_id    = (int)($_GET['gh']       ?? ($greenhouses[0]['id'] ?? 0));
$sensor   = $_GET['sensor']          ?? 'all';
$from     = $_GET['from']            ?? date('Y-m-d', strtotime('-7 days'));
$to       = $_GET['to']              ?? date('Y-m-d');
$group_by = in_array($_GET['group_by'] ?? '', ['hour','day']) ? $_GET['group_by'] : 'hour';

$validSorts = [
    'period_desc', 'period_asc', 'newest', 'oldest',
    'temp_desc', 'temp_asc', 'value_desc', 'value_asc',
    'hum_desc', 'hum_asc',
    'air_desc', 'air_asc',
    'uv_desc', 'uv_asc',
    'val_desc', 'val_asc'
];
$sort_by = in_array($_GET['sort_by'] ?? '', $validSorts) ? $_GET['sort_by'] : 'period_desc';

$allowedSensors = ['all', 'temperature', 'humidity', 'air_quality', 'uv'];
if (!in_array($sensor, $allowedSensors)) $sensor = 'all';

$sensorLabels = [
    'all'         => 'Semua Sensor (Gabungan)',
    'temperature' => 'Suhu Udara (°C)',
    'humidity'    => 'Kelembapan (%)',
    'air_quality' => 'Kualitas Udara (AQI)',
    'uv'          => 'Indeks UV',
];
$sensorColors = [
    'all'         => '#0D5D31',
    'temperature' => '#ef4444',
    'humidity'    => '#3b82f6',
    'air_quality' => '#a855f7',
    'uv'          => '#f59e0b',
];

$sortLabels = [
    'period_desc' => 'Periode (Terbaru → Terlama)',
    'newest'      => 'Periode (Terbaru → Terlama)',
    'period_asc'  => 'Periode (Terlama → Terbaru)',
    'oldest'      => 'Periode (Terlama → Terbaru)',
    'temp_desc'   => 'Suhu (°C) (Tertinggi → Terendah)',
    'temp_asc'    => 'Suhu (°C) (Terendah → Tertinggi)',
    'hum_desc'    => 'Kelembapan (%) (Tertinggi → Terendah)',
    'hum_asc'     => 'Kelembapan (%) (Terendah → Tertinggi)',
    'air_desc'    => 'Kualitas Udara (AQI) (Tertinggi → Terendah)',
    'air_asc'     => 'Kualitas Udara (AQI) (Terendah → Tertinggi)',
    'uv_desc'     => 'Indeks UV (Tertinggi → Terendah)',
    'uv_asc'      => 'Indeks UV (Terendah → Tertinggi)',
    'value_desc'  => 'Nilai Sensor (Tertinggi → Terendah)',
    'value_asc'   => 'Nilai Sensor (Terendah → Tertinggi)',
    'val_desc'    => 'Nilai Sensor (Tertinggi → Terendah)',
    'val_asc'     => 'Nilai Sensor (Terendah → Tertinggi)',
];

// Helper functions for header click sorting
function getSortLink($type) {
    global $sort_by;
    $params = $_GET;
    $curr = $sort_by;
    if ($curr === 'newest') $curr = 'period_desc';
    if ($curr === 'oldest') $curr = 'period_asc';
    if ($curr === 'value_desc') $curr = ($type === 'val') ? 'val_desc' : 'temp_desc';
    if ($curr === 'value_asc') $curr = ($type === 'val') ? 'val_asc' : 'temp_asc';

    if ($curr === $type . '_desc') {
        $nextSort = $type . '_asc';
    } else {
        $nextSort = $type . '_desc';
    }
    $params['sort_by'] = $nextSort;
    return '?' . http_build_query($params);
}

function isSortActive($type) {
    global $sort_by;
    $curr = $sort_by;
    if ($curr === 'newest') $curr = 'period_desc';
    if ($curr === 'oldest') $curr = 'period_asc';
    if ($curr === 'value_desc') $curr = ($type === 'val') ? 'val_desc' : 'temp_desc';
    if ($curr === 'value_asc') $curr = ($type === 'val') ? 'val_asc' : 'temp_asc';

    return ($curr === $type . '_desc' || $curr === $type . '_asc');
}

function getSortBadgeIcon($type) {
    global $sort_by;
    $curr = $sort_by;
    if ($curr === 'newest') $curr = 'period_desc';
    if ($curr === 'oldest') $curr = 'period_asc';
    if ($curr === 'value_desc') $curr = ($type === 'val') ? 'val_desc' : 'temp_desc';
    if ($curr === 'value_asc') $curr = ($type === 'val') ? 'val_asc' : 'temp_asc';

    if ($curr === $type . '_desc') {
        return '<span class="sort-badge-icon">▼</span>';
    } elseif ($curr === $type . '_asc') {
        return '<span class="sort-badge-icon">▲</span>';
    }
    return '<span class="sort-badge-icon muted">⇅</span>';
}

// Fetch history data (only if tables exist and gh_id valid)
$historyData = [];
if ($tablesExist && $gh_id > 0) {
    try {
        // Verify ownership
        $checkStmt = $pdo->prepare("SELECT id FROM greenhouses WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$gh_id, $user_id]);
        if ($checkStmt->fetch()) {
            // Check column names in telemetry_logs for schema flexibility
            $cols = $pdo->query("DESCRIBE telemetry_logs")->fetchAll(PDO::FETCH_COLUMN);
            $hasDeviceId  = in_array('device_id', $cols);
            $hasUvIndex   = in_array('uv_index', $cols);
            $hasCreatedAt = in_array('created_at', $cols);

            $timeCol   = $hasCreatedAt ? 'created_at' : 'recorded_at';
            $idCol     = $hasDeviceId ? 'device_id' : 'greenhouse_id';
            $uvCol     = $hasUvIndex ? 'uv_index' : 'uv';

            $timeFormat = $group_by === 'day'
                ? "DATE_FORMAT({$timeCol}, '%Y-%m-%d')"
                : "DATE_FORMAT({$timeCol}, '%Y-%m-%d %H:00:00')";

            // Map sort_by parameter to SQL ORDER BY
            switch ($sort_by) {
                case 'period_asc':
                case 'oldest':
                    $orderSql = "ORDER BY period ASC";
                    break;
                case 'temp_desc':
                    $orderSql = "ORDER BY temp_val DESC";
                    break;
                case 'temp_asc':
                    $orderSql = "ORDER BY temp_val ASC";
                    break;
                case 'hum_desc':
                    $orderSql = "ORDER BY hum_val DESC";
                    break;
                case 'hum_asc':
                    $orderSql = "ORDER BY hum_val ASC";
                    break;
                case 'air_desc':
                    $orderSql = "ORDER BY air_val DESC";
                    break;
                case 'air_asc':
                    $orderSql = "ORDER BY air_val ASC";
                    break;
                case 'uv_desc':
                    $orderSql = "ORDER BY uv_val DESC";
                    break;
                case 'uv_asc':
                    $orderSql = "ORDER BY uv_val ASC";
                    break;
                case 'val_desc':
                case 'value_desc':
                    $orderSql = ($sensor === 'all') ? "ORDER BY temp_val DESC" : "ORDER BY value DESC";
                    break;
                case 'val_asc':
                case 'value_asc':
                    $orderSql = ($sensor === 'all') ? "ORDER BY temp_val ASC" : "ORDER BY value ASC";
                    break;
                case 'period_desc':
                case 'newest':
                default:
                    $orderSql = "ORDER BY period DESC";
                    break;
            }

            if ($sensor === 'all') {
                $sql = "SELECT {$timeFormat} AS period,
                               ROUND(AVG(temperature), 2) AS temp_val,
                               ROUND(AVG(humidity), 2) AS hum_val,
                               ROUND(AVG(air_quality), 2) AS air_val,
                               ROUND(AVG({$uvCol}), 2) AS uv_val
                        FROM telemetry_logs
                        WHERE ({$idCol} = ? OR {$idCol} IN (SELECT id FROM devices WHERE greenhouse_id = ?))
                          AND {$timeCol} BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
                        GROUP BY period
                        {$orderSql}
                        LIMIT 500";
            } else {
                $sensorCol = ($sensor === 'uv' && $hasUvIndex) ? 'uv_index' : $sensor;
                $sql = "SELECT {$timeFormat} AS period, ROUND(AVG({$sensorCol}), 2) AS value
                        FROM telemetry_logs
                        WHERE ({$idCol} = ? OR {$idCol} IN (SELECT id FROM devices WHERE greenhouse_id = ?))
                          AND {$timeCol} BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
                        GROUP BY period
                        {$orderSql}
                        LIMIT 500";
            }

            $histStmt = $pdo->prepare($sql);
            $histStmt->execute([$gh_id, $gh_id, $from, $to]);
            $historyData = $histStmt->fetchAll();
        }
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
        $historyData = [];
    }
}

// Prepare Chronological Data for Chart (Sorted Period ASC)
$chartData = $historyData;
usort($chartData, function($a, $b) {
    return strcmp($a['period'], $b['period']);
});

// CSV & Excel Export
if (isset($_GET['export']) && $gh_id > 0 && $tablesExist) {
    $export_type = $_GET['export'];
    $gh_name = 'Greenhouse';
    foreach ($greenhouses as $g) {
        if ($g['id'] == $gh_id) { $gh_name = $g['name']; break; }
    }
    $clean_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $gh_name));

    if ($export_type === 'csv' || $export_type === '1') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="riwayat_' . $clean_name . '_' . $sensor . '_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Greenhouse', $gh_name]);
        fputcsv($out, ['Sensor', $sensorLabels[$sensor]]);
        fputcsv($out, ['Urutan (Sort By)', $sortLabels[$sort_by] ?? $sort_by]);
        fputcsv($out, ['Rentang Tanggal', "$from s/d $to"]);
        fputcsv($out, ['Granularitas', $group_by === 'day' ? 'Per Hari' : 'Per Jam']);
        fputcsv($out, ['Tanggal Export', date('Y-m-d H:i:s')]);
        fputcsv($out, []);

        if ($sensor === 'all') {
            fputcsv($out, ['Periode', 'Suhu Udara (°C)', 'Kelembapan (%)', 'Kualitas Udara (AQI)', 'Indeks UV']);
            foreach ($historyData as $row) {
                fputcsv($out, [$row['period'], $row['temp_val'], $row['hum_val'], $row['air_val'], $row['uv_val']]);
            }
        } else {
            fputcsv($out, ['Periode', $sensorLabels[$sensor]]);
            foreach ($historyData as $row) {
                fputcsv($out, [$row['period'], $row['value']]);
            }
        }

        fclose($out);
        exit;
    }

    if ($export_type === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="riwayat_' . $clean_name . '_' . $sensor . '_' . date('Ymd') . '.xls"');
        ?>
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                table { border-collapse: collapse; width: 100%; margin-top: 15px; }
                th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
                th { background-color: #0D5D31; color: white; }
                .title { font-size: 16pt; font-weight: bold; color: #0D5D31; margin-bottom: 10px; }
                .meta-table { margin-bottom: 15px; font-size: 10pt; }
                .meta-table td { border: none; padding: 4px 8px; font-weight: normal; }
                .meta-table td.bold { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="title">Laporan Riwayat Sensor MicroClimeet</div>
            <table class="meta-table">
                <tr><td class="bold">Greenhouse:</td><td><?php echo htmlspecialchars($gh_name); ?></td></tr>
                <tr><td class="bold">Sensor:</td><td><?php echo htmlspecialchars($sensorLabels[$sensor]); ?></td></tr>
                <tr><td class="bold">Urutan:</td><td><?php echo htmlspecialchars($sortLabels[$sort_by] ?? $sort_by); ?></td></tr>
                <tr><td class="bold">Rentang Tanggal:</td><td><?php echo htmlspecialchars($from); ?> s/d <?php echo htmlspecialchars($to); ?></td></tr>
                <tr><td class="bold">Tanggal Export:</td><td><?php echo date('d-m-Y H:i:s'); ?></td></tr>
            </table>
            <table>
                <thead>
                    <?php if ($sensor === 'all'): ?>
                    <tr>
                        <th>Periode</th>
                        <th>Suhu Udara (°C)</th>
                        <th>Kelembapan (%)</th>
                        <th>Kualitas Udara (AQI)</th>
                        <th>Indeks UV</th>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <th style="width: 50%;">Periode</th>
                        <th style="width: 50%;"><?php echo htmlspecialchars($sensorLabels[$sensor]); ?></th>
                    </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php foreach ($historyData as $row): ?>
                    <?php if ($sensor === 'all'): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['period']); ?></td>
                        <td><?php echo htmlspecialchars($row['temp_val']); ?></td>
                        <td><?php echo htmlspecialchars($row['hum_val']); ?></td>
                        <td><?php echo htmlspecialchars($row['air_val']); ?></td>
                        <td><?php echo htmlspecialchars($row['uv_val']); ?></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['period']); ?></td>
                        <td><?php echo htmlspecialchars($row['value']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        exit;
    }
}

$hasData = !empty($historyData);

include '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2><i data-lucide="bar-chart-2" style="color:#0D5D31;"></i> Riwayat Data Sensor</h2>
                <p>Analisis tren data sensor greenhouse Anda dalam rentang waktu tertentu.</p>
            </div>
            <?php if ($hasData): ?>
            <div class="header-controls" style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="?gh=<?php echo $gh_id; ?>&sensor=<?php echo $sensor; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>&group_by=<?php echo $group_by; ?>&sort_by=<?php echo $sort_by; ?>&export=csv"
                   class="btn btn-secondary" data-no-loader="true" download style="font-size:0.82rem;padding:8px 14px;gap:6px;display:flex;align-items:center;" title="Download format CSV">
                    <i data-lucide="file-spreadsheet" style="width:15px;height:15px;color:#10b981;"></i>
                    CSV
                </a>
                <a href="?gh=<?php echo $gh_id; ?>&sensor=<?php echo $sensor; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>&group_by=<?php echo $group_by; ?>&sort_by=<?php echo $sort_by; ?>&export=excel"
                   class="btn btn-secondary" data-no-loader="true" download style="font-size:0.82rem;padding:8px 14px;gap:6px;display:flex;align-items:center;" title="Download format Excel">
                    <i data-lucide="file-text" style="width:15px;height:15px;color:#0284c7;"></i>
                    Excel
                </a>
                <button onclick="window.print()" class="btn btn-secondary" style="font-size:0.82rem;padding:8px 14px;gap:6px;display:flex;align-items:center;">
                    <i data-lucide="printer" style="width:15px;height:15px;color:#64748b;"></i>
                    Cetak
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Error Alert -->
        <?php if ($dbError): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#dc2626;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:0.9rem;">
            <i data-lucide="alert-circle" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;"></i>
            <strong>Error Database:</strong> <?php echo htmlspecialchars($dbError); ?>
        </div>
        <?php endif; ?>

        <!-- Filter Panel -->
        <div class="glass-card" style="padding:20px;margin-bottom:20px;">
            <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;align-items:end;">
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
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:9px;">
                        <i data-lucide="search" style="width:16px;height:16px;"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>

        <?php if (!$tablesExist): ?>
        <div class="glass-card empty-state">
            <i data-lucide="database" class="empty-state-icon"></i>
            <h3 class="empty-state-title">Tabel Belum Siap</h3>
            <p class="empty-state-desc">Jalankan migrasi database terlebih dahulu.</p>
        </div>
        <?php elseif (empty($greenhouses)): ?>
        <div class="glass-card" style="text-align:center;padding:50px;">
            <i data-lucide="home" style="width:48px;height:48px;color:var(--accent-color);margin-bottom:12px;"></i>
            <p>Anda belum memiliki greenhouse. <a href="greenhouse.php" style="color:var(--accent-color);">Tambah sekarang →</a></p>
        </div>
        <?php elseif (!$hasData): ?>
        <div class="glass-card empty-state">
            <i data-lucide="bar-chart-2" class="empty-state-icon"></i>
            <h3 class="empty-state-title">Belum Ada Data Riwayat Sensor</h3>
            <p class="empty-state-desc">Data riwayat sensor akan tercatat secara otomatis saat perangkat ESP32 / Firebase mengirimkan telemetri riil.</p>
        </div>
        <?php else: ?>
        <!-- Summary Stats Cards -->
        <?php if ($sensor === 'all'): ?>
        <?php
            $temps = array_column($historyData, 'temp_val');
            $hums  = array_column($historyData, 'hum_val');
            $airs  = array_column($historyData, 'air_val');
            $uvs   = array_column($historyData, 'uv_val');

            $avg_temp = !empty($temps) ? round(array_sum($temps) / count($temps), 1) : 0;
            $avg_hum  = !empty($hums)  ? round(array_sum($hums) / count($hums), 1)  : 0;
            $avg_air  = !empty($airs)  ? round(array_sum($airs) / count($airs), 1)  : 0;
            $avg_uv   = !empty($uvs)   ? round(array_sum($uvs) / count($uvs), 1)    : 0;
        ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px;">
            <div class="glass-card" style="padding:18px;text-align:center;">
                <p style="color:var(--text-secondary);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Suhu Udara Avg</p>
                <p style="font-size:1.8rem;font-family:var(--font-heading);font-weight:800;color:#ef4444;"><?php echo $avg_temp; ?> °C</p>
            </div>
            <div class="glass-card" style="padding:18px;text-align:center;">
                <p style="color:var(--text-secondary);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Kelembapan Avg</p>
                <p style="font-size:1.8rem;font-family:var(--font-heading);font-weight:800;color:#3b82f6;"><?php echo $avg_hum; ?> %</p>
            </div>
            <div class="glass-card" style="padding:18px;text-align:center;">
                <p style="color:var(--text-secondary);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Kualitas Udara Avg</p>
                <p style="font-size:1.8rem;font-family:var(--font-heading);font-weight:800;color:#a855f7;"><?php echo $avg_air; ?> AQI</p>
            </div>
            <div class="glass-card" style="padding:18px;text-align:center;">
                <p style="color:var(--text-secondary);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Indeks UV Avg</p>
                <p style="font-size:1.8rem;font-family:var(--font-heading);font-weight:800;color:#f59e0b;"><?php echo $avg_uv; ?></p>
            </div>
        </div>
        <?php else: ?>
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
        <?php endif; ?>

        <!-- Chart -->
        <div class="glass-card" style="padding:24px;margin-bottom:20px;">
            <div id="history-chart"></div>
        </div>

        <!-- Data Table Card with High-Visibility Interactive Sort Chips -->
        <div class="glass-card" style="padding:0;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:rgba(248,250,252,0.6);">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h4 style="font-family:var(--font-heading);font-size:1.05rem;font-weight:800;color:#1E293B;margin:0;">Data Riwayat</h4>
                    <span style="background:rgba(13,93,49,0.12);color:#0D5D31;border:1px solid rgba(13,93,49,0.25);padding:5px 12px;border-radius:99px;font-size:0.78rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 6px rgba(13,93,49,0.1);">
                        <i data-lucide="arrow-up-down" style="width:14px;height:14px;"></i> Klik Header Kolom Untuk Mengurutkan Data
                    </span>
                </div>
                <span style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);"><?php echo count($historyData); ?> total baris</span>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:rgba(241,245,249,0.75);border-bottom:2px solid rgba(226,232,240,0.9);">
                            <th style="text-align:left;padding:12px 18px;">
                                <a href="<?php echo getSortLink('period'); ?>" class="sort-header-chip <?php echo isSortActive('period')?'active':''; ?>">
                                    <i data-lucide="calendar" style="width:14px;height:14px;"></i> Periode <?php echo getSortBadgeIcon('period'); ?>
                                </a>
                            </th>
                            <?php if ($sensor === 'all'): ?>
                            <th style="text-align:right;padding:12px 14px;">
                                <a href="<?php echo getSortLink('temp'); ?>" class="sort-header-chip <?php echo isSortActive('temp')?'active':''; ?>" style="<?php echo !isSortActive('temp')?'color:#dc2626;':''; ?>">
                                    <i data-lucide="thermometer" style="width:14px;height:14px;"></i> Suhu (°C) <?php echo getSortBadgeIcon('temp'); ?>
                                </a>
                            </th>
                            <th style="text-align:right;padding:12px 14px;">
                                <a href="<?php echo getSortLink('hum'); ?>" class="sort-header-chip <?php echo isSortActive('hum')?'active':''; ?>" style="<?php echo !isSortActive('hum')?'color:#2563eb;':''; ?>">
                                    <i data-lucide="droplets" style="width:14px;height:14px;"></i> Kelembapan (%) <?php echo getSortBadgeIcon('hum'); ?>
                                </a>
                            </th>
                            <th style="text-align:right;padding:12px 14px;">
                                <a href="<?php echo getSortLink('air'); ?>" class="sort-header-chip <?php echo isSortActive('air')?'active':''; ?>" style="<?php echo !isSortActive('air')?'color:#9333ea;':''; ?>">
                                    <i data-lucide="wind" style="width:14px;height:14px;"></i> AQI <?php echo getSortBadgeIcon('air'); ?>
                                </a>
                            </th>
                            <th style="text-align:right;padding:12px 14px;">
                                <a href="<?php echo getSortLink('uv'); ?>" class="sort-header-chip <?php echo isSortActive('uv')?'active':''; ?>" style="<?php echo !isSortActive('uv')?'color:#d97706;':''; ?>">
                                    <i data-lucide="sun" style="width:14px;height:14px;"></i> UV Index <?php echo getSortBadgeIcon('uv'); ?>
                                </a>
                            </th>
                            <?php else: ?>
                            <th style="text-align:right;padding:12px 18px;">
                                <a href="<?php echo getSortLink('val'); ?>" class="sort-header-chip <?php echo isSortActive('val')?'active':''; ?>" style="<?php echo !isSortActive('val')?'color:'.$sensorColors[$sensor].';':''; ?>">
                                    <i data-lucide="activity" style="width:14px;height:14px;"></i> <?php echo htmlspecialchars($sensorLabels[$sensor]); ?> <?php echo getSortBadgeIcon('val'); ?>
                                </a>
                            </th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyData as $row): ?>
                        <tr style="border-top:1px solid rgba(0,0,0,0.05);transition:background 0.15s ease;" onmouseover="this.style.background='rgba(13,93,49,0.03)'" onmouseout="this.style.background='transparent'">
                            <td style="padding:11px 20px;font-size:0.85rem;color:var(--text-primary);font-weight:500;"><?php echo htmlspecialchars($row['period']); ?></td>
                            <?php if ($sensor === 'all'): ?>
                            <td style="padding:11px 16px;font-size:0.85rem;text-align:right;font-weight:700;color:#ef4444;"><?php echo $row['temp_val']; ?></td>
                            <td style="padding:11px 16px;font-size:0.85rem;text-align:right;font-weight:700;color:#3b82f6;"><?php echo $row['hum_val']; ?></td>
                            <td style="padding:11px 16px;font-size:0.85rem;text-align:right;font-weight:700;color:#a855f7;"><?php echo $row['air_val']; ?></td>
                            <td style="padding:11px 16px;font-size:0.85rem;text-align:right;font-weight:700;color:#f59e0b;"><?php echo $row['uv_val']; ?></td>
                            <?php else: ?>
                            <td style="padding:11px 20px;font-size:0.85rem;text-align:right;font-weight:700;color:<?php echo $sensorColors[$sensor]; ?>;"><?php echo $row['value']; ?></td>
                            <?php endif; ?>
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

/* UI/UX Pro Max Interactive Sort Chips */
.sort-header-chip {
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 0.84rem;
    font-weight: 700;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    background: rgba(255, 255, 255, 0.9);
    border: 1.5px solid rgba(203, 213, 225, 0.9);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.sort-header-chip:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 93, 49, 0.15);
    border-color: #0D5D31;
}

.sort-header-chip.active {
    background: #0D5D31 !important;
    color: #FFFFFF !important;
    border-color: #0D5D31 !important;
    box-shadow: 0 4px 14px rgba(13, 93, 49, 0.35) !important;
}

.sort-header-chip.active svg {
    color: #FFFFFF !important;
}

.sort-badge-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 800;
    background: rgba(0, 0, 0, 0.07);
}

.sort-badge-icon.muted {
    opacity: 0.45;
}

.sort-header-chip.active .sort-badge-icon {
    background: rgba(255, 255, 255, 0.25);
    color: #FFFFFF;
}

@media print {
    .sidebar, .sidebar-footer, .header-controls, .glass-card form, .btn, nav { display: none !important; }
    body { background: #fff !important; color: #000 !important; font-family: sans-serif; }
    .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    .dashboard-wrapper { display: block !important; }
    .glass-card { box-shadow: none !important; border: 1px solid #ddd !important; background: #fff !important; margin-bottom: 16px !important; page-break-inside: avoid; }
    #history-chart { width: 100% !important; }
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; page-break-after: auto; }
}
</style>

<?php if ($hasData): ?>
<script>
const labels = <?php echo json_encode(array_column($chartData, 'period')); ?>;

<?php if ($sensor === 'all'): ?>
const chartSeries = [
    { name: 'Suhu Udara (°C)', data: <?php echo json_encode(array_column($chartData, 'temp_val')); ?> },
    { name: 'Kelembapan (%)', data: <?php echo json_encode(array_column($chartData, 'hum_val')); ?> },
    { name: 'Kualitas Udara (AQI)', data: <?php echo json_encode(array_column($chartData, 'air_val')); ?> },
    { name: 'Indeks UV', data: <?php echo json_encode(array_column($chartData, 'uv_val')); ?> }
];
const chartColors = ['#ef4444', '#3b82f6', '#a855f7', '#f59e0b'];
<?php else: ?>
const chartSeries = [{ name: '<?php echo addslashes($sensorLabels[$sensor]); ?>', data: <?php echo json_encode(array_column($chartData, 'value')); ?> }];
const chartColors = ['<?php echo $sensorColors[$sensor]; ?>'];
<?php endif; ?>

const chart = new ApexCharts(document.getElementById('history-chart'), {
    chart: { type: 'area', height: 340, toolbar: { show: true }, zoom: { enabled: true }, animations: { enabled: true, speed: 600 } },
    series: chartSeries,
    xaxis: { categories: labels, labels: { rotate: -35, style: { fontSize: '11px' } }, tickAmount: 12 },
    yaxis: { labels: { formatter: (v) => v?.toFixed(1) } },
    colors: chartColors,
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } },
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
