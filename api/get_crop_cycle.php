<?php
/**
 * api/get_crop_cycle.php
 * REST API untuk mengambil data siklus tanam, fase pertumbuhan, target nutrisi (EC & pH),
 * serta rekomendasi langkah solusi per-fase secara real-time.
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$gh_id   = isset($_GET['gh_id']) ? (int)$_GET['gh_id'] : 0;
$user_id = $_SESSION['user_id'] ?? 0;

if ($gh_id <= 0 && $user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM greenhouses WHERE user_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$user_id]);
        $gh_id = (int)($stmt->fetchColumn() ?: 0);
    } catch (PDOException $e) {}
}

if ($gh_id <= 0) {
    try {
        $gh_id = (int)($pdo->query("SELECT id FROM greenhouses ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);
    } catch (PDOException $e) { $gh_id = 1; }
}

// Fetch greenhouse & crop cycle
$crop_data = null;
try {
    $c_stmt = $pdo->prepare("SELECT * FROM crop_cycles WHERE greenhouse_id = ?");
    $c_stmt->execute([$gh_id]);
    $crop_data = $c_stmt->fetch();
} catch (PDOException $e) {}

if (!$crop_data) {
    // Default fallback if not seeded
    $pDate = date('Y-m-d', strtotime('-20 days'));
    $hDate = date('Y-m-d', strtotime('+55 days'));
    $crop_data = [
        'greenhouse_id' => $gh_id,
        'crop_name' => 'Melon Premium',
        'planting_date' => $pDate,
        'estimated_harvest_date' => $hDate,
        'target_ec' => 2.0,
        'target_ph' => 6.5,
        'notes' => ''
    ];
}

// Calculate days and progress
$today      = new DateTime();
$plantDate  = new DateTime($crop_data['planting_date']);
$harvestDate= !empty($crop_data['estimated_harvest_date']) ? new DateTime($crop_data['estimated_harvest_date']) : (clone $plantDate)->modify('+75 days');

$diffPlant   = $plantDate->diff($today);
$daysActive  = $today >= $plantDate ? (int)$diffPlant->days + 1 : 0;

$diffTotal   = $plantDate->diff($harvestDate);
$totalDays   = max(1, (int)$diffTotal->days);

$progressPct = max(0, min(100, round(($daysActive / $totalDays) * 100)));
$daysLeft    = max(0, $totalDays - $daysActive);

// Calculate proportional phase thresholds based on total cycle duration
$p1_end = max(5, round($totalDays * 0.20)); // ~20% for Penyemaian
$p2_end = max(15, round($totalDays * 0.45)); // ~45% for Vegetatif
$p3_end = max(30, round($totalDays * 0.80)); // ~80% for Generatif

$phaseName  = 'Fase Penyemaian';
$phaseIcon  = 'sprout';
$phaseColor = '#3b82f6'; // Blue
$ecTarget   = '1.0 – 1.2 mS/cm';
$phTarget   = '5.8 – 6.2';
$solution   = 'Jaga kelembapan media tanam (75–85%), pasang shading net 50%, siram nutrisi tipis EC 1.0 mS/cm 2x sehari.';
$nextPhase  = "Fase Vegetatif (Hari " . ($p1_end + 1) . ")";

if ($daysActive > $p1_end && $daysActive <= $p2_end) {
    $phaseName  = 'Fase Vegetatif';
    $phaseIcon  = 'leaf';
    $phaseColor = '#22c55e'; // Green
    $ecTarget   = '1.5 – 1.8 mS/cm';
    $phTarget   = '6.0 – 6.5';
    $solution   = 'Pemangkasan tunas air (wiwil), berikan larutan nutrisi N tinggi, pastikan sirkulasi udara blower & ventilasi lancar.';
    $nextPhase  = "Fase Pembungaan & Pembuahan (Hari " . ($p2_end + 1) . ")";
} elseif ($daysActive > $p2_end && $daysActive <= $p3_end) {
    $phaseName  = 'Fase Pembungaan & Pembuahan';
    $phaseIcon  = 'flower-2';
    $phaseColor = '#f59e0b'; // Amber
    $ecTarget   = '2.0 – 2.4 mS/cm';
    $phTarget   = '6.2 – 6.8';
    $solution   = 'Lakukan polinasi bunga betina pagi jam 07.00-09.00, seleksi 1-2 buah terbaik per tanaman, dan tingkatkan rasio pupuk K & P.';
    $nextPhase  = "Fase Pematangan & Panen (Hari " . ($p3_end + 1) . ")";
} elseif ($daysActive > $p3_end) {
    $phaseName  = 'Fase Pematangan & Panen';
    $phaseIcon  = 'award';
    $phaseColor = '#8b5cf6'; // Purple
    $ecTarget   = '2.2 – 2.5 mS/cm';
    $phTarget   = '6.2 – 6.8';
    $solution   = 'Kurangi debit irigasi 5 hari sebelum panen untuk menaikkan tingkat kemanisan (Brix > 14%), pasang jala penopang buah agar tidak patah.';
    $nextPhase  = 'Siap Panen!';
}

function formatIndonesianDate($dateStr) {
    if (empty($dateStr)) return '-';
    $days = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $ts = strtotime($dateStr);
    if (!$ts) return $dateStr;

    $dayName   = $days[date('l', $ts)] ?? date('l', $ts);
    $dayNum    = date('j', $ts);
    $monthName = $months[(int)date('n', $ts)] ?? date('F', $ts);
    $year      = date('Y', $ts);

    return "$dayName, $dayNum $monthName $year";
}

echo json_encode([
    'success'       => true,
    'greenhouse_id' => $gh_id,
    'crop_name'     => $crop_data['crop_name'],
    'planting_date' => $crop_data['planting_date'],
    'estimated_harvest_date' => $harvestDate->format('Y-m-d'),
    'formatted_planting_date' => formatIndonesianDate($crop_data['planting_date']),
    'formatted_harvest_date'  => formatIndonesianDate($harvestDate->format('Y-m-d')),
    'days_active'   => $daysActive,
    'total_days'    => $totalDays,
    'days_left'     => $daysLeft,
    'progress_pct'  => $progressPct,
    'phase_name'    => $phaseName,
    'phase_icon'    => $phaseIcon,
    'phase_color'   => $phaseColor,
    'ec_target'     => $ecTarget,
    'ph_target'     => $phTarget,
    'solution'      => $solution,
    'next_phase'    => $nextPhase
]);
