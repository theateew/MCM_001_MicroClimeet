<?php
/**
 * MicroClimeet — Notification Helper
 * Fungsi: alert count, WhatsApp via Fonnte, Web Push, cek threshold
 */

require_once __DIR__ . '/../config/push_config.php';

/**
 * Ambil jumlah alert belum dibaca milik user
 */
function getUnreadAlertCount(PDO $pdo, int $user_id): int {
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM alerts WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Kirim pesan WhatsApp via Fonnte API
 * @param string $phone  Nomor HP penerima (format: 628xxxxxx)
 * @param string $message Isi pesan
 * @return bool
 */
function sendWhatsApp(string $phone, string $message): bool {
    if (FONNTE_TOKEN === 'YOUR_FONNTE_TOKEN_HERE') return false; // Skip if not configured

    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($phone, '0')) {
        $phone = '62' . substr($phone, 1);
    }

    $data = [
        'target'  => $phone,
        'message' => $message,
        'sender'  => FONNTE_SENDER,
    ];

    $ch = curl_init(FONNTE_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . FONNTE_TOKEN,
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

/**
 * Kirim Web Push Notification ke semua subscription milik user
 * Menggunakan implementasi minimal VAPID tanpa library eksternal
 * (menggunakan openssl yang sudah built-in di PHP)
 */
function sendWebPush(PDO $pdo, int $user_id, string $title, string $body, string $url = '/'): void {
    if (VAPID_PUBLIC_KEY === 'YOUR_VAPID_PUBLIC_KEY_HERE') return;

    try {
        $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $subscriptions = $stmt->fetchAll();
    } catch (PDOException $e) {
        return;
    }

    $payload = json_encode([
        'title' => $title,
        'body'  => $body,
        'url'   => APP_URL . $url,
        'icon'  => APP_URL . '/assets/images/brand/mini_logo.png',
        'badge' => APP_URL . '/assets/images/brand/mini_logo.png',
    ]);

    foreach ($subscriptions as $sub) {
        sendVapidPush($sub['endpoint'], $sub['p256dh'], $sub['auth_key'], $payload);
    }
}

/**
 * Low-level VAPID push (minimal implementation tanpa composer)
 * Menggunakan curl + header Authorization: vapid
 */
function sendVapidPush(string $endpoint, string $p256dh, string $authKey, string $payload): void {
    // Build JWT for VAPID
    $header = base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $origin = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
    $claims = base64url_encode(json_encode([
        'aud' => $origin,
        'exp' => time() + 43200,
        'sub' => VAPID_SUBJECT,
    ]));

    $unsignedToken = $header . '.' . $claims;
    $privateKey = openssl_pkey_get_private(vapid_pem_from_b64(VAPID_PRIVATE_KEY));
    openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $jwt = $unsignedToken . '.' . base64url_encode($signature);

    $vapidAuthHeader = 'vapid t=' . $jwt . ', k=' . VAPID_PUBLIC_KEY;

    // Encrypt payload using ece (simplified — use raw for modern browsers)
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . $vapidAuthHeader,
            'Content-Type: application/json',
            'Content-Encoding: aes128gcm',
            'TTL: 86400',
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function vapid_pem_from_b64(string $b64): string {
    return "-----BEGIN EC PRIVATE KEY-----\n" .
           chunk_split(base64_encode(base64_decode(strtr($b64, '-_', '+/'))), 64, "\n") .
           "-----END EC PRIVATE KEY-----";
}

/**
 * Cek data sensor terhadap threshold, INSERT alert jika dilanggar,
 * kirim WhatsApp + Push Notification.
 *
 * @param PDO   $pdo
 * @param int   $greenhouse_id
 * @param int   $user_id
 * @param array $data          ['temperature'=>..., 'humidity'=>..., 'air_quality'=>..., 'uv'=>...]
 * @param array $thresholds    ['temperature'=>['min'=>...,'max'=>...], ...]
 * @param array $user          ['phone'=>..., 'name'=>...]
 * @return int  Jumlah alert baru yang ditrigger
 */
function checkAndTriggerAlerts(
    PDO $pdo,
    int $greenhouse_id,
    int $user_id,
    array $data,
    array $thresholds,
    array $user
): int {
    $sensor_labels = [
        'temperature' => 'Suhu Udara',
        'humidity'    => 'Kelembapan',
        'air_quality' => 'Kualitas Udara',
        'uv'          => 'Indeks UV',
    ];
    $sensor_units = [
        'temperature' => '°C',
        'humidity'    => '%',
        'air_quality' => 'AQI',
        'uv'          => 'UV',
    ];

    $alertCount = 0;

    foreach ($thresholds as $sensor => $range) {
        if (!isset($data[$sensor])) continue;

        $value = (float) $data[$sensor];
        $min   = (float) $range['min'];
        $max   = (float) $range['max'];
        $label = $sensor_labels[$sensor] ?? $sensor;
        $unit  = $sensor_units[$sensor] ?? '';
        $type  = null;
        $msg   = '';

        if ($value < $min) {
            $type = 'below_min';
            $msg  = "{$label} terlalu rendah: {$value}{$unit} (min: {$min}{$unit})";
        } elseif ($value > $max) {
            $type = 'above_max';
            $msg  = "{$label} terlalu tinggi: {$value}{$unit} (maks: {$max}{$unit})";
        }

        if ($type === null) continue;

        // Cek apakah alert serupa sudah ada dalam 10 menit terakhir (hindari spam)
        $checkStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM alerts 
             WHERE greenhouse_id = ? AND sensor_type = ? AND threshold_type = ?
               AND triggered_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
        );
        $checkStmt->execute([$greenhouse_id, $sensor, $type]);
        if ($checkStmt->fetchColumn() > 0) continue; // Skip duplikat

        // Insert alert
        $ins = $pdo->prepare(
            "INSERT INTO alerts (greenhouse_id, user_id, sensor_type, sensor_value, threshold_type, message)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $ins->execute([$greenhouse_id, $user_id, $sensor, $value, $type, $msg]);
        $alertCount++;

        // Ambil data greenhouse untuk pesan notifikasi
        $ghStmt = $pdo->prepare("SELECT name FROM greenhouses WHERE id = ?");
        $ghStmt->execute([$greenhouse_id]);
        $ghName = $ghStmt->fetchColumn() ?: "Greenhouse #$greenhouse_id";

        $fullMsg = "[MicroClimeet] {$msg}\nGreenhouse: {$ghName}\nCek dashboard: " . APP_URL . "/petani/alerts.php";

        // Kirim WhatsApp
        if (!empty($user['phone'])) {
            sendWhatsApp($user['phone'], $fullMsg);
        }

        // Kirim Web Push
        sendWebPush($pdo, $user_id, '⚠️ ' . APP_NAME . ' Alert', $msg, '/petani/alerts.php');
    }

    return $alertCount;
}
