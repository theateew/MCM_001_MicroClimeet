<?php
/**
 * includes/whatsapp_widget.php — Floating WhatsApp Direct Message Widget & Report Generator
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$widget_user_id   = $_SESSION['user_id'] ?? null;
$widget_user_role = $_SESSION['role'] ?? 'petani';

// Hide WhatsApp widget completely for Admin users or Admin dashboard pages
if (!$widget_user_id || !isset($pdo) || (isset($is_admin_area) && $is_admin_area) || $widget_user_role === 'admin') {
    return;
}

// Fetch user info
$u_stmt = $pdo->prepare("SELECT name, phone FROM users WHERE id = ?");
$u_stmt->execute([$widget_user_id]);
$w_user = $u_stmt->fetch();
$w_user_name = $w_user ? $w_user['name'] : 'Petani';

// Fetch primary greenhouse
$g_stmt = $pdo->prepare("SELECT name FROM greenhouses WHERE user_id = ? ORDER BY id ASC LIMIT 1");
$g_stmt->execute([$widget_user_id]);
$w_gh_name = $g_stmt->fetchColumn() ?: 'Greenhouse Utama';

// Fetch real unread alerts in last 24 hours (Only unread alerts trigger WA badge & template)
$a_stmt = $pdo->prepare(
    "SELECT a.*, g.name AS greenhouse_name
     FROM alerts a
     LEFT JOIN greenhouses g ON a.greenhouse_id = g.id
     WHERE (a.user_id = ? OR a.greenhouse_id IN (SELECT id FROM greenhouses WHERE user_id = ?))
       AND a.is_read = 0
       AND a.triggered_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
     ORDER BY a.triggered_at DESC"
);
$a_stmt->execute([$widget_user_id, $widget_user_id]);
$alerts24 = $a_stmt->fetchAll();
$alert24_count = count($alerts24);

// Support Phone Number Target (Admin MicroClimeet)
$support_phone = "6283829634058"; // Format 083829634058 untuk WhatsApp REST API

// Construct WhatsApp Message Text
$msg_lines = [];
$msg_lines[] = "🌱 *LAPORAN ADUAN PERINGATAN BELUM DIBACA (24 JAM TERAKHIR)*";
$msg_lines[] = "-------------------------------------------------";
$msg_lines[] = "📍 *Greenhouse:* " . $w_gh_name . " (" . $w_user_name . ")";
$msg_lines[] = "📅 *Waktu Lapor:* " . date('d M Y, H:i') . " WIB";
$msg_lines[] = "";

$recom_list = [];

if ($alert24_count > 0) {
    $msg_lines[] = "🚨 *Daftar Anomali Sensor Terdeteksi (" . $alert24_count . " Peringatan):*";
    $i = 1;
    foreach ($alerts24 as $alt) {
        $st = $alt['sensor_type'];
        $val = $alt['sensor_value'];
        $timeStr = date('H:i', strtotime($alt['triggered_at']));
        $msg_lines[] = $i . ". [" . $timeStr . " WIB] " . $alt['message'];
        $i++;

        if ($st === 'temperature' && !in_array('temp', $recom_list)) {
            $recom_list['temp'] = "• *Suhu Panas:* Aktifkan kipas ventilasi & pasang shading net 50% untuk mendinginkan ruang.";
        }
        if ($st === 'humidity' && !in_array('hum', $recom_list)) {
            $recom_list['hum'] = "• *Kelembapan Ekstrem:* Buka ventilasi atas / jalankan blower aerasi udara.";
        }
        if ($st === 'air_quality' && !in_array('air', $recom_list)) {
            $recom_list['air'] = "• *Kualitas Udara Buruk:* Cek sirkulasi filter exhaust fan greenhouse.";
        }
        if ($st === 'uv' && !in_array('uv', $recom_list)) {
            $recom_list['uv'] = "• *Radiasi UV Tinggi:* Gunakan plastik UV terpal pelindung untuk menghindari stres tanaman.";
        }
    }
} else {
    $msg_lines[] = "✅ *Status Sensor:* Semua parameter mikroklimat (Suhu, Kelembapan, AQI, UV) dalam kondisi *OPTIMAL & AMAN*. Tidak ada anomali dalam 24 jam terakhir.";
    $recom_list[] = "• Pertahankan jadwal penyiraman & ventilasi mikroklimat ideal.";
}

$msg_lines[] = "";
$msg_lines[] = "💡 *Rekomendasi & Saran Penanganan Teknis:*";
foreach ($recom_list as $rec) {
    $msg_lines[] = $rec;
}

$msg_lines[] = "";
$msg_lines[] = "Halo Tim Admin MicroClimeet, saya membutuhkan arahan teknis mengenai laporan di atas. Mohon bantuan lebih lanjut. Terima kasih!";

$full_wa_message = implode("\n", $msg_lines);
$encoded_wa_message = rawurlencode($full_wa_message);
?>

<!-- Floating WhatsApp Button (Fixed Bottom-Right Corner) -->
<div id="wa-floating-container" style="position:fixed;bottom:28px;right:28px;z-index:9999;display:flex;align-items:center;gap:10px;">
    <!-- Tooltip Label -->
    <div class="wa-tooltip-box" onclick="openWaModal()">
        <span>Lapor Aduan (WA)</span>
        <?php if ($alert24_count > 0): ?>
        <span style="background:#ef4444;color:#fff;border-radius:10px;padding:1px 6px;font-size:0.7rem;font-weight:800;"><?php echo $alert24_count; ?> Alert</span>
        <?php endif; ?>
    </div>

    <!-- FAB Button -->
    <button onclick="openWaModal()" class="wa-fab-btn" title="Kirim Laporan Aduan via WhatsApp ke Admin">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
        </svg>
        <?php if ($alert24_count > 0): ?>
        <span class="wa-fab-badge"><?php echo $alert24_count; ?></span>
        <?php endif; ?>
    </button>
</div>

<!-- Modal Preview Aduan WhatsApp (Glassmorphism & Backdrop Blur) -->
<div id="wa-preview-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:10000;background:rgba(15, 23, 42, 0.6);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);align-items:center;justify-content:center;padding:16px;animation:fadeIn 0.25s ease-out;">
    <div style="background:rgba(255, 255, 255, 0.96);border:1.5px solid rgba(255, 255, 255, 0.6);border-radius:22px;max-width:600px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0, 0, 0, 0.3);padding:24px 28px;position:relative;animation:slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        <!-- Modal Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(0,0,0,0.08);padding-bottom:14px;margin-bottom:18px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;border-radius:14px;background:linear-gradient(135deg, #25D366 0%, #128C7E 100%);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(37,211,102,0.35);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="font-family:var(--font-heading);font-weight:800;font-size:1.1rem;color:#0D5D31;margin:0;">Layanan Aduan Support WhatsApp</h3>
                    <p style="font-size:0.78rem;color:var(--text-secondary);margin:0;">Pilih template aduan atau tulis pesan manual untuk Tim Admin</p>
                </div>
            </div>
            <button onclick="closeWaModal()" style="background:rgba(0,0,0,0.05);border:none;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-secondary);transition:all 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.1)';this.style.color='#ef4444';" onmouseout="this.style.background='rgba(0,0,0,0.05)';this.style.color='var(--text-secondary)';">
                <i data-lucide="x" style="width:18px;height:18px;"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div style="display:flex;flex-direction:column;gap:14px;">
            <!-- Target Phone Readonly Display -->
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;background:rgba(13,93,49,0.06);padding:10px 14px;border-radius:12px;border:1px solid rgba(13,93,49,0.18);">
                <span style="font-size:0.83rem;font-weight:600;color:var(--text-secondary);">No. WhatsApp Tujuan Admin:</span>
                <span style="font-size:0.88rem;font-weight:800;color:#0D5D31;background:#ffffff;padding:5px 14px;border-radius:8px;border:1px solid rgba(13,93,49,0.2);display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 4px rgba(0,0,0,0.04);">
                    <i data-lucide="shield-check" style="width:15px;height:15px;color:#0D5D31;"></i>
                    083829634058 (Admin Support)
                </span>
            </div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

            <!-- Custom Grayscale Dropdown (Bootstrap Icons) -->
            <div style="position:relative;">
                <label style="display:block;font-size:0.8rem;font-weight:700;color:#0D5D31;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                    <i class="bi bi-list-ul" style="font-size:1rem;color:#475569;"></i>
                    Pilih Kategori / Template Aduan:
                </label>
                
                <button type="button" id="wa-dropdown-btn" onclick="toggleWaDropdown(event)" class="form-input" style="background:#fff;font-weight:700;color:#1e293b;border:1.5px solid rgba(13,93,49,0.25);border-radius:12px;padding:10px 14px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;width:100%;text-align:left;">
                    <span id="wa-selected-label" style="display:flex;align-items:center;gap:10px;">
                        <i class="bi bi-bell" style="color:#475569;font-size:1.1rem;"></i>
                        <span>Aduan Peringatan Sensor (Otomatik 24 Jam)</span>
                    </span>
                    <i class="bi bi-chevron-down" style="color:#64748b;font-size:0.85rem;"></i>
                </button>

                <div id="wa-dropdown-menu" style="display:none;position:absolute;top:100%;left:0;right:0;margin-top:6px;background:#ffffff;border:1.5px solid rgba(0,0,0,0.12);border-radius:14px;box-shadow:0 12px 28px rgba(0,0,0,0.15);z-index:10010;overflow:hidden;padding:6px 0;">
                    <div class="wa-dropdown-item active" onclick="selectWaOption(event, 'alert_auto', 'bi-bell', 'Aduan Peringatan Sensor (Otomatik 24 Jam)')" style="padding:10px 16px;display:flex;align-items:center;gap:12px;font-size:0.85rem;font-weight:600;color:#1e293b;cursor:pointer;transition:background 0.15s;">
                        <i class="bi bi-bell" style="color:#475569;font-size:1.1rem;flex-shrink:0;"></i>
                        <span>Aduan Peringatan Sensor (Otomatik 24 Jam)</span>
                    </div>
                    <div class="wa-dropdown-item" onclick="selectWaOption(event, 'hardware', 'bi-cpu', 'Kendala Perangkat Hardware / Sensor Off')" style="padding:10px 16px;display:flex;align-items:center;gap:12px;font-size:0.85rem;font-weight:600;color:#1e293b;cursor:pointer;transition:background 0.15s;">
                        <i class="bi bi-cpu" style="color:#475569;font-size:1.1rem;flex-shrink:0;"></i>
                        <span>Kendala Perangkat Hardware / Sensor Off</span>
                    </div>
                    <div class="wa-dropdown-item" onclick="selectWaOption(event, 'firebase', 'bi-wifi', 'Kendala Sync Data Realtime / Firebase')" style="padding:10px 16px;display:flex;align-items:center;gap:12px;font-size:0.85rem;font-weight:600;color:#1e293b;cursor:pointer;transition:background 0.15s;">
                        <i class="bi bi-wifi" style="color:#475569;font-size:1.1rem;flex-shrink:0;"></i>
                        <span>Kendala Sync Data Realtime / Firebase</span>
                    </div>
                    <div class="wa-dropdown-item" onclick="selectWaOption(event, 'threshold', 'bi-sliders', 'Kendala Threshold, Peringatan & Kalibrasi')" style="padding:10px 16px;display:flex;align-items:center;gap:12px;font-size:0.85rem;font-weight:600;color:#1e293b;cursor:pointer;transition:background 0.15s;">
                        <i class="bi bi-sliders" style="color:#475569;font-size:1.1rem;flex-shrink:0;"></i>
                        <span>Kendala Threshold, Peringatan & Kalibrasi</span>
                    </div>
                    <div class="wa-dropdown-item" onclick="selectWaOption(event, 'crop', 'bi-flower1', 'Konsultasi Siklus Tanam & Nutrisi (EC/pH)')" style="padding:10px 16px;display:flex;align-items:center;gap:12px;font-size:0.85rem;font-weight:600;color:#1e293b;cursor:pointer;transition:background 0.15s;">
                        <i class="bi bi-flower1" style="color:#475569;font-size:1.1rem;flex-shrink:0;"></i>
                        <span>Konsultasi Siklus Tanam & Nutrisi (EC/pH)</span>
                    </div>
                    <div class="wa-dropdown-item" onclick="selectWaOption(event, 'custom', 'bi-pencil-square', 'Tulis Pesan Aduan Manual (Kustom)')" style="padding:10px 16px;display:flex;align-items:center;gap:12px;font-size:0.85rem;font-weight:600;color:#1e293b;cursor:pointer;transition:background 0.15s;">
                        <i class="bi bi-pencil-square" style="color:#475569;font-size:1.1rem;flex-shrink:0;"></i>
                        <span>Tulis Pesan Aduan Manual (Kustom)</span>
                    </div>
                </div>
            </div>

            <!-- Custom Editable Textarea (Hidden by default) -->
            <div id="wa-custom-textarea-wrap" style="display:none;background:#f8faf8;padding:12px;border-radius:12px;border:1.5px solid rgba(13,93,49,0.2);">
                <label style="display:block;font-size:0.78rem;font-weight:700;color:#0D5D31;margin-bottom:6px;">
                    Tulis Pesan Aduan Anda Secara Bebas:
                </label>
                <textarea id="wa-custom-textarea" class="form-input" rows="4" placeholder="Ketik rincian kendala atau keluhan Anda di sini..." style="background:#fff;font-size:0.86rem;line-height:1.45;font-weight:500;" oninput="updateWaCustomPreview(this.value)"></textarea>
            </div>

            <!-- Message Preview Container -->
            <div style="background:#e5ddd5;border-radius:14px;padding:16px;border:1px solid rgba(0,0,0,0.1);background-image:radial-gradient(rgba(0,0,0,0.04) 1px, transparent 0);background-size:12px 12px;">
                <div style="font-size:0.72rem;font-weight:700;color:#54656f;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Pratinjau Pesan WhatsApp:</div>
                <div style="background:#ffffff;border-radius:12px;padding:14px;font-size:0.83rem;color:#111b21;line-height:1.5;box-shadow:0 1px 3px rgba(0,0,0,0.15);white-space:pre-wrap;word-break:break-word;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;" id="wa-msg-text-preview"><?php echo htmlspecialchars($full_wa_message); ?></div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="margin-top:20px;padding-top:14px;border-top:1px solid rgba(0,0,0,0.08);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <span style="font-size:0.76rem;color:var(--text-secondary);" id="wa-footer-note">*Pesan dibuat otomatis dengan data riil 24 jam terakhir.</span>
            <div style="display:flex;gap:8px;">
                <button onclick="closeWaModal()" class="btn btn-secondary" style="font-size:0.83rem;padding:9px 18px;">Batal</button>
                <a id="btn-send-wa-direct" href="https://api.whatsapp.com/send?phone=<?php echo $support_phone; ?>&text=<?php echo $encoded_wa_message; ?>" target="_blank" onclick="closeWaModal()" class="btn" style="background:linear-gradient(135deg, #25D366 0%, #128C7E 100%);color:#fff;font-size:0.85rem;padding:9px 22px;border-radius:10px;font-weight:700;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(37,211,102,0.35);text-decoration:none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                    Kirim via WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Floating WhatsApp Button Styling */
