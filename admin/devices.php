<?php
/**
 * admin/devices.php — CRUD Semua Greenhouse / Perangkat IoT
 */
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$page_title    = 'Kelola Perangkat';
$custom_css    = ['dashboard.css'];
$base_url      = '../';
$active_menu   = 'admin_devices';
$is_admin_area = true;
$message = $error = '';

// ── UPDATE status / device info ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'register_device') {
        $deviceCode = trim($_POST['device_code'] ?? '');
        $farmerId   = (int)($_POST['farmer_id'] ?? 0);

        if (empty($deviceCode)) {
            $error = "Kode Perangkat wajib diisi!";
        } else {
            try {
                // Check if device_code already exists in devices
                $c1 = $pdo->prepare("SELECT id FROM devices WHERE device_code = ?");
                $c1->execute([$deviceCode]);
                if ($c1->fetch()) {
                    $error = "⚠️ Kode Perangkat '$deviceCode' sudah terdaftar di inventaris Admin!";
                } else {
                    $gh_id = null;
                    if ($farmerId > 0) {
                        $ghc = $pdo->prepare("SELECT id FROM greenhouses WHERE user_id = ? AND (device_code IS NULL OR device_code = '') LIMIT 1");
                        $ghc->execute([$farmerId]);
                        $gh_id = $ghc->fetchColumn();

                        if ($gh_id) {
                            $pdo->prepare("UPDATE greenhouses SET device_code = ?, status = 'active' WHERE id = ?")
                                ->execute([$deviceCode, $gh_id]);
                        } else {
                            $pdo->prepare("INSERT INTO greenhouses (user_id, name, commodity, device_code, status) VALUES (?, 'Greenhouse Utama', 'Melon Premium', ?, 'active')")
                                ->execute([$farmerId, $deviceCode]);
                            $gh_id = $pdo->lastInsertId();
                        }
                    }

                    $pdo->prepare("INSERT INTO devices (greenhouse_id, device_code, status, created_at) VALUES (?, ?, 'online', NOW())")
                        ->execute([$gh_id ?: null, $deviceCode]);

                    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                    $log->execute([$_SESSION['user_id'], "Admin mendaftarkan Kode Perangkat Hardware baru '$deviceCode'"]);

                    $message = "Kode Perangkat '$deviceCode' berhasil didaftarkan ke inventaris!";
                }
            } catch (PDOException $e) {
                $error = "Gagal mendaftarkan perangkat: " . $e->getMessage();
            }
        }
    }

    elseif ($action === 'update_device') {
        $id           = (int)$_POST['gh_id'];
        $device_code  = trim($_POST['device_code']  ?? '');
        $firebase_key = trim($_POST['firebase_key'] ?? '');
        $status       = in_array($_POST['status'], ['active','inactive','warning']) ? $_POST['status'] : 'active';
        try {
            $pdo->prepare(
                "UPDATE greenhouses SET device_code=?, firebase_key=?, status=? WHERE id=?"
            )->execute([$device_code ?: null, $firebase_key ?: null, $status, $id]);
            $log = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
            $log->execute([$_SESSION['user_id'], "Admin memperbarui perangkat greenhouse ID $id"]);
            $message = "Perangkat berhasil diperbarui!";
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }

    elseif ($action === 'delete_device') {
        $id = (int)$_POST['gh_id'];
        try {
            $nameRow = $pdo->prepare("SELECT name FROM greenhouses WHERE id=?");
            $nameRow->execute([$id]);
            $ghName = $nameRow->fetchColumn() ?: "ID $id";
            $pdo->prepare("DELETE FROM greenhouses WHERE id=?")->execute([$id]);
            $log = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
            $log->execute([$_SESSION['user_id'], "Admin menghapus greenhouse: $ghName"]);
            $message = "Greenhouse \"$ghName\" berhasil dihapus.";
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
}

// ── Filters ───────────────────────────────────────────────────
$statusFilter = $_GET['status'] ?? '';
$farmerFilter = (int)($_GET['user_id'] ?? 0);
$search       = trim($_GET['q'] ?? '');

$conditions = ["g.device_code IS NOT NULL AND g.device_code != ''"];
$params = [];
if (in_array($statusFilter, ['active','inactive','warning'])) {
    $conditions[] = 'g.status = ?'; $params[] = $statusFilter;
}
if ($farmerFilter > 0) {
    $conditions[] = 'g.user_id = ?'; $params[] = $farmerFilter;
}
if ($search !== '') {
    $conditions[] = '(g.name LIKE ? OR g.device_code LIKE ? OR u.name LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$where = implode(' AND ', $conditions);

// Devices list
$devStmt = $pdo->prepare(
    "SELECT g.*, u.name AS farmer_name, u.phone AS farmer_phone
     FROM greenhouses g
     LEFT JOIN users u ON g.user_id = u.id
     WHERE $where
     ORDER BY g.created_at DESC"
);
$devStmt->execute($params);
$devices = $devStmt->fetchAll();

// All farmers for filter
$farmers = $pdo->query("SELECT id, name FROM users WHERE role='petani' ORDER BY name")->fetchAll();

// Stats for active hardware devices only
$statsStmt = $pdo->query(
    "SELECT status, COUNT(*) AS cnt FROM greenhouses WHERE device_code IS NOT NULL AND device_code != '' GROUP BY status"
);
$statusStats = [];
foreach ($statsStmt->fetchAll() as $r) { $statusStats[$r['status']] = $r['cnt']; }

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
            <div class="welcome-msg">
                <h2><i data-lucide="cpu" style="color:#0D5D31;"></i> Kelola Perangkat</h2>
                <p>Pantau dan kelola semua perangkat IoT greenhouse dari seluruh petani.</p>
            </div>
            <button onclick="document.getElementById('modal-register-device').style.display='flex'" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:12px;font-weight:700;background:#0D5D31;color:#fff;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(13,93,49,0.25);">
                <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
                + Registrasi Perangkat IoT Baru
            </button>
        </div>

        <?php if ($message): ?><div style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.3);border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#166534;display:flex;align-items:center;gap:10px;"><i data-lucide="check-circle" style="width:17px;height:17px;flex-shrink:0;"></i><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#b91c1c;display:flex;align-items:center;gap:10px;"><i data-lucide="alert-circle" style="width:17px;height:17px;flex-shrink:0;"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <!-- Status Cards -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px;">
            <?php
            $cards = [
                ['Aktif',       $statusStats['active']   ?? 0, '#22c55e', 'check-circle'],
                ['Peringatan',  $statusStats['warning']  ?? 0, '#f59e0b', 'alert-triangle'],
                ['Tidak Aktif', $statusStats['inactive'] ?? 0, '#9ca3af', 'wifi-off'],
            ];
            foreach ($cards as [$lbl, $cnt, $color, $icon]): ?>
            <div class="glass-card" style="padding:16px 20px;display:flex;align-items:center;gap:14px;">
                <div style="width:42px;height:42px;border-radius:50%;background:<?php echo $color; ?>22;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="<?php echo $icon; ?>" style="width:20px;height:20px;color:<?php echo $color; ?>;"></i>
                </div>
                <div>
                    <p style="font-size:1.6rem;font-family:var(--font-heading);font-weight:800;line-height:1;"><?php echo $cnt; ?></p>
                    <p style="font-size:0.78rem;color:var(--text-secondary);"><?php echo $lbl; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div class="glass-card" style="padding:14px 18px;margin-bottom:18px;">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Cari nama, kode perangkat, petani…"
                    style="flex:1;min-width:200px;padding:7px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;">
                <select name="status" style="padding:7px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;">
                    <option value="">Semua Status</option>
                    <option value="active"   <?php echo $statusFilter==='active'  ?'selected':''; ?>>Aktif</option>
                    <option value="warning"  <?php echo $statusFilter==='warning' ?'selected':''; ?>>Peringatan</option>
                    <option value="inactive" <?php echo $statusFilter==='inactive'?'selected':''; ?>>Tidak Aktif</option>
                </select>
                <select name="user_id" style="padding:7px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;">
                    <option value="0">Semua Petani</option>
                    <?php foreach ($farmers as $f): ?>
                    <option value="<?php echo $f['id']; ?>" <?php echo $farmerFilter==$f['id']?'selected':''; ?>>
                        <?php echo htmlspecialchars($f['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary" style="padding:7px 18px;font-size:0.85rem;">Filter</button>
                <a href="devices.php" style="font-size:0.83rem;color:var(--text-secondary);text-decoration:none;padding:7px 12px;">Reset</a>
                <span style="margin-left:auto;font-size:0.82rem;color:var(--text-secondary);"><?php echo count($devices); ?> perangkat</span>
            </form>
        </div>

        <!-- Devices Table -->
        <div class="glass-card" style="padding:0;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:rgba(0,0,0,0.03);">
                            <?php foreach(['Greenhouse','Petani','Kode Perangkat','Firebase Key','Lokasi','Status','Aksi'] as $h): ?>
                            <th style="text-align:left;padding:12px 16px;font-size:0.79rem;font-weight:700;color:var(--text-secondary);white-space:nowrap;"><?php echo $h; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($devices)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-secondary);">Tidak ada data perangkat.</td></tr>
                        <?php else: ?>
                        <?php foreach ($devices as $d): ?>
                        <?php
                            $sc = ['active'=>'#22c55e','inactive'=>'#9ca3af','warning'=>'#f59e0b'];
                            $statusColor = $sc[$d['status']] ?? '#9ca3af';
                        ?>
                        <tr style="border-top:1px solid rgba(0,0,0,0.05);" id="row-<?php echo $d['id']; ?>">
                            <td style="padding:12px 16px;font-weight:600;font-size:0.87rem;"><?php echo htmlspecialchars($d['name']); ?></td>
                            <td style="padding:12px 16px;font-size:0.85rem;"><?php echo htmlspecialchars($d['farmer_name']); ?></td>
                            <td style="padding:12px 16px;font-size:0.82rem;font-family:monospace;">
                                <?php echo $d['device_code'] ? htmlspecialchars($d['device_code']) : '<span style="color:var(--text-secondary);">-</span>'; ?>
                            </td>
                            <td style="padding:12px 16px;font-size:0.8rem;font-family:monospace;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?php echo $d['firebase_key'] ? htmlspecialchars($d['firebase_key']) : '<span style="color:var(--text-secondary);">-</span>'; ?>
                            </td>
                            <td style="padding:12px 16px;font-size:0.82rem;">
                                <?php if ($d['latitude'] && $d['longitude']): ?>
                                <a href="https://maps.google.com/?q=<?php echo $d['latitude']; ?>,<?php echo $d['longitude']; ?>"
                                   target="_blank" style="color:var(--accent-color);text-decoration:none;display:flex;align-items:center;gap:4px;">
                                    <i data-lucide="map-pin" style="width:13px;height:13px;"></i>
                                    <?php echo number_format((float)$d['latitude'], 4); ?>, <?php echo number_format((float)$d['longitude'], 4); ?>
                                </a>
                                <?php else: echo '<span style="color:var(--text-secondary);">-</span>'; endif; ?>
                            </td>
                            <td style="padding:12px 16px;">
                                <span style="display:inline-flex;align-items:center;gap:5px;background:<?php echo $statusColor; ?>18;color:<?php echo $statusColor; ?>;border:1px solid <?php echo $statusColor; ?>33;border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:700;">
                                    <span style="width:6px;height:6px;border-radius:50%;background:<?php echo $statusColor; ?>;"></span>
                                    <?php echo ucfirst($d['status']); ?>
                                </span>
                            </td>
                            <td style="padding:12px 16px;">
                                <div style="display:flex;gap:6px;">
                                    <button onclick='openDeviceEditModal(<?php echo htmlspecialchars(json_encode($d)); ?>)'
                                        style="padding:5px 12px;font-size:0.78rem;border-radius:var(--radius-pill);border:1.5px solid var(--accent-color);color:var(--accent-dark);background:transparent;cursor:pointer;display:flex;align-items:center;gap:4px;">
                                        <i data-lucide="edit-2" style="width:12px;height:12px;"></i> Edit
                                    </button>
                                    <button onclick="confirmDeviceDelete(<?php echo $d['id']; ?>, '<?php echo htmlspecialchars(addslashes($d['name'])); ?>')"
                                        style="padding:5px 10px;font-size:0.78rem;border-radius:var(--radius-pill);border:1.5px solid rgba(239,68,68,0.3);color:#ef4444;background:transparent;cursor:pointer;">
                                        <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal Edit Device -->
<div id="modal-device-edit" class="modal-overlay" style="display:none;" onclick="closeModal('modal-device-edit')">
    <div class="modal-box glass-card" onclick="event.stopPropagation()" style="max-width:480px;">
        <div class="modal-header">
            <h3><i data-lucide="cpu" style="width:18px;height:18px;"></i> Edit Perangkat</h3>
            <button onclick="closeModal('modal-device-edit')" class="modal-close">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_device">
            <input type="hidden" name="gh_id" id="dev-edit-id">
            <p id="dev-edit-name" style="font-weight:700;font-size:1rem;margin-bottom:16px;color:var(--accent-dark);"></p>
            <label class="form-label">Kode Perangkat IoT</label>
            <input type="text" name="device_code" id="dev-device-code" class="form-input" placeholder="MCM-001">
            <label class="form-label" style="margin-top:12px;">Firebase Node Key</label>
            <input type="text" name="firebase_key" id="dev-firebase-key" class="form-input" placeholder="sensors/greenhouse_01">
            <label class="form-label" style="margin-top:12px;">Status</label>
            <select name="status" id="dev-status" class="form-input">
                <option value="active">Aktif</option>
                <option value="inactive">Tidak Aktif</option>
                <option value="warning">Peringatan</option>
            </select>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeModal('modal-device-edit')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL: Registrasi Perangkat Baru (Manual Input) ────────────────────── -->
<div id="modal-register-device" class="modal-overlay" style="display:none;" onclick="closeModal('modal-register-device')">
    <div class="modal-box glass-card" style="max-width:480px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i data-lucide="cpu" style="width:20px;height:20px;color:#0D5D31;"></i> Registrasi Perangkat IoT Hardware</h3>
            <button onclick="closeModal('modal-register-device')" class="modal-close">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="register_device">
            <div style="margin-bottom:14px;">
                <label class="form-label">
                    Kode Akses Perangkat Hardware <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="device_code" required placeholder="mis. ESP32_GH_002 / MCM-2026-002" class="form-input"
                       style="font-family:monospace;font-weight:700;font-size:0.95rem;">
                <p style="font-size:0.75rem;color:var(--text-secondary);margin-top:4px;">
                    Masukkan Kode Seri Fisik yang diflash pada mikrokontroler ESP32.
                </p>
            </div>
            <div style="margin-bottom:18px;">
                <label class="form-label">
                    Alokasikan Langsung ke Petani (Opsional)
                </label>
                <select name="farmer_id" class="form-input">
                    <option value="0">-- Belum Dialokasikan (Petani Klaim Mandiri) --</option>
                    <?php foreach ($farmers as $f): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeModal('modal-register-device')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary" style="background:#0D5D31!important;color:#fff!important;font-weight:800;border:none;padding:10px 20px;border-radius:12px;cursor:pointer;">
                    Simpan & Daftarkan Alat
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Delete Device -->
<div id="modal-device-delete" class="modal-overlay" style="display:none;" onclick="closeModal('modal-device-delete')">
    <div class="modal-box glass-card" style="max-width:400px;" onclick="event.stopPropagation()">
        <div style="text-align:center;padding:8px 0 20px;">
            <i data-lucide="trash-2" style="width:48px;height:48px;color:#ef4444;margin-bottom:12px;"></i>
            <h3 style="font-family:var(--font-heading);">Hapus Perangkat?</h3>
            <p style="color:var(--text-secondary);font-size:0.88rem;margin-top:6px;">
                "<strong id="del-device-name"></strong>" dan semua data terkait (riwayat + peringatan) akan dihapus.
            </p>
        </div>
        <form method="POST" style="display:flex;gap:10px;justify-content:center;">
            <input type="hidden" name="action" value="delete_device">
            <input type="hidden" name="gh_id" id="del-device-id">
            <button type="button" onclick="closeModal('modal-device-delete')" class="btn btn-secondary">Batal</button>
            <button type="submit" style="background:#ef4444;color:#fff;border:none;padding:9px 24px;border-radius:var(--radius-pill);font-weight:700;cursor:pointer;">Hapus</button>
        </form>
    </div>
</div>

<style>
.modal-overlay { position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.45);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:16px; }
.modal-box { width:100%;max-width:540px;max-height:92vh;overflow-y:auto;padding:28px;border-radius:var(--radius-xl);animation:modalIn 0.3s cubic-bezier(0.16,1,0.3,1); }
@keyframes modalIn { from{opacity:0;transform:translateY(20px) scale(0.97)} to{opacity:1;transform:translateY(0) scale(1)} }
.modal-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:20px; }
.modal-header h3 { font-family:var(--font-heading);font-size:1.05rem;font-weight:700;display:flex;align-items:center;gap:8px; }
.modal-close { background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--text-secondary);padding:2px 6px;border-radius:6px; }
.form-label { display:block;font-weight:600;font-size:0.82rem;color:var(--text-secondary);margin-bottom:4px; }
.form-input { width:100%;padding:9px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);font-size:0.88rem;box-sizing:border-box; }
.form-input:focus { border-color:var(--accent-color);outline:none; }
</style>

<script>
function openModal(id)  { document.getElementById(id).style.display = 'flex'; lucide.createIcons(); }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function openDeviceEditModal(d) {
    document.getElementById('dev-edit-id').value     = d.id;
    document.getElementById('dev-edit-name').textContent = d.name;
    document.getElementById('dev-device-code').value = d.device_code  || '';
    document.getElementById('dev-firebase-key').value= d.firebase_key || '';
    document.getElementById('dev-status').value      = d.status        || 'active';
    openModal('modal-device-edit');
}
function confirmDeviceDelete(id, name) {
    document.getElementById('del-device-id').value = id;
    document.getElementById('del-device-name').textContent = name;
    openModal('modal-device-delete');
}
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
<?php include '../includes/footer.php'; ?>
