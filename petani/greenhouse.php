<?php
/**
 * petani/greenhouse.php
 * CRUD Greenhouse: tambah, edit, hapus greenhouse + lokasi Maps iframe
 */
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('petani');

$page_title  = 'Greenhouse Saya';
$custom_css  = ['dashboard.css'];
$base_url    = '../';
$active_menu = 'greenhouse';
$user_id     = (int)$_SESSION['user_id'];
$message     = '';
$error       = '';

// Migration helper check for photo and commodity columns
try {
    $pdo->exec("ALTER TABLE greenhouses ADD COLUMN photo VARCHAR(255) NULL");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE greenhouses ADD COLUMN commodity VARCHAR(100) NULL AFTER name");
} catch (PDOException $e) {}

function processGreenhousePhotoUpload($fileInput) {
    if (!isset($fileInput) || $fileInput['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $fileType = @mime_content_type($fileInput['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Format file gambar tidak valid. Gunakan format JPG, PNG, WEBP, atau GIF.');
    }
    if ($fileInput['size'] > 5 * 1024 * 1024) {
        throw new Exception('Ukuran file gambar terlalu besar. Maksimal 5MB.');
    }

    $uploadDir = __DIR__ . '/../uploads/greenhouses/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($fileInput['name'], PATHINFO_EXTENSION);
    $filename = 'gh_' . uniqid() . '_' . time() . '.' . strtolower($ext);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($fileInput['tmp_name'], $targetPath)) {
        return 'uploads/greenhouses/' . $filename;
    }
    return null;
}