.wa-fab-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: #ffffff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(37, 211, 102, 0.45);
    position: relative;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    animation: waPulse 2.5s infinite;
}

.wa-fab-btn:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 12px 30px rgba(37, 211, 102, 0.6);
}

.wa-fab-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #ef4444;
    color: #ffffff;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    font-size: 0.72rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.wa-tooltip-box {
    background: rgba(15, 23, 42, 0.88);
    color: #ffffff;
    padding: 8px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    backdrop-filter: blur(8px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid rgba(255,255,255,0.15);
}

.wa-tooltip-box:hover {
    background: rgba(13, 93, 49, 0.95);
    transform: translateX(-4px);
}

.wa-dropdown-item:hover {
    background: #f1f5f9 !important;
    color: #0D5D31 !important;
}
.wa-dropdown-item.active {
    background: rgba(13, 93, 49, 0.08) !important;
    color: #0D5D31 !important;
    font-weight: 700 !important;
}
</style>

<script>
const supportPhone = <?php echo json_encode($support_phone); ?>;
const ghName = <?php echo json_encode($w_gh_name); ?>;
const userName = <?php echo json_encode($w_user_name); ?>;
const autoAlertMsg = <?php echo json_encode($full_wa_message); ?>;
const currentDateStr = <?php echo json_encode(date('d M Y, H:i') . ' WIB'); ?>;

