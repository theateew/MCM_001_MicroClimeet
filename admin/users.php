<?php

/**
 * admin/users.php — Manajemen Petani: Approve, Suspend/Blokir, Reset Password, Activity Logs
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

// ── Handle GET: approve / suspend / activate ─────────────────
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $userId = (int)$_GET['id'];
    $newStatus = 'inactive';
    $log_msg   = '';

    if ($action === 'approve' || $action === 'activate') {
        $newStatus = 'active';
        $log_msg   = "Menyetujui/Mengaktifkan kembali akses petani ID $userId";
    } elseif ($action === 'suspend') {
        $newStatus = 'suspended';
        $log_msg   = "Menangguhkan/Blokir sementara akun petani ID $userId";
    }

    try {
        $pdo->prepare("UPDATE users SET status=? WHERE id=? AND role='petani'")->execute([$newStatus, $userId]);
        $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?,?)")->execute([$_SESSION['user_id'], $log_msg]);
        $message = "Status akun petani berhasil diperbarui!";
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

// ── Handle POST: edit / reset password / delete ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pAction = $_POST['action'];

    if ($pAction === 'edit_user') {
        $uid   = (int)$_POST['user_id'];
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = in_array($_POST['status'], ['active', 'inactive', 'suspended']) ? $_POST['status'] : 'inactive';
        $newPass = trim($_POST['new_password'] ?? '');

        if (empty($name) || empty($email)) {
            $error = "Nama dan email wajib diisi.";
        } else {
            try {
                if (!empty($newPass)) {
                    $hash = password_hash($newPass, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET name=?,email=?,phone=?,status=?,password=? WHERE id=? AND role='petani'")
                        ->execute([$name, $email, $phone, $status, $hash, $uid]);
                } else {
                    $pdo->prepare("UPDATE users SET name=?,email=?,phone=?,status=? WHERE id=? AND role='petani'")
                        ->execute([$name, $email, $phone, $status, $uid]);
                }
                $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?,?)")
                    ->execute([$_SESSION['user_id'], "Admin memperbarui profil petani: $name (ID $uid)"]);
                $message = "Data petani berhasil diperbarui!";
            } catch (PDOException $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($pAction === 'reset_password') {
        $uid     = (int)$_POST['user_id'];
        $newPass = trim($_POST['reset_new_password'] ?? '');
        if (strlen($newPass) < 6) {
            $error = "Password baru minimal 6 karakter.";
        } else {
            try {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password=? WHERE id=? AND role='petani'")->execute([$hash, $uid]);
                $uName = $pdo->query("SELECT name FROM users WHERE id=$uid")->fetchColumn() ?: "ID $uid";
                $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?,?)")
                    ->execute([$_SESSION['user_id'], "Admin mereset password akun petani '$uName' (ID $uid)"]);
                $message = "Password akun petani \"$uName\" berhasil direset!";
            } catch (PDOException $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($pAction === 'delete_user') {
        $uid = (int)$_POST['user_id'];
        try {
            require_once '../config/firebase_helper.php';
            $uRow = $pdo->prepare("SELECT name, email, password, google_id FROM users WHERE id=? AND role='petani'");
            $uRow->execute([$uid]);
            $uData = $uRow->fetch();

            if ($uData) {
                $uName  = $uData['name'];
                $uEmail = $uData['email'];
                // Delete user from Firebase Auth
                firebaseDeleteUserByEmailAndPassword($uEmail, $uData['password']);

                // Delete from MySQL users table
                $pdo->prepare("DELETE FROM users WHERE id=? AND role='petani'")->execute([$uid]);
                $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?,?)")
                    ->execute([$_SESSION['user_id'], "Admin menghapus permanen akun petani & Firebase Auth: $uName ($uEmail)"]);
                $message = "Petani \"$uName\" berhasil dihapus dari sistem dan Firebase Auth.";
            }
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    } elseif ($pAction === 'assign_device') {
        $uid        = (int)$_POST['user_id'];
        $deviceCode = trim($_POST['device_code'] ?? '');
        $ghName     = trim($_POST['greenhouse_name'] ?? 'Greenhouse Utama');

        if (empty($deviceCode)) {
            $error = "Kode / ID Perangkat wajib diisi!";
        } else {
            try {
                // Check if device_code is already assigned to another farmer
                $dupCheck = $pdo->prepare("SELECT u.name FROM greenhouses g JOIN users u ON g.user_id = u.id WHERE g.device_code = ? AND g.user_id != ?");
                $dupCheck->execute([$deviceCode, $uid]);
                $existingOwner = $dupCheck->fetchColumn();

                if ($existingOwner) {
                    $error = "⚠️ Kode Akses Perangkat '$deviceCode' sudah terhubung dengan petani lain ($existingOwner)! Satu perangkat keras hanya beralokasi untuk 1 petani.";
                } else {
                    // Check if user already has a greenhouse
                    $ghCheck = $pdo->prepare("SELECT id FROM greenhouses WHERE user_id = ? LIMIT 1");
                    $ghCheck->execute([$uid]);
                    $ghId = $ghCheck->fetchColumn();

                    if ($ghId) {
                        $pdo->prepare("UPDATE greenhouses SET device_code = ?, status = 'active' WHERE id = ?")
                            ->execute([$deviceCode, $ghId]);
                    } else {
                        $pdo->prepare("INSERT INTO greenhouses (user_id, name, device_code, status) VALUES (?, ?, ?, 'active')")
                            ->execute([$uid, $ghName, $deviceCode]);
                    }

                    $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?,?)")
                        ->execute([$_SESSION['user_id'], "Admin mengalokasikan ID Perangkat '$deviceCode' ke petani ID $uid"]);
                    $message = "Kode Perangkat '$deviceCode' berhasil diberikan ke akun petani!";
                }
            } catch (PDOException $e) {
                $error = "Gagal mengalokasikan perangkat: " . $e->getMessage();
            }
        }
    }
}

// ── Filter & Search ───────────────────────────────────────────
$search       = trim($_GET['q'] ?? '');
$statusFilter = in_array($_GET['status'] ?? '', ['active', 'inactive', 'suspended']) ? $_GET['status'] : '';

$conditions = ["u.role = 'petani'"];
$params     = [];
if ($search !== '') {
    $conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusFilter !== '') {
    $conditions[] = "u.status = ?";
    $params[] = $statusFilter;
}
$where = implode(' AND ', $conditions);

$farmers = $pdo->prepare("
    SELECT u.*, 
           GROUP_CONCAT(DISTINCT g.device_code SEPARATOR ', ') AS device_codes,
           COUNT(DISTINCT g.id) AS total_devices
    FROM users u
    LEFT JOIN greenhouses g ON g.user_id = u.id AND g.device_code IS NOT NULL AND g.device_code != ''
    WHERE $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$farmers->execute($params);
$farmers = $farmers->fetchAll();

// Summary counts
$totalStats = ['total' => 0, 'active' => 0, 'pending' => 0, 'suspended' => 0];
try {
    $sRows = $pdo->query("SELECT status, COUNT(*) AS cnt FROM users WHERE role='petani' GROUP BY status")->fetchAll();
    foreach ($sRows as $r) {
        $totalStats['total'] += $r['cnt'];
        if ($r['status'] === 'active')    $totalStats['active']    = $r['cnt'];
        if ($r['status'] === 'inactive')  $totalStats['pending']   = $r['cnt'];
        if ($r['status'] === 'suspended') $totalStats['suspended'] = $r['cnt'];
    }
} catch (PDOException $e) {
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2><i data-lucide="users" style="color:#0D5D31;"></i> Manajemen Akun Petani</h2>
                <p>Kelola persetujuan registrasi, atur status (aktif/suspend), reset password, alokasi perangkat IoT, dan pantau log aktivitas per-petani.</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?><div style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.3);border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#15803d;display:flex;align-items:center;gap:10px;font-size:0.9rem;font-weight:700;"><i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:12px 18px;margin-bottom:18px;color:#b91c1c;display:flex;align-items:center;gap:10px;font-size:0.9rem;"><i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0;"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <!-- Summary + Search Bar -->
        <div style="display:flex;flex-wrap:wrap;gap:14px;margin-bottom:20px;align-items:center;">
            <div class="glass-card" style="padding:12px 18px;display:flex;align-items:center;gap:10px;min-width:140px;">
                <i data-lucide="users" style="width:20px;height:20px;color:var(--accent-color);"></i>
                <div>
                    <p style="font-size:1.4rem;font-weight:800;font-family:var(--font-heading);line-height:1;"><?php echo $totalStats['total']; ?></p>
                    <p style="font-size:0.75rem;color:var(--text-secondary);">Total Petani</p>
                </div>
            </div>
            <div class="glass-card" style="padding:12px 18px;display:flex;align-items:center;gap:10px;min-width:140px;">
                <i data-lucide="user-check" style="width:20px;height:20px;color:#22c55e;"></i>
                <div>
                    <p style="font-size:1.4rem;font-weight:800;font-family:var(--font-heading);line-height:1;"><?php echo $totalStats['active']; ?></p>
                    <p style="font-size:0.75rem;color:var(--text-secondary);">Aktif</p>
                </div>
            </div>
            <div class="glass-card" style="padding:12px 18px;display:flex;align-items:center;gap:10px;min-width:140px;">
                <i data-lucide="clock" style="width:20px;height:20px;color:#f59e0b;"></i>
                <div>
                    <p style="font-size:1.4rem;font-weight:800;font-family:var(--font-heading);line-height:1;"><?php echo $totalStats['pending']; ?></p>
                    <p style="font-size:0.75rem;color:var(--text-secondary);">Menunggu</p>
                </div>
            </div>
            <div class="glass-card" style="padding:12px 18px;display:flex;align-items:center;gap:10px;min-width:140px;">
                <i data-lucide="user-x" style="width:20px;height:20px;color:#ef4444;"></i>
                <div>
                    <p style="font-size:1.4rem;font-weight:800;font-family:var(--font-heading);line-height:1;"><?php echo $totalStats['suspended']; ?></p>
                    <p style="font-size:0.75rem;color:var(--text-secondary);">Ditangguhkan</p>
                </div>
            </div>

            <form method="GET" style="display:flex;gap:8px;flex:1;min-width:260px;margin-left:auto;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Cari nama, email, telepon…"
                    style="flex:1;padding:8px 14px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;">
                <select name="status" style="padding:8px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.85rem;">
                    <option value="">Semua Status</option>
                    <option value="active" <?php echo $statusFilter === 'active'   ? 'selected' : ''; ?>>Aktif</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Menunggu Approval</option>
                    <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Ditangguhkan</option>
                </select>
                <button type="submit" class="btn btn-primary" style="padding:8px 16px;font-size:0.85rem;">Cari</button>
                <?php if ($search || $statusFilter): ?><a href="users.php" style="padding:8px 12px;border-radius:10px;border:1.5px solid rgba(0,0,0,0.1);font-size:0.83rem;color:var(--text-secondary);text-decoration:none;display:flex;align-items:center;">Reset</a><?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="glass-card" style="padding:0;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:rgba(0,0,0,0.03);">
                            <?php foreach (['Nama Petani', 'Email / Auth', 'No. Telepon', 'Perangkat IoT', 'Status', 'Aksi'] as $h): ?>
                                <th style="text-align:left;padding:12px 16px;font-size:0.79rem;font-weight:700;color:var(--text-secondary);white-space:nowrap;"><?php echo $h; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($farmers)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:40px;color:var(--text-secondary);">Tidak ada petani ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($farmers as $f): ?>
                                <tr style="border-top:1px solid rgba(0,0,0,0.05);">
                                    <td style="padding:12px 16px;font-weight:600;font-size:0.87rem;"><?php echo htmlspecialchars($f['name']); ?></td>
                                    <td style="padding:12px 16px;font-size:0.84rem;color:var(--text-secondary);">
                                        <div><?php echo htmlspecialchars($f['email']); ?></div>
                                        <?php if (!empty($f['google_id'])): ?>
                                            <span style="display:inline-flex;align-items:center;margin-top:2px;background:#e8f0fe;color:#1a73e8;border:1px solid #aecbfa;border-radius:12px;padding:2px 8px;font-size:0.7rem;font-weight:700;">
                                                <svg width="12" height="12" viewBox="0 0 24 24" style="margin-right:4px;flex-shrink:0;">
                                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                                    <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z" />
                                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
                                                </svg>
                                                Google OAuth
                                            </span>
                                        <?php else: ?>
                                            <span style="display:inline-block;margin-top:2px;background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;border-radius:12px;padding:1px 8px;font-size:0.7rem;font-weight:700;">Email Biasa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px 16px;font-size:0.83rem;color:var(--text-secondary);"><?php echo htmlspecialchars($f['phone'] ?: '-'); ?></td>
                                    <td style="padding:12px 16px;font-size:0.82rem;">
                                        <?php if (!empty($f['device_codes'])): ?>
                                            <span style="background:rgba(13,93,49,0.1);color:#0D5D31;border:1px solid rgba(13,93,49,0.25);border-radius:12px;padding:2px 8px;font-size:0.75rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                                                <i data-lucide="smartphone" style="width:12px;height:12px;"></i> <?php echo htmlspecialchars($f['device_codes']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="background:rgba(239,68,68,0.08);color:#c93535;border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:2px 8px;font-size:0.75rem;font-weight:700;display:inline-block;">
                                                Belum Berlangganan
                                            </span>
                                        <?php endif; ?>
                                        <div style="margin-top:4px;">
                                            <button onclick="openAssignDeviceModal(<?php echo $f['id']; ?>, '<?php echo htmlspecialchars(addslashes($f['name'])); ?>')" style="background:none;border:none;color:#0D5D31;font-size:0.73rem;font-weight:700;cursor:pointer;padding:0;text-decoration:underline;">
                                                + Set Kode Akses
                                            </button>
                                        </div>
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <?php if ($f['status'] === 'active'): ?>
                                            <span style="background:rgba(34,197,94,0.12);color:#15803d;border:1px solid rgba(34,197,94,0.3);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:700;">Aktif</span>
                                        <?php elseif ($f['status'] === 'suspended'): ?>
                                            <span style="background:rgba(239,68,68,0.12);color:#b91c1c;border:1px solid rgba(239,68,68,0.3);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:700;">Ditangguhkan</span>
                                        <?php else: ?>
                                            <span style="background:rgba(251,191,36,0.12);color:#92400e;border:1px solid rgba(251,191,36,0.3);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:700;">Menunggu Approval</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                            <?php if ($f['status'] === 'active'): ?>
                                                <a href="users.php?action=suspend&id=<?php echo $f['id']; ?>"
                                                    style="padding:5px 11px;font-size:0.76rem;border-radius:var(--radius-pill);border:1.5px solid rgba(239,68,68,0.35);color:#b91c1c;background:transparent;text-decoration:none;display:inline-flex;align-items:center;gap:4px;font-weight:600;">
                                                    <i data-lucide="user-x" style="width:12px;height:12px;"></i> Suspend
                                                </a>
                                            <?php else: ?>
                                                <a href="users.php?action=activate&id=<?php echo $f['id']; ?>"
                                                    style="padding:5px 11px;font-size:0.76rem;border-radius:var(--radius-pill);background:var(--accent-color);color:#fff;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:4px;font-weight:700;">
                                                    <i data-lucide="user-check" style="width:12px;height:12px;"></i> Approve (Setujui)
                                                </a>
                                            <?php endif; ?>

                                            <!-- Reset Password -->
                                            <?php if (!empty($f['google_id'])): ?>
                                                <button disabled title="Autentikasi dikelola oleh Google OAuth"
                                                    style="padding:5px 10px;font-size:0.76rem;border-radius:var(--radius-pill);border:1.5px solid #cbd5e1;color:#94a3b8;background:#f8fafc;cursor:not-allowed;display:inline-flex;align-items:center;gap:4px;">
                                                    <i data-lucide="lock" style="width:12px;height:12px;"></i> Reset Sandi
                                                </button>
                                            <?php else: ?>
                                                <button onclick="openResetPasswordModal(<?php echo $f['id']; ?>, '<?php echo htmlspecialchars(addslashes($f['name'])); ?>')"
                                                    style="padding:5px 10px;font-size:0.76rem;border-radius:var(--radius-pill);border:1.5px solid rgba(235,80,17,0.3);color:var(--accent-orange);background:transparent;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                                                    <i data-lucide="key" style="width:12px;height:12px;"></i> Reset Sandi
                                                </button>
                                            <?php endif; ?>

                                            <!-- Log Activity -->
                                            <button onclick="openUserLogsModal(<?php echo $f['id']; ?>, '<?php echo htmlspecialchars(addslashes($f['name'])); ?>')"
                                                style="padding:5px 10px;font-size:0.76rem;border-radius:var(--radius-pill);border:1.5px solid rgba(59,130,246,0.3);color:#2563eb;background:transparent;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                                                <i data-lucide="activity" style="width:12px;height:12px;"></i> Log Aktivitas
                                            </button>

                                            <!-- Edit Profile -->
                                            <button onclick='openEditUser(<?php echo htmlspecialchars(json_encode($f)); ?>)'
                                                style="padding:5px 10px;font-size:0.76rem;border-radius:var(--radius-pill);border:1.5px solid rgba(0,0,0,0.12);color:var(--text-primary);background:transparent;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                                                <i data-lucide="edit-2" style="width:12px;height:12px;"></i> Edit
                                            </button>

                                            <!-- Delete -->
                                            <button onclick="confirmDeleteUser(<?php echo $f['id']; ?>, '<?php echo htmlspecialchars(addslashes($f['name'])); ?>')"
                                                style="padding:5px 9px;font-size:0.76rem;border-radius:var(--radius-pill);border:1.5px solid rgba(239,68,68,0.3);color:#ef4444;background:transparent;cursor:pointer;">
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
            <label class="form-label" style="margin-top:12px;">Status Akun</label>
            <select name="status" id="eu-status" class="form-input">
                <option value="active">✓ Aktif</option>
                <option value="inactive">⏳ Menunggu Approval</option>
                <option value="suspended">🚫 Ditangguhkan / Diblokir</option>
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

<!-- Modal Reset Password -->
<div id="modal-reset-password" class="modal-overlay" style="display:none;" onclick="closeModal('modal-reset-password')">
    <div class="modal-box glass-card" style="max-width:440px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i data-lucide="key" style="width:18px;height:18px;color:var(--accent-orange);"></i> Reset Password Petani</h3>
            <button onclick="closeModal('modal-reset-password')" class="modal-close">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="reset-user-id">
            <p style="font-size:0.88rem;color:var(--text-secondary);margin-bottom:16px;">
                Reset kata sandi untuk petani: <strong id="reset-user-name" style="color:var(--text-primary);"></strong>
            </p>
            <label class="form-label">Password Baru</label>
            <div style="display:flex;gap:8px;">
                <input type="text" name="reset_new_password" id="reset-new-password" class="form-input" placeholder="Minimal 6 Karakter" required>
                <button type="button" onclick="generateRandomPass()" class="btn btn-secondary" style="font-size:0.75rem;padding:8px 12px;white-space:nowrap;">Acak</button>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:22px;">
                <button type="button" onclick="closeModal('modal-reset-password')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Password Baru</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal User Activity Logs -->
<div id="modal-user-logs" class="modal-overlay" style="display:none;" onclick="closeModal('modal-user-logs')">
    <div class="modal-box glass-card" style="max-width:720px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i data-lucide="activity" style="width:18px;height:18px;color:#2563eb;"></i> Audit Log Aktivitas: <span id="log-user-name"></span></h3>
            <button onclick="closeModal('modal-user-logs')" class="modal-close">✕</button>
        </div>
        <div id="user-logs-container" style="max-height:380px;overflow-y:auto;padding-right:4px;">
            <p style="text-align:center;color:var(--text-secondary);padding:20px;">Memuat data log…</p>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:16px;">
            <button type="button" onclick="closeModal('modal-user-logs')" class="btn btn-secondary">Tutup</button>
        </div>
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
    .modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9000;
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .modal-box {
        width: 100%;
        max-width: 540px;
        max-height: 92vh;
        overflow-y: auto;
        padding: 26px;
        border-radius: var(--radius-xl);
        animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: translateY(18px) scale(0.97)
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1)
        }
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .modal-header h3 {
        font-family: var(--font-heading);
        font-size: 1.05rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: var(--text-secondary);
        padding: 2px 6px;
        border-radius: 6px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        font-size: 0.81rem;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }

    .form-input {
        width: 100%;
        padding: 9px 12px;
        border-radius: 10px;
        border: 1.5px solid rgba(0, 0, 0, 0.1);
        background: rgba(255, 255, 255, 0.7);
        font-size: 0.88rem;
        box-sizing: border-box;
    }

    .form-input:focus {
        border-color: var(--accent-color);
        outline: none;
    }
</style>

<!-- Modal Assign Device -->
<div id="modal-assign-device" class="modal-overlay" style="display:none;" onclick="closeModal('modal-assign-device')">
    <div class="modal-box glass-card" style="max-width:440px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i data-lucide="cpu" style="width:18px;height:18px;color:#0D5D31;"></i> Alokasi Kode Perangkat: <span id="assign-user-name"></span></h3>
            <button onclick="closeModal('modal-assign-device')" class="modal-close">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="assign_device">
            <input type="hidden" name="user_id" id="assign-user-id">

            <div style="margin-bottom:14px;">
                <label class="form-label">Nama Greenhouse / Lahan</label>
                <input type="text" name="greenhouse_name" value="Greenhouse Utama" class="form-input" required>
            </div>

            <div style="margin-bottom:18px;">
                <label class="form-label">Kode Akses / ID Perangkat IoT (Node Firebase)</label>
                <input type="text" name="device_code" placeholder="Contoh: ESP32_GH_001 atau MCM-GH001" class="form-input" required style="font-family:monospace;font-weight:700;letter-spacing:1px;">
                <small style="color:var(--text-secondary);font-size:0.75rem;display:block;margin-top:4px;">Berikan Kode Perangkat ini kepada petani yang telah berlangganan.</small>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeModal('modal-assign-device')" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Kode Perangkat</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAssignDeviceModal(id, name) {
        document.getElementById('assign-user-id').value = id;
        document.getElementById('assign-user-name').textContent = name;
        openModal('modal-assign-device');
    }

    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
        lucide.createIcons();
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function openEditUser(u) {
        document.getElementById('eu-user-id').value = u.id;
        document.getElementById('eu-name').value = u.name || '';
        document.getElementById('eu-email').value = u.email || '';
        document.getElementById('eu-phone').value = u.phone || '';
        document.getElementById('eu-status').value = u.status || 'inactive';

        // Disable email editing for Google Auth users
        const emailInput = document.getElementById('eu-email');
        if (u.google_id) {
            emailInput.readOnly = true;
            emailInput.title = 'Email Google dikelola oleh Google OAuth';
            emailInput.style.background = '#f1f5f9';
        } else {
            emailInput.readOnly = false;
            emailInput.title = '';
            emailInput.style.background = '';
        }
        openModal('modal-edit-user');
    }

    function openResetPasswordModal(id, name) {
        document.getElementById('reset-user-id').value = id;
        document.getElementById('reset-user-name').textContent = name;
        document.getElementById('reset-new-password').value = '';
        openModal('modal-reset-password');
    }

    function generateRandomPass() {
        const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#';
        let pass = '';
        for (let i = 0; i < 8; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        document.getElementById('reset-new-password').value = pass;
    }

    function openUserLogsModal(id, name) {
        document.getElementById('log-user-name').textContent = name;
        const container = document.getElementById('user-logs-container');
        container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:20px;">Memuat data log…</p>';
        openModal('modal-user-logs');

        fetch('../api/get_user_logs.php?user_id=' + id)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.logs && res.logs.length > 0) {
                    container.innerHTML = res.logs.map(l => `
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:12px 14px;border-bottom:1px solid rgba(0,0,0,0.05);font-size:0.86rem;">
                        <div style="display:flex;align-items:flex-start;gap:10px;flex:1;min-width:0;">
                            <i data-lucide="clock" style="width:16px;height:16px;min-width:16px;min-height:16px;flex-shrink:0;color:var(--text-secondary);margin-top:2px;"></i>
                            <span style="font-weight:500;color:var(--text-primary);line-height:1.45;word-break:break-word;">
                                ${l.activity}
                            </span>
                        </div>
                        <span style="font-size:0.78rem;color:var(--text-secondary);font-family:monospace;white-space:nowrap;flex-shrink:0;margin-top:2px;">
                            ${l.accessed_at}
                        </span>
                    </div>
                `).join('');
                    if (window.lucide) lucide.createIcons();
                } else {
                    container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:24px;">Belum ada log aktivitas untuk petani ini.</p>';
                }
            })
            .catch(err => {
                container.innerHTML = '<p style="text-align:center;color:#ef4444;padding:20px;">Gagal memuat log aktivitas.</p>';
            });
    }

    function confirmDeleteUser(id, name) {
        document.getElementById('del-user-id').value = id;
        document.getElementById('del-user-name').textContent = name;
        openModal('modal-delete-user');
    }
    document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
<?php include '../includes/footer.php'; ?>