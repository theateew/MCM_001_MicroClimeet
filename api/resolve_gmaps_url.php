<?php
/**
 * api/resolve_gmaps_url.php
 * Resolves short Google Maps URLs (e.g. maps.app.goo.gl or goo.gl/maps) to extract Latitude & Longitude.
 */
header('Content-Type: application/json');

$url = trim($_GET['url'] ?? '');

if (empty($url)) {
    echo json_encode(['success' => false, 'error' => 'URL tidak boleh kosong.']);
    exit;
}

// Check if input is raw coordinates string first
if (preg_match('/^\s*(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)\s*$/', $url, $m)) {
    echo json_encode([
        'success'   => true,
        'latitude'  => number_format((float)$m[1], 6, '.', ''),
        'longitude' => number_format((float)$m[2], 6, '.', ''),
        'final_url' => $url
    ]);
    exit;
}

// Prepend https:// if user pasted maps.app.goo.gl/... without scheme
if (!preg_match('/^https?:\/\//i', $url)) {
    $url = 'https://' . $url;
}

try {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $html = curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    $lat = null;
    $lng = null;

    // Pattern 1: @lat,lng (e.g. /@ -7.559081,110.774424,17z)
    if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $finalUrl, $m)) {
        $lat = (float)$m[1];
        $lng = (float)$m[2];
    } 
    // Pattern 2: !3dlat!4dlng
    elseif (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $finalUrl, $m)) {
        $lat = (float)$m[1];
        $lng = (float)$m[2];
    }
    // Pattern 3: q=lat,lng or query=lat,lng or ll=lat,lng
    elseif (preg_match('/[?&](?:q|query|ll)=(-?\d+\.\d+),(-?\d+\.\d+)/i', $finalUrl, $m)) {
        $lat = (float)$m[1];
        $lng = (float)$m[2];
    }
    // Pattern 4: Search inside HTML page content if JS redirect embedded
    elseif (!empty($html) && preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $html, $m)) {
        $lat = (float)$m[1];
        $lng = (float)$m[2];
    }
    elseif (!empty($html) && preg_match('/\[\s*(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)\s*\]/', $html, $m)) {
        $lat = (float)$m[1];
        $lng = (float)$m[2];
    }

    if ($lat !== null && $lng !== null) {
        echo json_encode([
            'success'   => true,
            'latitude'  => number_format($lat, 6, '.', ''),
            'longitude' => number_format($lng, 6, '.', ''),
            'final_url' => $finalUrl
        ]);
    } else {
        echo json_encode([
            'success'   => false,
            'error'     => 'Gagal mendeteksi koordinat dari link ini. Pastikan link menunjuk ke titik lokasi Google Maps.',
            'final_url' => $finalUrl
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
}