const waTemplates = {
    alert_auto: autoAlertMsg,
    hardware: `🌱 *LAPORAN KENDALA HARDWARE & PERANGKAT IOT*\n-------------------------------------------------\n📍 *Greenhouse:* ${ghName} (${userName})\n📅 *Waktu Lapor:* ${currentDateStr}\n\n🚨 *Rincian Kendala Physical Hardware:*\n• Perangkat ESP32 Offline / Sinyal terputus\n• Sensor BME680 / PMS5003 / UV tidak terbaca (Nilai 0 / Off)\n• kendala Adaptor / Catu Daya Mikrokontroler\n\nHalo Tim Admin Support MicroClimeet, perangkat IoT di lokasi greenhouse saya mengalami kendala fisik/hardware. Mohon petunjuk teknis perbaikan. Terima kasih!`,
    firebase: `🌱 *LAPORAN GANGGUAN KONEKSI & TELEMETRI FIREBASE*\n-------------------------------------------------\n📍 *Greenhouse:* ${ghName} (${userName})\n📅 *Waktu Lapor:* ${currentDateStr}\n\n🌐 *Rincian Gangguan Realtime DB:*\n• Card Grafik & Telemetri tidak ter-update di Dashboard\n• Status Perangkat tertulis 'Offline' padahal ESP32 menyala\n• Terjadi keterlambatan penerimaan data sensor\n\nHalo Tim Admin MicroClimeet, data telemetri di dashboard saya tidak ter-update secara realtime. Mohon dilakukan pengecekan node Firebase. Terima kasih!`,
    threshold: `🌱 *LAPORAN KENDALA THRESHOLD & KALIBRASI SENSOR*\n-------------------------------------------------\n📍 *Greenhouse:* ${ghName} (${userName})\n📅 *Waktu Lapor:* ${currentDateStr}\n\n⚙️ *Rincian Pengaturan:*\n• Notifikasi Peringatan tidak muncul saat nilai sensor melampaui batas\n• Penyesuaian ambang batas (Threshold) khusus komoditas\n• Permintaan reset / penyesuaian offset kalibrasi sensor\n\nHalo Admin MicroClimeet, mohon bantuan penyesuaian ambang batas (threshold) atau kalibrasi ulang sensor greenhouse saya. Terima kasih!`,
    crop: `🌱 *KONSULTASI SIKLUS TANAM & FORMULASI NUTRISI*\n-------------------------------------------------\n📍 *Greenhouse:* ${ghName} (${userName})\n📅 *Waktu Lapor:* ${currentDateStr}\n\n🌱 *Topik Konsultasi:*\n• Penyesuaian fase vegetatif / generatif tanaman\n• Formulasi nutrisi EC & pH ideal untuk komoditas budidaya\n• Gejala stres mikroklimat pada tanaman\n\nHalo Tim Ahli / Admin MicroClimeet, saya ingin berkonsultasi mengenai rekomendasi nutrisi dan penanganan tanaman di greenhouse saya. Terima kasih!`,
    custom: `🌱 *PESAN ADUAN KUSTOM PETANI*\n-------------------------------------------------\n📍 *Greenhouse:* ${ghName} (${userName})\n📅 *Waktu Lapor:* ${currentDateStr}\n\n💬 *Pesan Aduan:* \n`
};

