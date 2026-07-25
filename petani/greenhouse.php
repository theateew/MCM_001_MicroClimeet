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

// ── CREATE ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    if ($action === 'create') {
        $name        = trim($_POST['name']        ?? '');
        $location    = trim($_POST['location']    ?? '');
        $latitude    = trim($_POST['latitude']    ?? '');
        $longitude   = trim($_POST['longitude']   ?? '');
        $device_code = trim($_POST['device_code'] ?? '');
        $firebase_key= trim($_POST['firebase_key']?? '');
        $notes       = trim($_POST['notes']       ?? '');

        if (empty($name)) {
            $error = 'Nama greenhouse wajib diisi.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO greenhouses (user_id, name, location, latitude, longitude, device_code, firebase_key, notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $user_id,
                    $name,
                    $location ?: null,
                    $latitude  !== '' ? (float)$latitude  : null,
                    $longitude !== '' ? (float)$longitude : null,
                    $device_code ?: null,
                    $firebase_key ?: null,
                    $notes ?: null,
                ]);

                $log = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                $log->execute([$user_id, "Menambahkan greenhouse baru: $name"]);
                $message = "Greenhouse \"$name\" berhasil ditambahkan!";
            } catch (PDOException $e) {
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
        $device_code = trim($_POST['device_code']  ?? '');
        $firebase_key= trim($_POST['firebase_key'] ?? '');
        $notes       = trim($_POST['notes']        ?? '');
        $status      = $_POST['status']            ?? 'active';

        if ($gh_id <= 0 || empty($name)) {
            $error = 'Data tidak valid.';
        } else {
            try {
                // Verifikasi kepemilikan
                $check = $pdo->prepare("SELECT id FROM greenhouses WHERE id = ? AND user_id = ?");
                $check->execute([$gh_id, $user_id]);
                if (!$check->fetch()) {
                    $error = 'Greenhouse tidak ditemukan.';
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE greenhouses SET name=?, location=?, latitude=?, longitude=?, 
                         device_code=?, firebase_key=?, notes=?, status=?
                         WHERE id = ? AND user_id = ?"
                    );
                    $stmt->execute([
                        $name,
                        $location ?: null,
                        $latitude  !== '' ? (float)$latitude  : null,
                        $longitude !== '' ? (float)$longitude : null,
                        $device_code  ?: null,
                        $firebase_key ?: null,
                        $notes ?: null,
                        $status,
                        $gh_id,
                        $user_id,
                    ]);

                    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                    $log->execute([$user_id, "Memperbarui data greenhouse: $name"]);
                    $message = "Greenhouse \"$name\" berhasil diperbarui!";
                }
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }

    // ── DELETE ────────────────────────────────────────────────
    elseif ($action === 'delete') {
        $gh_id = (int)($_POST['gh_id'] ?? 0);
        if ($gh_id > 0) {
            try {
                $check = $pdo->prepare("SELECT name FROM greenhouses WHERE id = ? AND user_id = ?");
                $check->execute([$gh_id, $user_id]);
                $ghRow = $check->fetch();
                if ($ghRow) {
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
                <h2>🏡 Greenhouse Saya</h2>
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
        <div class="glass-card" style="text-align:center;padding:60px 32px;">
            <i data-lucide="home" style="width:56px;height:56px;color:var(--accent-color);margin-bottom:16px;"></i>
            <h3 style="font-family:var(--font-heading);font-size:1.4rem;margin-bottom:8px;">Belum Ada Greenhouse</h3>
            <p style="color:var(--text-secondary);margin-bottom:24px;">Mulai dengan menambahkan greenhouse pertama Anda.</p>
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
                    <?php if ($gh['location']): ?>
                    <p style="color:var(--text-secondary);font-size:0.85rem;display:flex;align-items:center;gap:5px;margin-bottom:12px;">
                        <i data-lucide="map-pin" style="width:14px;height:14px;"></i>
                        <?php echo htmlspecialchars($gh['location']); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Device info chips -->
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
                        <?php if ($gh['device_code']): ?>
                        <span style="background:rgba(4,73,4,0.08);color:#166534;border:1px solid rgba(4,73,4,0.15);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:600;">
                            📟 <?php echo htmlspecialchars($gh['device_code']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($gh['firebase_key']): ?>
                        <span style="background:rgba(251,146,60,0.1);color:#c2410c;border:1px solid rgba(251,146,60,0.2);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:600;">
                            🔥 Firebase: <?php echo htmlspecialchars(substr($gh['firebase_key'], 0, 12)) . (strlen($gh['firebase_key']) > 12 ? '…' : ''); ?>
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
        <form method="POST">
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
        <form method="POST" id="form-edit">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="gh_id" id="edit-gh-id">
            <?php include '_greenhouse_form.php'; ?>
            <div style="margin-top:14px;">
                <label class="form-label">Status</label>
                <select name="status" id="edit-status" class="form-input">
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                    <option value="warning">Peringatan</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeModal('modal-edit')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

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
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
</style>

<script>
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
    lucide.createIcons();
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
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
    document.getElementById('edit-status').value        = gh.status      || 'active';
    openModal('modal-edit');
}
function confirmDelete(id, name) {
    document.getElementById('delete-gh-id').value   = id;
    document.getElementById('delete-gh-name').textContent = name;
    openModal('modal-delete');
}
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>

<?php
// Footer / JS includes
$custom_js = [];
include '../includes/footer.php';
?>
