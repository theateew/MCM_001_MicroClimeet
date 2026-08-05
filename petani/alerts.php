<?php
/**
 * petani/alerts.php — Daftar Peringatan Sensor Real-Time + Toggle Slide Notifikasi (Aktif/Nonaktif) + Modal Panduan
 */
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/notifications.php';
check_auth('petani');

$page_title  = 'Peringatan Sensor';
$custom_css  = ['dashboard.css'];
$base_url    = '../';
$active_menu = 'alerts';
$user_id     = (int)$_SESSION['user_id'];

// Mark all as read jika diminta
if (isset($_GET['mark_all_read'])) {
    $pdo->prepare("UPDATE alerts SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    header("Location: alerts.php");
    exit;
}

// Mark single as read
if (isset($_GET['mark_read'])) {
    $pdo->prepare("UPDATE alerts SET is_read = 1 WHERE id = ? AND user_id = ?")
        ->execute([(int)$_GET['mark_read'], $user_id]);
}

// Delete single alert
if (isset($_GET['delete_alert'])) {
    $pdo->prepare("DELETE FROM alerts WHERE id = ? AND user_id = ?")
        ->execute([(int)$_GET['delete_alert'], $user_id]);
    header("Location: alerts.php?filter=" . urlencode($_GET['filter'] ?? 'all'));
    exit;
}

// Delete all alerts
if (isset($_GET['delete_all'])) {
    $pdo->prepare("DELETE FROM alerts WHERE user_id = ?")->execute([$user_id]);
    header("Location: alerts.php");
    exit;
}

// Filter
$filter    = in_array($_GET['filter'] ?? '', ['unread', 'all']) ? ($_GET['filter'] ?? 'all') : 'all';
$gh_filter = (int)($_GET['gh'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;

// Get greenhouses for filter dropdown
$greenhouses = $pdo->prepare("SELECT id, name FROM greenhouses WHERE user_id = ? ORDER BY name");
$greenhouses->execute([$user_id]);
$greenhouses = $greenhouses->fetchAll();

// Build query (Match by user_id OR greenhouse_id owned by user)
$conditions = ['(a.user_id = ? OR a.greenhouse_id IN (SELECT id FROM greenhouses WHERE user_id = ?))'];
$params     = [$user_id, $user_id];
if ($filter === 'unread') { $conditions[] = 'a.is_read = 0'; }
if ($gh_filter > 0) { $conditions[] = 'a.greenhouse_id = ?'; $params[] = $gh_filter; }
$where = implode(' AND ', $conditions);

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM alerts a WHERE $where");
$countStmt->execute($params);
$total    = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// Fetch alerts
$alertStmt = $pdo->prepare(
    "SELECT a.*, g.name AS greenhouse_name
     FROM alerts a
     LEFT JOIN greenhouses g ON a.greenhouse_id = g.id
     WHERE $where
     ORDER BY a.triggered_at DESC
     LIMIT $perPage OFFSET $offset"
);
$alertStmt->execute($params);
$alerts = $alertStmt->fetchAll();

$unread_count = getUnreadAlertCount($pdo, $user_id);

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2><i data-lucide="bell" style="color:#0D5D31;"></i> Peringatan Sensor</h2>
                <p>Monitor dan kelola peringatan ambang batas sensor greenhouse Anda.</p>
            </div>
            <div class="header-controls" style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php if ($unread_count > 0): ?>
                <a href="alerts.php?mark_all_read=1" class="btn btn-secondary" style="font-size:0.83rem;padding:8px 14px;">
                    <i data-lucide="check-check" style="width:15px;height:15px;"></i>
                    Tandai Semua Dibaca (<?php echo $unread_count; ?>)
                </a>
                <?php endif; ?>

                <?php if ($total > 0): ?>
                <a href="alerts.php?delete_all=1" onclick="return confirm('Apakah Anda yakin ingin menghapus SELURUH riwayat peringatan?');" class="btn btn-secondary" style="font-size:0.83rem;padding:8px 14px;color:#dc2626;border-color:rgba(239,68,68,0.3);">
                    <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                    Hapus Semua Riwayat
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Cards Grid (Panduan Peringatan Kiri + Web Push & Toggle Switch Kanan) -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:20px;">
            <!-- Card 1 (Kiri): Panduan Peringatan Sensor -->
            <div class="glass-card" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:linear-gradient(135deg, rgba(13,93,49,0.08) 0%, rgba(16,185,129,0.08) 100%);border:1.5px solid rgba(13,93,49,0.22);">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:#0D5D31;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 10px rgba(13,93,49,0.25);">
                        <i data-lucide="book-open" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <h4 style="font-family:var(--font-heading);font-weight:700;font-size:0.92rem;color:#0D5D31;margin-bottom:2px;">Panduan Peringatan Sensor</h4>
                        <p style="font-size:0.8rem;color:var(--text-secondary);margin:0;">Panduan frekuensi 3x DB &amp; 5x Web Push anti-spam.</p>
                    </div>
                </div>
                <button onclick="openGuideModal()" class="btn" style="background:#0D5D31;color:#fff;font-size:0.82rem;padding:9px 18px;gap:6px;display:flex;align-items:center;border-radius:10px;font-weight:600;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(13,93,49,0.25);transition:all 0.2s;">
                    <i data-lucide="info" style="width:15px;height:15px;"></i>
                    Buka Panduan
                </button>
            </div>

            <!-- Card 2 (Kanan): Pengujian Notifikasi Web Push Browser & Toggle Slide Switch -->
            <div class="glass-card" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:linear-gradient(135deg, rgba(59,130,246,0.08) 0%, rgba(99,102,241,0.08) 100%);border:1.5px solid rgba(59,130,246,0.22);">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 10px rgba(37,99,235,0.25);">
                        <i data-lucide="bell-ring" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <h4 style="font-family:var(--font-heading);font-weight:700;font-size:0.92rem;color:#1e40af;margin-bottom:2px;">Notifikasi Web Push Browser</h4>
                        <p style="font-size:0.8rem;color:var(--text-secondary);margin:0;">Aktifkan / nonaktifkan notifikasi pop-up browser.</p>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                    <!-- Slide Switch Toggle (Aktif/Nonaktif) -->
                    <div style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.75);padding:6px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.08);box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                        <span style="font-size:0.82rem;font-weight:600;color:var(--text-secondary);">Notifikasi:</span>
                        <label class="toggle-switch" title="Aktifkan / Nonaktifkan Notifikasi Web Push">
                            <input type="checkbox" id="notification-toggle-switch" onchange="toggleNotificationState(this.checked)">
                            <span class="slider round"></span>
                        </label>
                        <span id="toggle-status-label" style="font-size:0.83rem;font-weight:700;color:#0D5D31;min-width:55px;">Aktif</span>
                    </div>

                    <button id="btn-test-push" onclick="testWebPushNotification()" class="btn" style="background:#2563eb;color:#fff;font-size:0.82rem;padding:9px 18px;gap:6px;display:flex;align-items:center;border-radius:10px;font-weight:600;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(37,99,235,0.25);transition:all 0.2s;">
                        <i data-lucide="send" style="width:15px;height:15px;"></i>
                        Uji Coba
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="glass-card" style="padding:14px 20px;margin-bottom:14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span style="font-size:0.83rem;font-weight:600;color:var(--text-secondary);">Filter:</span>
            <a href="?filter=all&gh=<?php echo $gh_filter; ?>" class="filter-chip <?php echo $filter==='all'?'active':''; ?>">
                Semua
            </a>
            <a href="?filter=unread&gh=<?php echo $gh_filter; ?>" class="filter-chip <?php echo $filter==='unread'?'active':''; ?>">
                Belum Dibaca
                <?php if ($unread_count > 0): ?>
                <span class="chip-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <?php if (!empty($greenhouses)): ?>
            <select onchange="location.href='?filter=<?php echo $filter; ?>&gh=' + this.value" class="form-input" style="width:auto;font-size:0.83rem;padding:5px 10px;">
                <option value="0">Semua Greenhouse</option>
                <?php foreach ($greenhouses as $g): ?>
                <option value="<?php echo $g['id']; ?>" <?php echo $gh_filter==$g['id']?'selected':''; ?>>
                    <?php echo htmlspecialchars($g['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <span style="margin-left:auto;font-size:0.82rem;color:var(--text-secondary);"><?php echo $total; ?> peringatan</span>
        </div>

        <?php if (!empty($alerts)): ?>
        <!-- Custom Alert WhatsApp Action Bar -->
        <div class="glass-card" style="padding:12px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:linear-gradient(135deg, rgba(37,211,102,0.06) 0%, rgba(18,140,126,0.06) 100%);border:1.5px solid rgba(37,211,102,0.22);">
            <div style="display:flex;align-items:center;gap:10px;">
                <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.83rem;font-weight:700;cursor:pointer;color:var(--text-primary);">
                    <input type="checkbox" id="select-all-alerts" onchange="toggleSelectAllAlerts(this.checked)" style="width:18px;height:18px;cursor:pointer;accent-color:#0D5D31;">
                    Pilih Semua Peringatan
                </label>
                <span style="font-size:0.78rem;color:var(--text-secondary);">(Terpilih: <strong id="selected-alert-count" style="color:#0D5D31;">0</strong>)</span>
            </div>
            <button onclick="sendSelectedAlertsToWA()" class="btn" style="background:linear-gradient(135deg, #25D366 0%, #128C7E 100%);color:#fff;font-size:0.82rem;padding:8px 18px;border-radius:10px;font-weight:700;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(37,211,102,0.35);">
                <i data-lucide="message-square" style="width:15px;height:15px;"></i>
                Kirim Peringatan Terpilih ke WA Admin
            </button>
        </div>
        <?php endif; ?>

        <!-- Alerts List (Pure Real-Time Data) -->
        <?php if (empty($alerts)): ?>
        <div class="glass-card empty-state" style="padding:40px 20px;text-align:center;">
            <div style="width:60px;height:60px;border-radius:50%;background:rgba(13,93,49,0.08);color:#0D5D31;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                <i data-lucide="shield-check" style="width:32px;height:32px;"></i>
            </div>
            <h3 class="empty-state-title" style="font-family:var(--font-heading);font-weight:700;font-size:1.1rem;color:var(--text-primary);margin-bottom:6px;">Tidak Ada Peringatan Sensor</h3>
            <p class="empty-state-desc" style="font-size:0.88rem;color:var(--text-secondary);margin:0;max-width:500px;margin:0 auto;">
                Semua parameter mikroklimat (Suhu, Kelembapan, AQI, UV) saat ini dalam kondisi optimal &amp; aman. Data peringatan real-time akan otomatis tercatat di sini saat terdeteksi anomali. 🌿
            </p>
        </div>
        <?php else: ?>
        <div class="alerts-list" style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($alerts as $a): ?>
            <?php
                $isUnread = !$a['is_read'];
                $sensorIcons = ['temperature'=>'thermometer','humidity'=>'droplets','air_quality'=>'wind','uv'=>'sun'];
                $sensorLabels = ['temperature'=>'Suhu Udara','humidity'=>'Kelembapan','air_quality'=>'Kualitas Udara','uv'=>'Indeks UV'];
                $icon = $sensorIcons[$a['sensor_type']] ?? 'alert-circle';
                $label = $sensorLabels[$a['sensor_type']] ?? $a['sensor_type'];
                $isAbove = $a['threshold_type'] === 'above_max';
                $cardBg = $isUnread ? ($isAbove ? 'rgba(239,68,68,0.06)' : 'rgba(251,191,36,0.06)') : 'rgba(0,0,0,0.02)';
                $accentColor = $isAbove ? '#ef4444' : '#f59e0b';
            ?>
            <div style="background:<?php echo $cardBg; ?>;border:1px solid <?php echo $isUnread ? ($isAbove?'rgba(239,68,68,0.2)':'rgba(251,191,36,0.2)') : 'rgba(0,0,0,0.07)'; ?>;border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:14px;<?php echo $isUnread?'':'opacity:0.75;'; ?>">
                <!-- Custom Selection Checkbox -->
                <label style="display:flex;align-items:center;padding:2px;cursor:pointer;" title="Pilih peringatan ini untuk dikirim ke WA Admin">
                    <input type="checkbox" class="alert-checkbox"
                           data-id="<?php echo $a['id']; ?>"
                           data-time="<?php echo date('d/m/Y H:i', strtotime($a['triggered_at'])); ?>"
                           data-gh="<?php echo htmlspecialchars(addslashes($a['greenhouse_name'] ?? 'Greenhouse')); ?>"
                           data-msg="<?php echo htmlspecialchars(addslashes($a['message'])); ?>"
                           data-type="<?php echo htmlspecialchars($a['sensor_type']); ?>"
                           onchange="updateSelectedCount()"
                           style="width:18px;height:18px;cursor:pointer;accent-color:#0D5D31;">
                </label>

                <div style="width:40px;height:40px;border-radius:50%;background:<?php echo $isUnread?($isAbove?'rgba(239,68,68,0.12)':'rgba(251,191,36,0.12)'):'rgba(0,0,0,0.06)'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="<?php echo $icon; ?>" style="width:18px;height:18px;color:<?php echo $accentColor; ?>;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;flex-wrap:wrap;">
                        <span style="font-weight:700;font-size:0.88rem;"><?php echo htmlspecialchars($a['message']); ?></span>
                        <?php if ($isUnread): ?>
                        <span style="background:<?php echo $accentColor; ?>;color:#fff;border-radius:10px;padding:1px 7px;font-size:0.7rem;font-weight:700;">BARU</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;font-size:0.78rem;color:var(--text-secondary);">
                        <span style="display:inline-flex;align-items:center;gap:4px;"><i data-lucide="map-pin" style="width:13px;height:13px;"></i> <?php echo htmlspecialchars($a['greenhouse_name'] ?? '-'); ?></span>
                        <span style="display:inline-flex;align-items:center;gap:4px;"><i data-lucide="clock" style="width:13px;height:13px;"></i> <?php echo date('d M Y, H:i', strtotime($a['triggered_at'])); ?></span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                    <?php if ($isUnread): ?>
                    <a href="?mark_read=<?php echo $a['id']; ?>&filter=<?php echo $filter; ?>&gh=<?php echo $gh_filter; ?>"
                       title="Tandai sudah dibaca"
                       style="color:var(--accent-color);text-decoration:none;padding:4px;"
                       onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                        <i data-lucide="check-circle-2" style="width:18px;height:18px;"></i>
                    </a>
                    <?php endif; ?>

                    <!-- Delete Single Alert Button -->
                    <a href="?delete_alert=<?php echo $a['id']; ?>&filter=<?php echo $filter; ?>&gh=<?php echo $gh_filter; ?>"
                       onclick="return confirm('Hapus peringatan ini?');"
                       title="Hapus Peringatan Ini"
                       style="color:#ef4444;text-decoration:none;padding:4px;"
                       onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                        <i data-lucide="trash" style="width:17px;height:17px;"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div style="display:flex;justify-content:center;gap:6px;margin-top:24px;">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?filter=<?php echo $filter; ?>&gh=<?php echo $gh_filter; ?>&page=<?php echo $p; ?>"
               style="padding:7px 13px;border-radius:var(--radius-pill);border:1.5px solid <?php echo $p==$page?'var(--accent-color)':'rgba(0,0,0,0.1)'; ?>;background:<?php echo $p==$page?'var(--accent-color)':'transparent'; ?>;color:<?php echo $p==$page?'#fff':'var(--text-secondary)'; ?>;font-size:0.83rem;font-weight:600;text-decoration:none;">
                <?php echo $p; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<!-- Glassmorphism Modal Backdrop for Alert Guide -->
<div id="guide-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999;background:rgba(15, 23, 42, 0.55);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);align-items:center;justify-content:center;padding:16px;animation:fadeIn 0.25s ease-out;">
    <div style="background:rgba(255, 255, 255, 0.96);border:1.5px solid rgba(255, 255, 255, 0.6);border-radius:22px;max-width:620px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0, 0, 0, 0.25);padding:24px 28px;position:relative;animation:slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        <!-- Modal Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(0,0,0,0.08);padding-bottom:14px;margin-bottom:18px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;border-radius:10px;background:#0D5D31;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 10px rgba(13,93,49,0.25);">
                    <i data-lucide="shield-check" style="width:20px;height:20px;"></i>
                </div>
                <div>
                    <h3 style="font-family:var(--font-heading);font-weight:800;font-size:1.1rem;color:#0D5D31;margin:0;">Panduan &amp; Logika Peringatan Sensor</h3>
                    <p style="font-size:0.78rem;color:var(--text-secondary);margin:0;">Sistem Deteksi Frekuensi Real-Time &amp; Anti-Spam MicroClimeet</p>
                </div>
            </div>
            <button onclick="closeGuideModal()" style="background:rgba(0,0,0,0.05);border:none;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-secondary);transition:all 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.1)';this.style.color='#ef4444';" onmouseout="this.style.background='rgba(0,0,0,0.05)';this.style.color='var(--text-secondary)';">
                <i data-lucide="x" style="width:18px;height:18px;"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div style="display:flex;flex-direction:column;gap:16px;font-size:0.85rem;color:var(--text-primary);line-height:1.6;">
            <!-- Section 1 -->
            <div style="background:linear-gradient(135deg, rgba(13,93,49,0.05) 0%, rgba(16,185,129,0.05) 100%);border:1px solid rgba(13,93,49,0.18);border-radius:14px;padding:14px 16px;">
                <div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#0D5D31;font-size:0.92rem;margin-bottom:6px;">
                    <i data-lucide="database" style="width:16px;height:16px;"></i>
                    <span>1. Pencatatan Database &amp; Dashboard (3x Pengecekan)</span>
                </div>
                <p style="margin:0 0 8px 0;color:var(--text-secondary);">
                    Peringatan sensor <strong>hanya akan dicatat ke database MySQL</strong> dan ditampilkan di widget <em>Peringatan Terkini</em> setelah parameter bernilai anomali/berbahaya selama <strong>3 kali pengecekan berturut-turut</strong>.
                </p>
                <div style="background:rgba(255,255,255,0.85);border-radius:8px;padding:8px 12px;border:1px solid rgba(0,0,0,0.06);font-size:0.8rem;">
                    <strong>Contoh Kasus (UV Index):</strong><br>
                    • Pengecekan 1 = <code style="background:rgba(239,68,68,0.1);color:#dc2626;padding:1px 5px;border-radius:4px;">6.0</code>, Pengecekan 2 = <code style="background:rgba(239,68,68,0.1);color:#dc2626;padding:1px 5px;border-radius:4px;">5.0</code>, Pengecekan 3 = <code style="background:rgba(239,68,68,0.1);color:#dc2626;padding:1px 5px;border-radius:4px;">7.0</code> (3x berturut-turut!)<br>
                    • Rerata otomatis dihitung: (6 + 5 + 7) / 3 = <strong>6.0 UV Index</strong>.<br>
                    • Catatan riwayat disimpan dengan nilai rerata 6.0 untuk menyaring lonjakan data palsu (noise).
                </div>
            </div>

            <!-- Section 2 -->
            <div style="background:linear-gradient(135deg, rgba(245,158,11,0.05) 0%, rgba(217,119,6,0.05) 100%);border:1px solid rgba(245,158,11,0.2);border-radius:14px;padding:14px 16px;">
                <div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#b45309;font-size:0.92rem;margin-bottom:6px;">
                    <i data-lucide="bell" style="width:16px;height:16px;"></i>
                    <span>2. Notifikasi Web Push Pop-up OS (5x Pengecekan)</span>
                </div>
                <p style="margin:0 0 8px 0;color:var(--text-secondary);">
                    Notifikasi Web Push ke layar browser/HP Android <strong>hanya dipicu setelah 5 kali pengecekan berturut-turut</strong> bernilai anomali.
                </p>
                <div style="background:rgba(255,255,255,0.85);border-radius:8px;padding:8px 12px;border:1px solid rgba(0,0,0,0.06);font-size:0.8rem;">
                    <strong>Manfaat Anti-Spam:</strong> Mencegah layar pengguna dibombardir notifikasi mengganggu saat sensor hanya mengalami gangguan sinyal atau lonjakan sementara singkat.
                </div>
            </div>

            <!-- Section 3 -->
            <div style="background:linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(99,102,241,0.05) 100%);border:1px solid rgba(59,130,246,0.2);border-radius:14px;padding:14px 16px;">
                <div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#1e40af;font-size:0.92rem;margin-bottom:6px;">
                    <i data-lucide="globe" style="width:16px;height:16px;"></i>
                    <span>3. Notifikasi Background &amp; Status Perangkat Off</span>
                </div>
                <p style="margin:0;color:var(--text-secondary);">
                    Dengan <em>Service Worker PWA (sw.js)</em>, peringatan darurat dan notifikasi status <strong>Perangkat Offline (Mati/Putus)</strong> tetap berjalan dan dikirim ke perangkat HP/komputer Anda meskipun tab website ditutup atau HP dalam kondisi terkunci.
                </p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="margin-top:20px;padding-top:14px;border-top:1px solid rgba(0,0,0,0.08);display:flex;justify-content:flex-end;">
            <button onclick="closeGuideModal()" class="btn" style="background:#0D5D31;color:#fff;font-size:0.85rem;padding:9px 22px;border-radius:10px;font-weight:600;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(13,93,49,0.25);">
                Saya Mengerti
            </button>
        </div>
    </div>
</div>

<style>
.filter-chip {
    padding: 6px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600;
    border: 1.5px solid rgba(0,0,0,0.1); color: var(--text-secondary);
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
    transition: all 0.2s;
}
.filter-chip.active { background: var(--accent-color); color: #fff; border-color: transparent; }
.filter-chip:hover { border-color: var(--accent-color); color: var(--accent-dark); }
.chip-badge { background: #ef4444; color: #fff; border-radius: 10px; padding: 1px 6px; font-size: 0.7rem; }

/* Slide Switch Toggle CSS */
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: #cbd5e1;
  transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

input:checked + .slider {
  background-color: #0D5D31;
}

input:focus + .slider {
  box-shadow: 0 0 1px #0D5D31;
}

input:checked + .slider:before {
  transform: translateX(20px);
}

.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>

<script>
function openGuideModal() {
    const modal = document.getElementById('guide-modal');
    if (modal) {
        modal.style.display = 'flex';
        if (window.lucide) lucide.createIcons();
    }
}

function closeGuideModal() {
    const modal = document.getElementById('guide-modal');
    if (modal) modal.style.display = 'none';
}

// Close modal when clicking backdrop outside content
window.addEventListener('click', (e) => {
    const modal = document.getElementById('guide-modal');
    if (e.target === modal) closeGuideModal();
});

function initNotificationToggle() {
    const enabled = localStorage.getItem('web_push_enabled') !== 'false';
    const switchEl = document.getElementById('notification-toggle-switch');
    const labelEl = document.getElementById('toggle-status-label');
    if (switchEl) switchEl.checked = enabled;
    if (labelEl) {
        labelEl.innerText = enabled ? 'Aktif' : 'Nonaktif';
        labelEl.style.color = enabled ? '#0D5D31' : '#dc2626';
    }
}

async function toggleNotificationState(isChecked) {
    const labelEl = document.getElementById('toggle-status-label');
    localStorage.setItem('web_push_enabled', isChecked ? 'true' : 'false');
    
    if (labelEl) {
        labelEl.innerText = isChecked ? 'Aktif' : 'Nonaktif';
        labelEl.style.color = isChecked ? '#0D5D31' : '#dc2626';
    }

    if (isChecked) {
        if ('Notification' in window) {
            const perm = await Notification.requestPermission();
            if (perm === 'granted') {
                console.log('Notifikasi Web Push diaktifkan.');
            } else {
                alert('⚠️ Izin notifikasi browser diblokir. Harap izinkan notifikasi pada setelan browser Anda.');
            }
        }
    } else {
        console.log('Notifikasi Web Push dinonaktifkan.');
    }
}

async function testWebPushNotification() {
    const isEnabled = localStorage.getItem('web_push_enabled') !== 'false';
    if (!isEnabled) {
        alert('⚠️ Notifikasi Web Push saat ini dalam status NONAKTIF. Geser tombol Slide Switch ke AKTIF terlebih dahulu.');
        return;
    }

    const btn = document.getElementById('btn-test-push');
    if (btn) btn.disabled = true;

    if (!('Notification' in window)) {
        alert('⚠️ Browser Anda tidak mendukung Web Push Notification.');
        if (btn) btn.disabled = false;
        return;
    }

    try {
        // 1. Minta Izin Notifikasi dari User
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            alert('⚠️ Izin notifikasi ditolak/diblokir. Harap aktifkan izin notifikasi pada setelan browser Anda.');
            if (btn) btn.disabled = false;
            return;
        }

        // 2. Tampilkan Notifikasi Browser Langsung
        if ('serviceWorker' in navigator) {
            const reg = await navigator.serviceWorker.ready;
            reg.showNotification('🔔 Uji Notifikasi MicroClimeet', {
                body: 'Web Push Browser berfungsi sempurna! Anda akan menerima alert sensor secara langsung.',
                icon: '../assets/images/brand/mini_logo.png',
                badge: '../assets/images/brand/mini_logo.png',
                vibrate: [200, 100, 200],
                tag: 'test-push-notification',
                renotify: true
            });
        } else {
            new Notification('🔔 Uji Notifikasi MicroClimeet', {
                body: 'Web Push Browser berfungsi sempurna! Anda akan menerima alert sensor secara langsung.',
                icon: '../assets/images/brand/mini_logo.png'
            });
        }

        // 3. Panggil API backend PHP untuk memicu kirim push dari server
        const response = await fetch('../api/push_subscribe.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'test' })
        });
        const resData = await response.json();

        if (resData.success) {
            console.log('Server push test response:', resData);
        }
    } catch (err) {
        console.error('Error testing Web Push:', err);
    } finally {
        if (btn) btn.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    initNotificationToggle();
});

// ── Custom Alert Selection & WhatsApp Report Generator ────────
function toggleSelectAllAlerts(isChecked) {
    const checkboxes = document.querySelectorAll('.alert-checkbox');
    checkboxes.forEach(cb => cb.checked = isChecked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.alert-checkbox:checked').length;
    const countEl = document.getElementById('selected-alert-count');
    if (countEl) countEl.innerText = count;

    const selectAllCb = document.getElementById('select-all-alerts');
    const allCbs = document.querySelectorAll('.alert-checkbox');
    if (selectAllCb && allCbs.length > 0) {
        selectAllCb.checked = (count === allCbs.length);
    }
}

function sendSelectedAlertsToWA() {
    const checked = Array.from(document.querySelectorAll('.alert-checkbox:checked'));
    if (checked.length === 0) {
        alert('⚠️ Silakan centang/pilih minimal 1 peringatan yang ingin dikirimkan ke Admin via WhatsApp.');
        return;
    }

    const farmerName = <?php echo json_encode($_SESSION['name'] ?? 'Petani'); ?>;
    let lines = [];
    lines.push("🌱 *LAPORAN ADUAN PERINGATAN MIKROKLIMAT TERPILIH*");
    lines.push("-------------------------------------------------");
    lines.push("📍 *Petani:* " + farmerName);
    lines.push("📅 *Waktu Lapor:* " + new Date().toLocaleString('id-ID'));
    lines.push("");
    lines.push("🚨 *Daftar Peringatan Terpilih (" + checked.length + " Peringatan):*");

    const recomMap = {};
    checked.forEach((cb, idx) => {
        const time = cb.getAttribute('data-time');
        const gh   = cb.getAttribute('data-gh');
        const msg  = cb.getAttribute('data-msg');
        const type = cb.getAttribute('data-type');

        lines.push((idx + 1) + ". [" + time + " WIB - " + gh + "] " + msg);

        if (type === 'temperature') recomMap['temp'] = "• *Suhu Panas:* Aktifkan blower aerasi & pasang shading net 50%.";
        if (type === 'humidity')    recomMap['hum']  = "• *Kelembapan Ekstrem:* Buka ventilasi udara atas greenhouse.";
        if (type === 'air_quality') recomMap['air']  = "• *Kualitas Udara Buruk:* Periksa sirkulasi exhaust fan.";
        if (type === 'uv')         recomMap['uv']   = "• *Radiasi UV Tinggi:* Pasang terpal pelindung sinar UV.";
    });

    lines.push("");
    lines.push("💡 *Saran Penanganan Teknis:*");
    if (Object.keys(recomMap).length > 0) {
        Object.values(recomMap).forEach(rec => lines.push(rec));
    } else {
        lines.push("• Pertahankan jadwal penyiraman & ventilasi mikroklimat ideal.");
    }

    lines.push("");
    lines.push("Halo Tim Admin MicroClimeet, mohon arahan penanganan untuk laporan peringatan terpilih di atas. Terima kasih!");

    const fullText = lines.join("\n");
    const adminPhone = "6283829634058";
    const waUrl = "https://api.whatsapp.com/send?phone=" + adminPhone + "&text=" + encodeURIComponent(fullText);
    window.open(waUrl, '_blank');
}
</script>

<?php include '../includes/footer.php'; ?>
