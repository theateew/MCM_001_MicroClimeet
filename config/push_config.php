<?php
/**
 * MicroClimeet — Notification Configuration
 * Web Push (VAPID) + WhatsApp via Fonnte API
 *
 * SETUP INSTRUCTIONS:
 * 1. Generate VAPID keys: https://web-push-codelab.glitch.me/
 * 2. Daftar Fonnte: https://fonnte.com → dapatkan API token
 * 3. Isi nilai di bawah ini
 */

// ============================================================
// WEB PUSH (VAPID) CONFIGURATION
// ============================================================
define('VAPID_PUBLIC_KEY',  'YOUR_VAPID_PUBLIC_KEY_HERE');
define('VAPID_PRIVATE_KEY', 'YOUR_VAPID_PRIVATE_KEY_HERE');
define('VAPID_SUBJECT',     'mailto:admin@microclimeet.com');

// ============================================================
// WHATSAPP via FONNTE API
// ============================================================
define('FONNTE_TOKEN',      'YOUR_FONNTE_TOKEN_HERE');
define('FONNTE_ENDPOINT',   'https://api.fonnte.com/send');
// Nomor pengirim (nomor WA yang terdaftar di Fonnte)
define('FONNTE_SENDER',     'YOUR_WA_NUMBER'); // Format: 628xxxxxx

// ============================================================
// APP SETTINGS
// ============================================================
define('APP_URL', 'http://localhost:8000');
define('APP_NAME', 'MicroClimeet');