function changeWaTemplate(key) {
    const customWrap = document.getElementById('wa-custom-textarea-wrap');
    const customTextarea = document.getElementById('wa-custom-textarea');
    const footerNote = document.getElementById('wa-footer-note');
    let textToSend = '';

    if (key === 'custom') {
        if (customWrap) customWrap.style.display = 'block';
        if (footerNote) footerNote.innerText = '*Pesan kustom ditulis manual oleh petani.';
        if (customTextarea) {
            if (!customTextarea.value.trim()) {
                customTextarea.value = 'Saya ingin melaporkan kendala berikut...';
            }
            textToSend = waTemplates.custom + customTextarea.value.trim();
        } else {
            textToSend = waTemplates.custom;
        }
    } else {
        if (customWrap) customWrap.style.display = 'none';
        if (footerNote) {
            footerNote.innerText = (key === 'alert_auto')
                ? '*Pesan dibuat otomatis dengan data riil 24 jam terakhir.'
                : '*Template pesan aduan resmi MicroClimeet Support.';
        }
        textToSend = waTemplates[key] || autoAlertMsg;
    }

    updateWaPreviewAndLink(textToSend);
}

function updateWaCustomPreview(customText) {
    const textToSend = waTemplates.custom + (customText.trim() || '...');
    updateWaPreviewAndLink(textToSend);
}

