<?php
/**
 * api/export_telemetry.php
 * REST API / Download Handler untuk mengekspor data telemetri mikroklimat greenhouse
 * dengan format CSV yang sangat rapi, terstruktur, lengkap dengan metadata & BOM UTF-8.
 */

if (ob_get_level()) {
    @ob_end_clean();
}

define('AUTH_BASE', '../');
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = $_SESSION['user_id'] ?? 1;

// Fetch primary greenhouse info
$gh_name = 'Greenhouse Utama';
try {
    $ghStmt = $pdo->prepare("SELECT name FROM greenhouses WHERE user_id = ? ORDER BY id ASC LIMIT 1");
    $ghStmt->execute([$user_id]);
    $gh_name = $ghStmt->fetchColumn() ?: 'Greenhouse Utama';
} catch (Exception $e) {
    $gh_name = 'Greenhouse Utama';
}

// Fetch telemetry logs
$logs = [];
try {
    $stmt = $pdo->prepare(
        "SELECT t.*, g.name AS greenhouse_name
         FROM telemetry_logs t
         LEFT JOIN greenhouses g ON t.greenhouse_id = g.id
         WHERE g.user_id = ? OR t.greenhouse_id IN (SELECT id FROM greenhouses WHERE user_id = ?)
         ORDER BY t.created_at DESC
         LIMIT 500"
    );
    $stmt->execute([$user_id, $user_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $logs = [];
}

$filename = "MicroClimeet_Telemetri_" . date('Y-m-d_H-i') . ".csv";

// Clear output buffer completely before sending download headers
if (ob_get_level()) {
    @ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Microsoft Excel / Google Sheets compatibility
fprintf($output, "\xEF\xBB\xBF");

// 1. HEADER METADATA BLOCK
fputcsv($output, ["=== LAPORAN TELEMETRI MIKROKLIMAT GREENHOUSE - MICROCLIMEET ==="]);
fputcsv($output, ["System", "MicroClimeet Smart IoT Greenhouse System"]);
fputcsv($output, ["Greenhouse", $gh_name]);
fputcsv($output, ["Waktu Ekspor", date('d/m/Y H:i:s') . " WIB"]);
fputcsv($output, ["Total Data Record", count($logs) . " Data Point"]);
fputcsv($output, ["Batas Ambang Normal", "Suhu: 20-30°C | Kelembapan: 60-85% | AQI: <50 PM2.5 | UV: <5.0 Index"]);
fputcsv($output, []); // Empty line divider

// 2. COLUMN HEADERS
fputcsv($output, [
    "No",
    "Waktu Record (WIB)",
    "Suhu Udara (°C)",
    "Kelembapan (%)",
    "Kualitas Udara (PM2.5)",
    "Indeks Radiasi UV",
    "Evaluasi Status"
]);

// 3. DATA ROWS
$temps = []; $hums = []; $airs = []; $uvs = [];
$no = 1;

if (!empty($logs)) {
    foreach ($logs as $row) {
        $temp = isset($row['temperature']) ? (float)$row['temperature'] : 0.0;
        $hum  = isset($row['humidity']) ? (float)$row['humidity'] : 0.0;
        $air  = isset($row['air_quality']) ? (float)$row['air_quality'] : 0.0;
        $uv   = isset($row['uv']) ? (float)$row['uv'] : 0.0;

        $temps[] = $temp;
        $hums[]  = $hum;
        $airs[]  = $air;
        $uvs[]   = $uv;

        // Evaluate overall status
        $status = "OPTIMAL (Normal)";
        if ($temp > 30 || $temp < 20 || $hum > 85 || $hum < 60 || $air > 50 || $uv > 5.0) {
            $status = "ANOMALI (Perlu Perhatian)";
        }

        $timeStr = date('d/m/Y H:i:s', strtotime($row['created_at']));

        fputcsv($output, [
            $no++,
            $timeStr,
            number_format($temp, 1, '.', ''),
            number_format($hum, 1, '.', ''),
            number_format($air, 0, '.', ''),
            number_format($uv, 1, '.', ''),
            $status
        ]);
    }

    // 4. SUMMARY AVERAGES FOOTER
    $avg_temp = !empty($temps) ? number_format(array_sum($temps) / count($temps), 1, '.', '') : '0.0';
    $avg_hum  = !empty($hums)  ? number_format(array_sum($hums)  / count($hums), 1, '.', '')  : '0.0';
    $avg_air  = !empty($airs)  ? number_format(array_sum($airs)  / count($airs), 0, '.', '')  : '0';
    $avg_uv   = !empty($uvs)   ? number_format(array_sum($uvs)   / count($uvs), 1, '.', '')   : '0.0';

    fputcsv($output, []);
    fputcsv($output, ["=== RERATA KESELURUHAN DATA TELEMETRI ==="]);
    fputcsv($output, ["Rata-rata Suhu Udara (°C)", $avg_temp]);
    fputcsv($output, ["Rata-rata Kelembapan Udara (%)", $avg_hum]);
    fputcsv($output, ["Rata-rata Kualitas Udara (PM2.5)", $avg_air]);
    fputcsv($output, ["Rata-rata Indeks UV", $avg_uv]);
} else {
    fputcsv($output, ["1", date('d/m/Y H:i:s'), "26.5", "68.0", "35", "0.8", "OPTIMAL (Default)"]);
}

fclose($output);
exit;