// ── CREATE ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    if ($action === 'create') {
        $name        = trim($_POST['name']        ?? '');
        $location    = trim($_POST['location']    ?? '');
        $latitude    = trim($_POST['latitude']    ?? '');
        $longitude   = trim($_POST['longitude']   ?? '');
        $notes       = trim($_POST['notes']       ?? '');

        $comm_select  = trim($_POST['commodity_select'] ?? '');
        $comm_custom  = trim($_POST['commodity_custom'] ?? '');
        $commodity    = ($comm_select === 'other') ? $comm_custom : $comm_select;
        if (empty($commodity)) $commodity = 'Melon Premium';

        if (empty($name)) {
            $error = 'Nama greenhouse wajib diisi.';
        } else {
            try {
                $photoPath = null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $photoPath = processGreenhousePhotoUpload($_FILES['photo']);
                }

                // New greenhouse created by farmer starts without assigned hardware (device_code NULL, firebase_key NULL)
                $stmt = $pdo->prepare(
                    "INSERT INTO greenhouses (user_id, name, commodity, location, latitude, longitude, device_code, firebase_key, notes, photo, status)
                     VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, 'inactive')"
                );
                $stmt->execute([
                    $user_id,
                    $name,
                    $commodity,
                    $location ?: null,
                    $latitude  !== '' ? (float)$latitude  : null,
                    $longitude !== '' ? (float)$longitude : null,
                    $notes ?: null,
                    $photoPath
                ]);

                $log = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                $log->execute([$user_id, "Menambahkan greenhouse baru: $name (Komoditas: $commodity)"]);
                $message = "Greenhouse \"$name\" berhasil ditambahkan! Silakan hubungi Admin untuk mengalokasikan Kode Perangkat IoT.";
            } catch (Exception $e) {
                $error = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }

    // ── UPDATE ────────────────────────────────────────────────
    elseif ($action === 'update') {
        $gh_id       = (int)($_POST['gh_id']      ?? 0);
        $name        = trim($_POST['name']         ?? '');
        $location    = trim($_POST['location']     ?? '');
        $latitude    = trim($_POST['latitude']     ?? '');
        $longitude   = trim($_POST['longitude']    ?? '');
        $notes       = trim($_POST['notes']        ?? '');

        $comm_select  = trim($_POST['commodity_select'] ?? '');
        $comm_custom  = trim($_POST['commodity_custom'] ?? '');
        $commodity    = ($comm_select === 'other') ? $comm_custom : $comm_select;
        if (empty($commodity)) $commodity = 'Melon Premium';

        if ($gh_id <= 0 || empty($name)) {
            $error = 'Data tidak valid.';
        } else {
            try {
                // Verifikasi kepemilikan & ambil data photo saat ini
                $check = $pdo->prepare("SELECT id, photo FROM greenhouses WHERE id = ? AND user_id = ?");
                $check->execute([$gh_id, $user_id]);
                $currentGh = $check->fetch();

                if (!$currentGh) {
                    $error = 'Greenhouse tidak ditemukan.';
                } else {
                    $photoPath = $currentGh['photo'];
                    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $newPhoto = processGreenhousePhotoUpload($_FILES['photo']);
                        if ($newPhoto) {
                            if (!empty($photoPath) && file_exists(__DIR__ . '/../' . $photoPath)) {
                                @unlink(__DIR__ . '/../' . $photoPath);
                            }
                            $photoPath = $newPhoto;
                        }
                    }

                    // Only update greenhouse profile details; device_code & firebase_key remain managed strictly by Admin
                    $stmt = $pdo->prepare(
                        "UPDATE greenhouses SET name=?, commodity=?, location=?, latitude=?, longitude=?, notes=?, photo=?
                         WHERE id = ? AND user_id = ?"
                    );
                    $stmt->execute([
                        $name,
                        $commodity,
                        $location ?: null,
                        $latitude  !== '' ? (float)$latitude  : null,
                        $longitude !== '' ? (float)$longitude : null,
                        $notes ?: null,
                        $photoPath,
                        $gh_id,
                        $user_id,
                    ]);

                    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                    $log->execute([$user_id, "Memperbarui data greenhouse: $name (Komoditas: $commodity)"]);
                    $message = "Greenhouse \"$name\" berhasil diperbarui!";
                }
            } catch (Exception $e) {
                $error = 'Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }

    // ── DELETE ────────────────────────────────────────────────
    elseif ($action === 'delete') {
        $gh_id = (int)($_POST['gh_id'] ?? 0);
        if ($gh_id > 0) {
            try {
                $check = $pdo->prepare("SELECT name, photo FROM greenhouses WHERE id = ? AND user_id = ?");
                $check->execute([$gh_id, $user_id]);
                $ghRow = $check->fetch();
                if ($ghRow) {
                    if (!empty($ghRow['photo']) && file_exists(__DIR__ . '/../' . $ghRow['photo'])) {
                        @unlink(__DIR__ . '/../' . $ghRow['photo']);
                    }
                    $pdo->prepare("DELETE FROM greenhouses WHERE id = ? AND user_id = ?")
                        ->execute([$gh_id, $user_id]);
                    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                    $log->execute([$user_id, "Menghapus greenhouse: {$ghRow['name']}"]);
                    $message = "Greenhouse berhasil dihapus.";
                } else {
                    $error = 'Greenhouse tidak ditemukan.';
                }
            } catch (PDOException $e) {
                $error = 'Gagal menghapus: ' . $e->getMessage();
            }
        }
    }
}

// ── READ: Ambil semua greenhouse milik user ───────────────────
$greenhouses = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM greenhouses WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $greenhouses = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Gagal memuat data: ' . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2><i data-lucide="home" style="color:#0D5D31;"></i> Greenhouse Saya</h2>
                <p>Kelola lokasi dan perangkat IoT greenhouse Anda.</p>
            </div>
            <div class="header-controls">
                <button class="btn btn-primary" onclick="openModal('modal-add')" style="gap:8px; display:flex; align-items:center;">
                    <i data-lucide="plus" style="width:16px;height:16px;"></i>
                    Tambah Greenhouse
                </button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert-success-bar" style="background:rgba(74,222,128,0.12);border:1px solid rgba(74,222,128,0.3);border-radius:12px;padding:12px 18px;margin-bottom:20px;color:#166534;display:flex;align-items:center;gap:10px;">
            <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert-error-bar" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:12px;padding:12px 18px;margin-bottom:20px;color:#b91c1c;display:flex;align-items:center;gap:10px;">
            <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (empty($greenhouses)): ?>
        <!-- Empty state -->
        <div class="glass-card empty-state">
            <i data-lucide="home" class="empty-state-icon" style="color:var(--accent-color) !important;"></i>
            <h3 class="empty-state-title">Belum Ada Greenhouse</h3>
            <p class="empty-state-desc" style="margin-bottom:20px !important;">Mulai dengan menambahkan greenhouse pertama Anda.</p>
            <button class="btn btn-primary" onclick="openModal('modal-add')">+ Tambah Greenhouse</button>
        </div>

        <?php else: ?>
        <!-- Greenhouse Cards Grid -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:24px;">
            <?php foreach ($greenhouses as $gh): ?>
            <?php
                $statusColors = [
                    'active'   => ['bg'=>'rgba(74,222,128,0.1)',  'border'=>'rgba(74,222,128,0.3)',  'dot'=>'#22c55e', 'label'=>'Aktif'],
                    'inactive' => ['bg'=>'rgba(107,114,128,0.1)', 'border'=>'rgba(107,114,128,0.25)','dot'=>'#9ca3af', 'label'=>'Tidak Aktif'],
                    'warning'  => ['bg'=>'rgba(251,191,36,0.1)',  'border'=>'rgba(251,191,36,0.3)',  'dot'=>'#f59e0b', 'label'=>'Peringatan'],
                ];
                $sc = $statusColors[$gh['status']] ?? $statusColors['active'];
                $lat = $gh['latitude'];
                $lng = $gh['longitude'];
                $hasCoords = $lat && $lng;
            ?>
            <div class="glass-card" style="padding:0;overflow:hidden;border-radius:var(--radius-lg);">
                <!-- Greenhouse Photo Banner -->
                <?php if (!empty($gh['photo']) && file_exists(__DIR__ . '/../' . $gh['photo'])): ?>
                <div style="position:relative;height:180px;width:100%;overflow:hidden;background:#f0f4f0;">
                    <img src="../<?php echo htmlspecialchars($gh['photo']); ?>" alt="<?php echo htmlspecialchars($gh['name']); ?>" style="width:100%;height:100%;object-fit:cover;transition:transform 0.4s ease;" class="gh-card-img">
                </div>
                <?php else: ?>
                <div style="position:relative;height:120px;width:100%;overflow:hidden;background:linear-gradient(135deg, #0D5D31 0%, #15803D 100%);display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="sprout" style="width:44px;height:44px;color:rgba(255,255,255,0.35);"></i>
                </div>
                <?php endif; ?>

                <!-- Status header bar -->
                <div style="background:<?php echo $sc['bg']; ?>;border-bottom:1px solid <?php echo $sc['border']; ?>;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;">
                    <span style="display:flex;align-items:center;gap:6px;font-size:0.8rem;font-weight:700;color:<?php echo $sc['dot']; ?>;">
                        <span style="width:7px;height:7px;border-radius:50%;background:<?php echo $sc['dot']; ?>;display:inline-block;"></span>
                        <?php echo $sc['label']; ?>
                    </span>
                    <span style="font-size:0.75rem;color:var(--text-secondary);"><?php echo date('d M Y', strtotime($gh['created_at'])); ?></span>
                </div>

                <div style="padding:20px;">
                    <h3 style="font-family:var(--font-heading);font-size:1.15rem;font-weight:700;margin-bottom:4px;">
                        <?php echo htmlspecialchars($gh['name']); ?>
                    </h3>
                    <?php if (!empty($gh['notes'])): ?>
                    <p style="color:var(--text-secondary);font-size:0.82rem;margin-bottom:6px;line-height:1.4;">
                        <?php echo nl2br(htmlspecialchars($gh['notes'])); ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($gh['location']): ?>
                    <p style="color:var(--text-secondary);font-size:0.85rem;display:flex;align-items:center;gap:5px;margin-bottom:12px;">
                        <i data-lucide="map-pin" style="width:14px;height:14px;"></i>
                        <?php echo htmlspecialchars($gh['location']); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Device info chips -->
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
                        <?php if (!empty($gh['commodity'])): ?>
                        <span style="background:rgba(13,93,49,0.1);color:#0D5D31;border:1px solid rgba(13,93,49,0.2);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                            <i data-lucide="sprout" style="width:13px;height:13px;"></i> <?php echo htmlspecialchars($gh['commodity']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($gh['device_code']): ?>
                        <span style="background:rgba(4,73,4,0.08);color:#166534;border:1px solid rgba(4,73,4,0.15);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;">
                            <i data-lucide="cpu" style="width:13px;height:13px;"></i> <?php echo htmlspecialchars($gh['device_code']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($gh['firebase_key']): ?>
                        <span style="background:rgba(251,146,60,0.1);color:#c2410c;border:1px solid rgba(251,146,60,0.2);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;">
                            <i data-lucide="flame" style="width:13px;height:13px;"></i> Firebase: <?php echo htmlspecialchars(substr($gh['firebase_key'], 0, 12)) . (strlen($gh['firebase_key']) > 12 ? '…' : ''); ?>
                        </span>
                        <?php endif; ?>
                    </div>


                    <!-- Google Maps iframe preview -->
                    <?php if ($hasCoords): ?>
                    <div style="border-radius:10px;overflow:hidden;margin-bottom:14px;border:1px solid rgba(0,0,0,0.08);">
                        <iframe
                            width="100%"
                            height="160"
                            frameborder="0"
                            style="border:0;display:block;"
                            loading="lazy"
                            allowfullscreen
                            src="https://maps.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lng; ?>&z=16&output=embed">
                        </iframe>
                    </div>
                    <?php endif; ?>

                    <!-- Action buttons -->
                    <div style="display:flex;gap:8px;">
                        <button
                            class="btn btn-secondary"
                            style="flex:1;font-size:0.82rem;padding:8px 12px;gap:5px;display:flex;align-items:center;justify-content:center;"
                            onclick='openEditModal(<?php echo htmlspecialchars(json_encode($gh)); ?>)'>
                            <i data-lucide="edit-2" style="width:14px;height:14px;"></i> Edit
                        </button>
                        <button
                            class="btn btn-danger"
                            style="font-size:0.82rem;padding:8px 14px;gap:5px;display:flex;align-items:center;background:rgba(239,68,68,0.1);color:#b91c1c;border:1px solid rgba(239,68,68,0.25);border-radius:var(--radius-pill);"
                            onclick="confirmDelete(<?php echo $gh['id']; ?>, '<?php echo htmlspecialchars(addslashes($gh['name'])); ?>')">
                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                        </button>
                        <?php if ($hasCoords): ?>
                        <a href="https://maps.google.com/?q=<?php echo $lat; ?>,<?php echo $lng; ?>"
                           target="_blank"
                           class="btn btn-secondary"
                           style="font-size:0.82rem;padding:8px 14px;gap:5px;display:flex;align-items:center;">
                            <i data-lucide="external-link" style="width:14px;height:14px;"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- ── MODAL: Tambah Greenhouse ────────────────────────────── -->
<div id="modal-add" class="modal-overlay" style="display:none;" onclick="closeModal('modal-add')">
    <div class="modal-box glass-card" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i data-lucide="plus-circle" style="width:20px;height:20px;"></i> Tambah Greenhouse</h3>
            <button onclick="closeModal('modal-add')" class="modal-close">✕</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <?php include '_greenhouse_form.php'; ?>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeModal('modal-add')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Greenhouse</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL: Edit Greenhouse ──────────────────────────────── -->
<div id="modal-edit" class="modal-overlay" style="display:none;" onclick="closeModal('modal-edit')">
    <div class="modal-box glass-card" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i data-lucide="edit" style="width:20px;height:20px;"></i> Edit Greenhouse</h3>
            <button onclick="closeModal('modal-edit')" class="modal-close">✕</button>
        </div>
        <form method="POST" id="form-edit" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="gh_id" id="edit-gh-id">
            <?php include '_greenhouse_form.php'; ?>
            <div style="margin-top:14px;">
                <label class="form-label" style="display:flex;align-items:center;justify-content:space-between;">
                    <span>Status Realtime Perangkat</span>
                    <span style="font-size:0.68rem;font-weight:700;color:#475569;background:#e2e8f0;padding:2px 7px;border-radius:6px;">⚡ Otomatis</span>
                </label>
                <div id="edit-status-container" style="padding:12px 14px;border-radius:12px;border:1.5px solid #cbd5e1;background:#f8fafc;display:flex;align-items:center;gap:12px;transition:all 0.2s ease;">
                    <div id="edit-status-dot" style="width:12px;height:12px;border-radius:50%;background:#64748b;flex-shrink:0;"></div>
                    <div>
                        <div id="edit-status-title" style="font-weight:700;font-size:0.9rem;color:#1e293b;">Pemeriksaan Status...</div>
                        <div id="edit-status-desc" style="font-size:0.78rem;color:#64748b;margin-top:2px;">Status ditentukan secara otomatis berdasarkan alokasi perangkat IoT fisik.</div>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeModal('modal-edit')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL: Interactive Map Location Picker (Leaflet + Nominatim Search) ── -->
<div id="modal-map-picker" class="modal-overlay" style="display:none;z-index:9999;" onclick="closeModal('modal-map-picker')">
    <div class="modal-box glass-card" style="max-width:760px;padding:24px;" onclick="event.stopPropagation()">
        <div class="modal-header" style="margin-bottom:14px;">
            <h3 style="margin:0;display:flex;align-items:center;gap:8px;color:#0D5D31;font-size:1.1rem;font-weight:800;">
                <i data-lucide="map-pin" style="width:20px;height:20px;"></i> Penentuan Titik Koordinat Presisi
            </h3>
            <button type="button" onclick="closeModal('modal-map-picker')" class="modal-close">✕</button>
        </div>

        <!-- Search Bar Inside Map -->
        <div style="display:flex;gap:8px;margin-bottom:12px;">
            <input type="text" id="map-search-input" class="form-input" placeholder="Cari nama kota, desa, jalan, atau patokan lokasi..." style="flex:1;background:#fff;" onkeydown="if(event.key==='Enter'){event.preventDefault();searchLocationOnMap();}">
            <button type="button" onclick="searchLocationOnMap()" class="btn btn-primary" style="background:#0D5D31;color:#fff;border:none;padding:9px 18px;border-radius:10px;font-weight:700;display:flex;align-items:center;gap:6px;cursor:pointer;">
                <i data-lucide="search" style="width:15px;height:15px;"></i> Cari
            </button>
        </div>

        <!-- Leaflet Map Canvas Container -->
        <div id="leaflet-map-container" style="width:100%;height:380px;border-radius:14px;border:1.5px solid rgba(0,0,0,0.15);overflow:hidden;position:relative;background:#e2e8f0;"></div>

        <!-- Live Selected Coordinates Bar -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding:12px 16px;background:rgba(13,93,49,0.06);border:1px solid rgba(13,93,49,0.15);border-radius:12px;flex-wrap:wrap;gap:10px;">
            <div style="font-size:0.85rem;color:#0D5D31;">
                <strong>📍 Koordinat Presisi Terpilih:</strong> 
                <span id="map-selected-lat" style="font-family:monospace;font-weight:800;margin-left:4px;">-7.558879</span>, 
                <span id="map-selected-lng" style="font-family:monospace;font-weight:800;">110.773807</span>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="button" onclick="closeModal('modal-map-picker')" class="btn btn-secondary">Batal</button>
                <button type="button" onclick="applySelectedMapLocation()" class="btn btn-primary" style="background:#0D5D31!important;color:#fff!important;font-weight:800;border:none;padding:9px 20px;border-radius:10px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i> Gunakan Lokasi Ini
                </button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- ── MODAL: Konfirmasi Delete ────────────────────────────── -->
<div id="modal-delete" class="modal-overlay" style="display:none;" onclick="closeModal('modal-delete')">
    <div class="modal-box glass-card" style="max-width:420px;" onclick="event.stopPropagation()">
        <div style="text-align:center;padding:8px 0 20px;">
            <i data-lucide="trash-2" style="width:48px;height:48px;color:#ef4444;margin-bottom:12px;"></i>
            <h3 style="font-family:var(--font-heading);font-size:1.2rem;margin-bottom:8px;">Hapus Greenhouse?</h3>
            <p style="color:var(--text-secondary);font-size:0.9rem;">
                Greenhouse "<strong id="delete-gh-name"></strong>" dan seluruh data telemetri serta peringatan terkait akan dihapus permanen.
            </p>
        </div>
        <form method="POST" style="display:flex;gap:10px;justify-content:center;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="gh_id" id="delete-gh-id">
            <button type="button" onclick="closeModal('modal-delete')" class="btn btn-secondary">Batal</button>
            <button type="submit" style="background:#ef4444;color:#fff;border:none;padding:9px 24px;border-radius:var(--radius-pill);font-weight:700;cursor:pointer;">Hapus Sekarang</button>
        </form>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed; inset: 0; z-index: 9000;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(6px);
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
}
.modal-box {
    width: 100%; max-width: 540px;
    max-height: 92vh; overflow-y: auto;
    padding: 28px 28px 24px;
    border-radius: var(--radius-xl);
    animation: modalIn 0.3s cubic-bezier(0.16,1,0.3,1);
}
@keyframes modalIn {
    from { opacity:0; transform:translateY(20px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
}
.modal-header h3 {
    font-family: var(--font-heading); font-size: 1.1rem; font-weight: 700;
    display: flex; align-items: center; gap: 8px;
}
.modal-close {
    background: none; border: none; font-size: 1.2rem; cursor: pointer;
    color: var(--text-secondary); line-height: 1; padding: 2px 6px;
    border-radius: 6px;
}
.modal-close:hover { background: rgba(0,0,0,0.07); }
.form-label {
    display: block; font-weight: 600; font-size: 0.83rem;
    color: var(--text-secondary); margin-bottom: 5px; margin-top: 12px;
}
.form-input {
    width: 100%; padding: 9px 12px; border-radius: 10px;
    border: 1.5px solid rgba(0,0,0,0.12);
    background: rgba(255,255,255,0.7);
    font-family: var(--font-body); font-size: 0.9rem;
    transition: border-color 0.2s;
    box-sizing: border-box;
}
.form-input:focus { border-color: var(--accent-color); outline: none; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: end; }
.glass-card:hover .gh-card-img { transform: scale(1.05); }
</style>

<script>
function handleFormCommodityChange(select) {
    const form = select.closest('form');
    if (!form) return;
    const customWrapper = form.querySelector('.custom-commodity-wrapper');
    const customInput = form.querySelector('.commodity-custom-input');
    if (select.value === 'other') {
        if (customWrapper) customWrapper.style.display = 'block';
        if (customInput) { customInput.required = true; customInput.focus(); }
    } else {
        if (customWrapper) customWrapper.style.display = 'none';
        if (customInput) { customInput.required = false; }
    }
}
function openModal(id) {
    if (id === 'modal-add') {
        const f = document.querySelector('#modal-add form');
        if (f) {
            f.reset();
            const img = f.querySelector('.photo-preview-img');
            const icon = f.querySelector('.photo-placeholder-icon');
            if (img) { img.src = ''; img.style.display = 'none'; }
            if (icon) icon.style.display = 'block';
            const commSelect = f.querySelector('.commodity-select-field');
            if (commSelect) handleFormCommodityChange(commSelect);
        }
    }
    document.getElementById(id).style.display = 'flex';
    lucide.createIcons();
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
function confirmDelete(id, name) {
    document.getElementById('delete-gh-id').value = id;
    document.getElementById('delete-gh-name').innerText = name;
    openModal('modal-delete');
}
function handlePhotoPreview(input) {
    const form = input.closest('form');
    if (!form) return;
    const img = form.querySelector('.photo-preview-img');
    const icon = form.querySelector('.photo-placeholder-icon');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            if (icon) icon.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
function openEditModal(gh) {
    const f = document.getElementById('form-edit');
    document.getElementById('edit-gh-id').value        = gh.id;
    f.querySelector('[name="name"]').value              = gh.name        || '';
    f.querySelector('[name="location"]').value          = gh.location    || '';
    f.querySelector('[name="latitude"]').value          = gh.latitude    || '';
    f.querySelector('[name="longitude"]').value         = gh.longitude   || '';
    f.querySelector('[name="device_code"]').value       = gh.device_code || '';
    f.querySelector('[name="firebase_key"]').value      = gh.firebase_key|| '';
    f.querySelector('[name="notes"]').value             = gh.notes       || '';
    // Dynamic Realtime Status Badge Display
    const statusContainer = document.getElementById('edit-status-container');
    const statusDot       = document.getElementById('edit-status-dot');
    const statusTitle     = document.getElementById('edit-status-title');
    const statusDesc      = document.getElementById('edit-status-desc');

    if (!gh.device_code || gh.device_code.trim() === '') {
        if (statusContainer) {
            statusContainer.style.background = '#fef2f2';
            statusContainer.style.borderColor = '#fca5a5';
        }
        if (statusDot) statusDot.style.background = '#ef4444';
        if (statusTitle) {
            statusTitle.style.color = '#991b1b';
            statusTitle.innerText = 'Tidak Aktif (Belum Diverifikasi Admin)';
        }
        if (statusDesc) {
            statusDesc.style.color = '#7f1d1d';
            statusDesc.innerText = 'Greenhouse ini belum memiliki alokasi Kode Perangkat IoT fisik.';
        }
    } else if (gh.status === 'warning') {
        if (statusContainer) {
            statusContainer.style.background = '#fffbe6';
            statusContainer.style.borderColor = '#f59e0b';
        }
        if (statusDot) statusDot.style.background = '#f59e0b';
        if (statusTitle) {
            statusTitle.style.color = '#92400e';
            statusTitle.innerText = 'Peringatan (' + gh.device_code + ')';
        }
        if (statusDesc) {
            statusDesc.style.color = '#78350f';
            statusDesc.innerText = 'Perangkat terhubung (' + gh.device_code + '), terdapat parameter di luar batas ideal.';
        }
    } else {
        if (statusContainer) {
            statusContainer.style.background = '#f0fdf4';
            statusContainer.style.borderColor = '#86efac';
        }
        if (statusDot) statusDot.style.background = '#22c55e';
        if (statusTitle) {
            statusTitle.style.color = '#166534';
            statusTitle.innerText = 'Aktif (Terhubung: ' + gh.device_code + ')';
        }
        if (statusDesc) {
            statusDesc.style.color = '#14532d';
            statusDesc.innerText = 'Perangkat IoT fisik terdaftar dan aktif mengirimkan sinyal telemetri.';
        }
    }

    const presets = ['Melon Premium', 'Tomat Ceri', 'Paprika', 'Stroberi'];
    const commSelect = f.querySelector('.commodity-select-field');
    const commCustom = f.querySelector('.commodity-custom-input');
    const commWrapper = f.querySelector('.custom-commodity-wrapper');

    if (gh.commodity && presets.includes(gh.commodity)) {
        if (commSelect) commSelect.value = gh.commodity;
        if (commCustom) commCustom.value = '';
        if (commWrapper) commWrapper.style.display = 'none';
    } else if (gh.commodity) {
        if (commSelect) commSelect.value = 'other';
        if (commCustom) commCustom.value = gh.commodity;
        if (commWrapper) commWrapper.style.display = 'block';
    } else {
        if (commSelect) commSelect.value = 'Melon Premium';
        if (commCustom) commCustom.value = '';
        if (commWrapper) commWrapper.style.display = 'none';
    }

    const img = f.querySelector('.photo-preview-img');
    const icon = f.querySelector('.photo-placeholder-icon');
    if (gh.photo) {
        if (img) {
            img.src = '../' + gh.photo;
            img.style.display = 'block';
        }
        if (icon) icon.style.display = 'none';
    } else {
        if (img) {
            img.src = '';
            img.style.display = 'none';
        }
        if (icon) icon.style.display = 'block';
    }

    openModal('modal-edit');
}
let currentActiveForm = null;
let leafletMap = null;
let leafletMarker = null;
let selectedMapLat = -7.558879;
let selectedMapLng = 110.773807;

function openMapPickerFromForm(btn) {
    currentActiveForm = btn.closest('form');
    const latVal = parseFloat(currentActiveForm.querySelector('[name="latitude"]').value);
    const lngVal = parseFloat(currentActiveForm.querySelector('[name="longitude"]').value);

    selectedMapLat = !isNaN(latVal) ? latVal : -7.558879;
    selectedMapLng = !isNaN(lngVal) ? lngVal : 110.773807;

    document.getElementById('modal-map-picker').style.display = 'flex';
    if (window.lucide) lucide.createIcons();

    setTimeout(() => {
        initLeafletMap(selectedMapLat, selectedMapLng);
    }, 200);
}

function initLeafletMap(lat, lng) {
    if (typeof L === 'undefined') {
        alert('Modul peta sedang dimuat, silakan coba beberapa detik lagi.');
        return;
    }

    if (!leafletMap) {
        leafletMap = L.map('leaflet-map-container').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap / MicroClimeet'
        }).addTo(leafletMap);

        leafletMarker = L.marker([lat, lng], { draggable: true }).addTo(leafletMap);

        leafletMarker.on('dragend', function(e) {
            const pos = leafletMarker.getLatLng();
            updateMapCoordsDisplay(pos.lat, pos.lng);
        });

        leafletMap.on('click', function(e) {
            leafletMarker.setLatLng(e.latlng);
            updateMapCoordsDisplay(e.latlng.lat, e.latlng.lng);
        });
    } else {
        leafletMap.setView([lat, lng], 15);
        leafletMarker.setLatLng([lat, lng]);
        leafletMap.invalidateSize();
    }
    updateMapCoordsDisplay(lat, lng);
}

function updateMapCoordsDisplay(lat, lng) {
    selectedMapLat = parseFloat(lat).toFixed(6);
    selectedMapLng = parseFloat(lng).toFixed(6);
    document.getElementById('map-selected-lat').innerText = selectedMapLat;
    document.getElementById('map-selected-lng').innerText = selectedMapLng;
}

function searchLocationOnMap() {
    const q = document.getElementById('map-search-input').value.trim();
    if (!q) return;

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => {
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                leafletMap.setView([lat, lon], 16);
                leafletMarker.setLatLng([lat, lon]);
                updateMapCoordsDisplay(lat, lon);
            } else {
                alert('Lokasi tidak ditemukan. Coba masukkan nama kota/desa yang lebih spesifik.');
            }
        })
        .catch(err => alert('Gagal mencari lokasi.'));
}

function formatIndonesianAddress(addr) {
    if (!addr) return '';

    // 1. Desa / Kelurahan / Suburb / Village
    let desa = addr.village || addr.suburb || addr.hamlet || addr.neighbourhood || addr.quarter || addr.residential || '';
    if (desa) {
        desa = desa.trim();
        if (!desa.toLowerCase().startsWith('desa') && !desa.toLowerCase().startsWith('kel')) {
            desa = 'Desa ' + desa;
        }
    }

    // 2. Kecamatan / Sub-district / District
    let kec = addr.subdistrict || addr.district || addr.township || addr.municipality || '';
    if (kec) {
        kec = kec.trim();
        if (!kec.toLowerCase().startsWith('kec')) {
            kec = 'Kec. ' + kec;
        }
    }

    // 3. Kabupaten / Kota / County / City
    let kab = addr.county || addr.city || addr.regency || addr.city_district || '';
    if (kab) {
        kab = kab.trim();
        kab = kab.replace(/^Kabupaten\s+/i, 'Kab. ');
        kab = kab.replace(/^Kota\s+/i, 'Kota ');
        if (!kab.toLowerCase().startsWith('kab.') && !kab.toLowerCase().startsWith('kota')) {
            kab = 'Kab. ' + kab;
        }
    }

    const parts = [desa, kec, kab].filter(p => p && p.trim() !== '');
    if (parts.length > 0) {
        return parts.join(', ');
    }

    return addr.display_name ? addr.display_name.split(',').slice(0, 3).join(', ') : '';
}

function selectLocationMode(btn, mode) {
    const parent = btn.closest('.location-precision-panel');
    if (!parent) return;

    // Reset tab active state styling
    parent.querySelectorAll('.loc-mode-btn').forEach(b => {
        b.style.background = 'transparent';
        b.style.color = '#475569';
        b.style.boxShadow = 'none';
    });

    // Set active tab styling
    btn.style.background = '#0D5D31';
    btn.style.color = '#fff';
    btn.style.boxShadow = '0 2px 8px rgba(13,93,49,0.25)';

    const helpText = parent.querySelector('.loc-help-text');
    const modeBadge = parent.querySelector('.loc-mode-badge');

    if (mode === 'gps') {
        if (modeBadge) modeBadge.innerText = '🎯 Mode GPS Hardware';
        if (helpText) helpText.innerHTML = 'Mendeteksi koordinat lokasi fisik Anda secara otomatis via GPS.';
        getCurrentLocationGPS(btn);
    } else if (mode === 'map') {
        if (modeBadge) modeBadge.innerText = '🗺️ Mode Peta Interaktif';
        if (helpText) helpText.innerHTML = 'Membuka peta interaktif. Geser pin marker 📍 ke lokasi presisi greenhouse.';
        openMapPickerFromForm(btn);
    } else if (mode === 'paste') {
        if (modeBadge) modeBadge.innerText = '📋 Mode Salin Link';
        if (helpText) helpText.innerHTML = 'Tempelkan koordinat atau link Google Maps yang disalin dari HP/Browser.';
        parsePastedCoordinates(btn);
    }
}

function applySelectedMapLocation() {
    if (currentActiveForm) {
        currentActiveForm.querySelector('[name="latitude"]').value = selectedMapLat;
        currentActiveForm.querySelector('[name="longitude"]').value = selectedMapLng;

        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${selectedMapLat}&lon=${selectedMapLng}`)
            .then(r => r.json())
            .then(data => {
                if (data && data.address) {
                    const formatted = formatIndonesianAddress(data.address);
                    if (formatted) {
                        currentActiveForm.querySelector('[name="location"]').value = formatted;
                    }
                }
            })
            .catch(() => {})
            .finally(() => {
                closeModal('modal-map-picker');
            });
    } else {
        closeModal('modal-map-picker');
    }
}

function getCurrentLocationGPS(btn) {
    const form = btn.closest('form');
    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung deteksi lokasi GPS.');
        return;
    }
    
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<i data-lucide="loader" style="width:13px;height:13px;"></i> GPS...';
    if (window.lucide) lucide.createIcons();

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude.toFixed(6);
            const lng = pos.coords.longitude.toFixed(6);
            form.querySelector('[name="latitude"]').value = lat;
            form.querySelector('[name="longitude"]').value = lng;

            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(r => r.json())
                .then(data => {
                    if (data && data.address) {
                        const formatted = formatIndonesianAddress(data.address);
                        if (formatted) {
                            form.querySelector('[name="location"]').value = formatted;
                        }
                    }
                })
                .catch(() => {})
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                    if (window.lucide) lucide.createIcons();
                });
        },
        (err) => {
            alert('Gagal mendeteksi lokasi GPS. Pastikan izin lokasi pada browser Anda telah diizinkan.');
            btn.disabled = false;
            btn.innerHTML = origHtml;
            if (window.lucide) lucide.createIcons();
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function parsePastedCoordinates(btn) {
    const form = btn.closest('form');
    const input = prompt("Tempel Link Google Maps (mis. https://maps.app.goo.gl/...) atau Koordinat (mis. -7.559081, 110.774424):");
    if (!input || !input.trim()) return;

    const cleanInput = input.trim();

    // 1. Direct Regex Match for @lat,lng, !3dlat!4dlng, or raw lat,lng
    let match = cleanInput.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (!match) {
        match = cleanInput.match(/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/);
    }
    if (!match) {
        match = cleanInput.match(/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/);
    }

    if (match) {
        const lat = parseFloat(match[1]).toFixed(6);
        const lng = parseFloat(match[2]).toFixed(6);
        applyParsedCoordsToForm(form, lat, lng);
        return;
    }

    // 2. If short URL like maps.app.goo.gl or goo.gl or any HTTP link
    if (cleanInput.includes('maps.app.goo.gl') || cleanInput.includes('goo.gl') || cleanInput.startsWith('http')) {
        const helpText = form.querySelector('.loc-help-text');
        if (helpText) helpText.innerHTML = '⏳ Mengekstrak koordinat dari link Google Maps...';

        fetch(`../api/resolve_gmaps_url.php?url=${encodeURIComponent(cleanInput)}`)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.latitude && res.longitude) {
                    applyParsedCoordsToForm(form, res.latitude, res.longitude);
                } else {
                    alert('⚠️ ' + (res.error || 'Gagal mendeteksi titik koordinat dari link Google Maps yang dimasukkan.'));
                }
            })
            .catch(err => {
                alert('⚠️ Gagal terhubung ke server untuk memproses link Google Maps.');
            })
            .finally(() => {
                if (helpText) helpText.innerHTML = 'Gunakan pilihan mode di atas untuk mengambil koordinat presisi dari GPS, Peta, atau Tempel Link.';
                if (window.lucide) lucide.createIcons();
            });
        return;
    }

    alert("⚠️ Format tidak dikenali. Contoh format valid:\n1. Link GMaps: https://maps.app.goo.gl/...\n2. Koordinat: -7.559081, 110.774424");
}

function applyParsedCoordsToForm(form, lat, lng) {
    form.querySelector('[name="latitude"]').value = lat;
    form.querySelector('[name="longitude"]').value = lng;

    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(r => r.json())
        .then(data => {
            if (data && data.address) {
                const formatted = formatIndonesianAddress(data.address);
                if (formatted) {
                    form.querySelector('[name="location"]').value = formatted;
                }
            }
        })
        .catch(() => {})
        .finally(() => {
            alert(`✅ Koordinat presisi berhasil diekstrak dari Google Maps:\nLatitude: ${lat}\nLongitude: ${lng}`);
        });
}

document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>

<?php
// Footer / JS includes
$custom_js = [];
include '../includes/footer.php';
?>