function updateWaPreviewAndLink(msgText) {
    const previewEl = document.getElementById('wa-msg-text-preview');
    const sendBtn = document.getElementById('btn-send-wa-direct');
    if (previewEl) previewEl.innerText = msgText;
    if (sendBtn) {
        sendBtn.href = 'https://api.whatsapp.com/send?phone=' + supportPhone + '&text=' + encodeURIComponent(msgText);
    }
}

function openWaModal() {
    const modal = document.getElementById('wa-preview-modal');
    if (modal) {
        modal.style.display = 'flex';
        if (window.lucide) lucide.createIcons();
    }
}

function closeWaModal() {
    const modal = document.getElementById('wa-preview-modal');
    if (modal) modal.style.display = 'none';
}

function toggleWaDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('wa-dropdown-menu');
    if (menu) {
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }
}

function selectWaOption(e, key, iconClass, labelText) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('wa-dropdown-menu');
    const labelSpan = document.getElementById('wa-selected-label');

    if (menu) menu.style.display = 'none';
    if (labelSpan) {
        labelSpan.innerHTML = `<i class="bi ${iconClass}" style="color:#475569;font-size:1.1rem;flex-shrink:0;"></i> <span>${labelText}</span>`;
    }

    document.querySelectorAll('.wa-dropdown-item').forEach(el => el.classList.remove('active'));
    if (e && e.currentTarget) e.currentTarget.classList.add('active');

    changeWaTemplate(key);
}

// Close modal & dropdown when clicking backdrop outside content
window.addEventListener('click', (e) => {
    const modal = document.getElementById('wa-preview-modal');
    if (e.target === modal) closeWaModal();

    const btn = document.getElementById('wa-dropdown-btn');
    const menu = document.getElementById('wa-dropdown-menu');
    if (btn && menu && !btn.contains(e.target) && !menu.contains(e.target)) {
        menu.style.display = 'none';
    }
});
</script>
