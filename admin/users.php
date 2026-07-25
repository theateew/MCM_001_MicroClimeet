<?php
/**
 * admin/users.php — Manajemen Petani: Approve, Suspend, Edit, Delete, Search
 */
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$page_title    = "Manajemen Petani";
$custom_css    = ['dashboard.css'];
$base_url      = '../';
$active_menu   = 'admin_users';
$is_admin_area = true;
$message = $error = '';

// ── Handle GET: approve / suspend ─────────────────────────────
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $userId = (int)$_GET['id'];
    $status   = $action === 'approve' ? 'active' : 'inactive';
    $log_msg  = $action === 'approve'
        ? "Menyetujui pendaftaran petani ID $userId"
        : "Menonaktifkan akses petani ID $userId";
    try {
        $pdo->prepare("UPDATE users SET status=? WHERE id=? AND role='petani'")->execute([$status, $userId]);
        $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?,?)")->execute([$_SESSION['user_id'], $log_msg]);
        $message = "Status berhasil diperbarui!";
    } catch (PDOException $e) { $error = $e->getMessage(); }
}

// ── Handle POST: edit / delete ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pAction = $_POST['action'];

    if ($pAction === 'edit_user') {
        $uid   = (int)$_POST['user_id'];
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status= in_array($_POST['status'],['active','inactive']) ? $_POST['status'] : 'inactive';
        $newPass = trim($_POST['new_password'] ?? '');
        if (empty($name) || empty($email)) { $error = "Nama dan email wajib diisi."; }
        else {
            try {
                if (!empty($newPass)) {
                    $hash = password_hash($newPass, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET name=?,email=?,phone=?,status=?,password=? WHERE id=? AND role='petani'")
                        ->execute([$name,$email,$phone,$status,$hash,$uid]);
                } else {
                    $pdo->prepare("UPDATE users SET name=?,email=?,phone=?,status=? WHERE id=? AND role='petani'")
                        ->execute([$name,$email,$phone,$status,$uid]);
                }
                $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?,?)")
                    ->execute([$_SESSION['user_id'], "Admin mengedit data petani: $name (ID $uid)"]);
                $message = "Data petani berhasil diperbarui!";
            } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }

    elseif ($pAction === 'delete_user') {
        $uid = (int)$_POST['user_id'];
        try {
            $nameRow = $pdo->prepare("SELECT name FROM users WHERE id=? AND role='petani'");
            $nameRow->execute([$uid]);
            $uName = $nameRow->fetchColumn() ?: "ID $uid";
            $pdo->prepare("DELETE FROM users WHERE id=? AND role='petani'")->execute([$uid]);
            $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?,?)")
                ->execute([$_SESSION['user_id'], "Admin menghapus petani: $uName"]);
            $message = "Petani \"$uName\" berhasil dihapus.";
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
}

// ── Filter & Search ───────────────────────────────────────────
$search      = trim($_GET['q']      ?? '');
$statusFilter= in_array($_GET['status'] ?? '', ['active','inactive']) ? $_GET['status'] : '';

$conditions = ["role = 'petani'"];
$params     = [];
if ($search !== '') {
    $conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($statusFilter !== '') {
    $conditions[] = "status = ?"; $params[] = $statusFilter;
}
$where = implode(' AND ', $conditions);

$farmers = $pdo->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
$farmers->execute($params);
$farmers = $farmers->fetchAll();

// Summary counts
$total   = count($farmers);
$active  = count(array_filter($farmers, fn($f) => $f['status'] === 'active'));
$pending = $total - $active;

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2>👨‍🌾 Manajemen Petani</h2>
                <p>Kelola akun petani: persetujuan, edit data, dan penghapusan akun.</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?><div style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.3);border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#166534;display:flex;align-items:center;gap:10px;font-size:0.9rem;"><i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#b91c1c;display:flex;align-items:center;gap:10px;font-size:0.9rem;"><i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0;"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <!-- Summary + Search Bar -->
        <div style="display:flex;flex-wrap:wrap;gap:14px;margin-bottom:20px;align-items:center;">
            <div class="glass-card" style="padding:12px 20px;display:flex;align-items:center;gap:10px;min-width:150px;">
                <i data-lucide="users" style="width:20px;height:20px;color:var(--accent-color);"></i>
                <div><p style="font-size:1.5rem;font-weight:800;font-family:var(--font-heading);line-height:1;"><?php echo $total; ?></p><p style="font-size:0.75rem;color:var(--text-secondary);">Total Petani</p></div>
            </div>
            <div class="glass-card" style="padding:12px 20px;display:flex;align-items:center;gap:10px;min-width:150px;">
                <i data-lucide="user-check" style="width:20px;height:20px;color:#22c55e;"></i>
                <div><p style="font-size:1.5rem;font-weight:800;font-family:var(--font-heading);line-height:1;"><?php echo $active; ?></p><p style="font-size:0.75rem;color:var(--text-secondary);">Aktif</p></div>
            </div>
            <div class="glass-card" style="padding:12px 20px;display:flex;align-items:center;gap:10px;min-width:150px;">
                <i data-lucide="clock" style="width:20px;height:20px;color:#f59e0b;"></i>
                <div><p style="font-size:1.5rem;font-weight:800;font-family:var(--font-heading);line-height:1;"><?php echo $pending; ?></p><p style="font-size:0.75rem;color:var(--text-secondary);">Menunggu</p></div>
            </div>
            <form method="GET" style="display:flex;gap:8px;flex:1;min-width:260px;margin-left:auto;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Cari nama, email, telepon…"
                    style="flex:1;padding:8px 14px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;">
                <select name="status" style="padding:8px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;">
                    <option value="">Semua Status</option>
                    <option value="active"   <?php echo $statusFilter==='active'  ?'selected':''; ?>>Aktif</option>
                    <option value="inactive" <?php echo $statusFilter==='inactive'?'selected':''; ?>>Menunggu</option>
                </select>
                <button type="submit" class="btn btn-primary" style="padding:8px 16px;font-size:0.85rem;">Cari</button>
                <?php if ($search||$statusFilter): ?><a href="users.php" style="padding:8px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.83rem;color:var(--text-secondary);text-decoration:none;display:flex;align-items:center;">Reset</a><?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="glass-card" style="padding:0;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:rgba(0,0,0,0.03);">
                            <?php foreach(['Nama','Email','Telepon','Terdaftar','Status','Aksi'] as $h): ?>
                            <th style="text-align:left;padding:12px 16px;font-size:0.79rem;font-weight:700;color:var(--text-secondary);white-space:nowrap;"><?php echo $h; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($farmers)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-secondary);">Tidak ada petani ditemukan.</td></tr>
                        <?php else: ?>
                        <?php foreach ($farmers as $f): ?>
                        <tr style="border-top:1px solid rgba(0,0,0,0.05);">
                            <td style="padding:12px 16px;font-weight:600;font-size:0.87rem;"><?php echo htmlspecialchars($f['name']); ?></td>
                            <td style="padding:12px 16px;font-size:0.84rem;color:var(--text-secondary);"><?php echo htmlspecialchars($f['email']); ?></td>
                            <td style="padding:12px 16px;font-size:0.83rem;color:var(--text-secondary);"><?php echo htmlspecialchars($f['phone'] ?: '-'); ?></td>
                            <td style="padding:12px 16px;font-size:0.8rem;color:var(--text-secondary);white-space:nowrap;"><?php echo date('d M Y', strtotime($f['created_at'])); ?></td>
                            <td style="padding:12px 16px;">
                                <?php if ($f['status']==='active'): ?>
                                <span style="background:rgba(34,197,94,0.12);color:#15803d;border:1px solid rgba(34,197,94,0.3);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:700;">✓ Aktif</span>
                                <?php else: ?>
                                <span style="background:rgba(251,191,36,0.12);color:#92400e;border:1px solid rgba(251,191,36,0.3);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:700;">⏳ Menunggu</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px 16px;">
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <?php if ($f['status']==='active'): ?>
                                    <a href="users.php?action=suspend&id=<?php echo $f['id']; ?>"
                                       style="padding:5px 11px;font-size:0.77rem;border-radius:var(--radius-pill);border:1.5px solid rgba(251,146,60,0.35);color:#c2410c;background:transparent;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                                        <i data-lucide="user-x" style="width:12px;height:12px;"></i> Nonaktifkan
                                    </a>
                                    <?php else: ?>
                                    <a href="users.php?action=approve&id=<?php echo $f['id']; ?>"
                                       style="padding:5px 11px;font-size:0.77rem;border-radius:var(--radius-pill);background:var(--accent-color);color:#fff;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:4px;font-weight:700;">
                                        <i data-lucide="user-check" style="width:12px;height:12px;"></i> Setujui
                                    </a>
                                    <?php endif; ?>
                                    <button onclick='openEditUser(<?php echo htmlspecialchars(json_encode($f)); ?>)'
                                        style="padding:5px 11px;font-size:0.77rem;border-radius:var(--radius-pill);border:1.5px solid rgba(0,0,0,0.12);color:var(--text-primary);background:transparent;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                                        <i data-lucide="edit-2" style="width:12px;height:12px;"></i> Edit
                                    </button>
                                    <button onclick="confirmDeleteUser(<?php echo $f['id']; ?>, '<?php echo htmlspecialchars(addslashes($f['name'])); ?>')"
                                        style="padding:5px 9px;font-size:0.77rem;border-radius:var(--radius-pill);border:1.5px solid rgba(239,68,68,0.3);color:#ef4444;background:transparent;cursor:pointer;">
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

<!-- Modal Edit User -->
<div id="modal-edit-user" class="modal-overlay" style="display:none;" onclick="closeModal('modal-edit-user')">
    <div class="modal-box glass-card" onclick="event.stopPropagation()" style="max-width:480px;">
        <div class="modal-header">
            <h3><i data-lucide="edit" style="width:18px;height:18px;"></i> Edit Data Petani</h3>
            <button onclick="closeModal('modal-edit-user')" class="modal-close">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="eu-user-id">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="name" id="eu-name" class="form-input" required>
            <label class="form-label" style="margin-top:12px;">Email</label>
            <input type="email" name="email" id="eu-email" class="form-input" required>
            <label class="form-label" style="margin-top:12px;">No. Telepon</label>
            <input type="text" name="phone" id="eu-phone" class="form-input">
            <label class="form-label" style="margin-top:12px;">Status</label>
            <select name="status" id="eu-status" class="form-input">
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>
            <label class="form-label" style="margin-top:12px;">Password Baru <small style="font-weight:400;color:var(--text-secondary);">(kosongkan jika tidak diubah)</small></label>
            <input type="password" name="new_password" class="form-input" placeholder="••••••••">
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeModal('modal-edit-user')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Delete User -->
<div id="modal-delete-user" class="modal-overlay" style="display:none;" onclick="closeModal('modal-delete-user')">
    <div class="modal-box glass-card" style="max-width:400px;" onclick="event.stopPropagation()">
        <div style="text-align:center;padding:8px 0 20px;">
            <i data-lucide="user-x" style="width:48px;height:48px;color:#ef4444;margin-bottom:12px;"></i>
            <h3 style="font-family:var(--font-heading);">Hapus Akun Petani?</h3>
            <p style="color:var(--text-secondary);font-size:0.88rem;margin-top:8px;">
                Akun "<strong id="del-user-name"></strong>" beserta seluruh data greenhouse, riwayat sensor, dan peringatan akan dihapus permanen.
            </p>
        </div>
        <form method="POST" style="display:flex;gap:10px;justify-content:center;">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" id="del-user-id">
            <button type="button" onclick="closeModal('modal-delete-user')" class="btn btn-secondary">Batal</button>
            <button type="submit" style="background:#ef4444;color:#fff;border:none;padding:9px 24px;border-radius:var(--radius-pill);font-weight:700;cursor:pointer;">Hapus Permanen</button>
        </form>
    </div>
</div>

<style>
.modal-overlay { position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.45);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:16px; }
.modal-box { width:100%;max-width:540px;max-height:92vh;overflow-y:auto;padding:28px;border-radius:var(--radius-xl);animation:modalIn 0.3s cubic-bezier(0.16,1,0.3,1); }
@keyframes modalIn { from{opacity:0;transform:translateY(18px) scale(0.97)} to{opacity:1;transform:translateY(0) scale(1)} }
.modal-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:18px; }
.modal-header h3 { font-family:var(--font-heading);font-size:1.05rem;font-weight:700;display:flex;align-items:center;gap:8px; }
.modal-close { background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--text-secondary);padding:2px 6px;border-radius:6px; }
.form-label { display:block;font-weight:600;font-size:0.81rem;color:var(--text-secondary);margin-bottom:4px; }
.form-input { width:100%;padding:9px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);font-size:0.88rem;box-sizing:border-box; }
.form-input:focus { border-color:var(--accent-color);outline:none; }
</style>
<script>
function openModal(id)  { document.getElementById(id).style.display='flex'; lucide.createIcons(); }
function closeModal(id) { document.getElementById(id).style.display='none'; }
function openEditUser(u) {
    document.getElementById('eu-user-id').value = u.id;
    document.getElementById('eu-name').value    = u.name   || '';
    document.getElementById('eu-email').value   = u.email  || '';
    document.getElementById('eu-phone').value   = u.phone  || '';
    document.getElementById('eu-status').value  = u.status || 'inactive';
    openModal('modal-edit-user');
}
function confirmDeleteUser(id, name) {
    document.getElementById('del-user-id').value = id;
    document.getElementById('del-user-name').textContent = name;
    openModal('modal-delete-user');
}
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
<?php include '../includes/footer.php'; ?>
