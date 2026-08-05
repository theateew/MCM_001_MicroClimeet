<?php
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('petani');

$page_title = "Pengaturan Akun";
$custom_css = ['dashboard.css'];
$base_url = '../';

$message = '';
$error = '';

$userId = $_SESSION['user_id'];

// Migration safeguard for users.photo column
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN photo VARCHAR(255) NULL AFTER phone");
} catch (PDOException $e) {
    // Column already exists
}

// Function to process user profile photo upload (supports base64 cropped data & standard file)
function processUserProfilePhotoUpload($userId, $oldPhoto = null) {
    // 1. Check if cropped base64 string is passed from frame adjustment modal
    if (!empty($_POST['cropped_image_data'])) {
        $data = $_POST['cropped_image_data'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]); // jpeg, png, webp
            
            $data = base64_decode($data);
            if ($data === false) {
                return ['success' => false, 'error' => 'Gagal memproses gambar hasil penyesuaian frame.'];
            }
            
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = ($type === 'jpeg') ? 'jpg' : $type;
            $filename = 'usr_' . $userId . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            $dbPath = 'uploads/profiles/' . $filename;
            
            if (file_put_contents($targetPath, $data)) {
                if ($oldPhoto && file_exists(__DIR__ . '/../' . $oldPhoto)) {
                    @unlink(__DIR__ . '/../' . $oldPhoto);
                }
                return ['success' => true, 'path' => $dbPath];
            }
            return ['success' => false, 'error' => 'Gagal menyimpan foto profil ke server.'];
        }
    }

    // 2. Fallback to standard $_FILES upload
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => $oldPhoto];
    }

    $file = $_FILES['photo'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Gagal mengunggah file foto profil.'];
    }

    // Max 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Ukuran foto profil maksimal 5MB.'];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $fileInfo = @getimagesize($file['tmp_name']);
    if (!$fileInfo || !in_array($fileInfo['mime'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Format file harus berupa gambar (JPG, PNG, WEBP, GIF).'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($ext)) {
        $ext = str_replace('image/', '', $fileInfo['mime']);
    }

    $uploadDir = __DIR__ . '/../uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'usr_' . $userId . '_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext);
    $targetPath = $uploadDir . $filename;
    $dbPath = 'uploads/profiles/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        if ($oldPhoto && file_exists(__DIR__ . '/../' . $oldPhoto)) {
            @unlink(__DIR__ . '/../' . $oldPhoto);
        }
        return ['success' => true, 'path' => $dbPath];
    }

    return ['success' => false, 'error' => 'Gagal menyimpan file foto ke server.'];
}

// Get current user details from DB
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error = "Terjadi kesalahan memuat profil: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_2fa_toggle'])) {
        $two_fa_val = (int)($_POST['two_factor_value'] ?? 0);
        try {
            $pdo->prepare("UPDATE users SET two_factor_enabled = ? WHERE id = ?")->execute([$two_fa_val, $userId]);
            $user['two_factor_enabled'] = $two_fa_val;
            $message = $two_fa_val ? "Verifikasi 2FA via Email Gmail berhasil DIAKTIFKAN! 🔒" : "Verifikasi 2FA via Email Gmail telah DINON-AKTIFKAN.";
        } catch (PDOException $e) {
            $error = "Gagal memperbarui status 2FA: " . $e->getMessage();
        }
    } else {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $new_pass = trim($_POST['password']);
    
    if (empty($name) || empty($phone)) {
        $error = "Nama dan nomor telepon wajib diisi!";
    } else {
        $uploadResult = processUserProfilePhotoUpload($userId, $user['photo'] ?? null);
        if (!$uploadResult['success']) {
            $error = $uploadResult['error'];
        } else {
            $photoPath = $uploadResult['path'];
            try {
                if (!empty($new_pass)) {
                    $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, password = ?, photo = ? WHERE id = ?");
                    $stmt->execute([$name, $phone, $hashed_password, $photoPath, $userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, photo = ? WHERE id = ?");
                    $stmt->execute([$name, $phone, $photoPath, $userId]);
                }
                
                // Log activity
                $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                $log_stmt->execute([$userId, "Melakukan pembaruan pengaturan profil akun & foto profil"]);
                
                // Update session
                $_SESSION['name'] = $name;
                $_SESSION['photo'] = $photoPath;
                
                // Reload user info
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                $message = "Pengaturan profil berhasil diperbarui!";
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan pembaruan: " . $e->getMessage();
            }
        }
    }
}
}

include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <?php 
    $active_menu = 'settings';
    include '../includes/sidebar.php'; 
    ?>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2><i data-lucide="settings" style="color:#0D5D31;"></i> Pengaturan Akun & Profil</h2>
                <p>Kelola profil personal Anda dan atur preferensi pemantauan.</p>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <!-- Left Column: Form Settings -->
            <div class="left-column">
                <div class="glass-card">
                    <h3 class="dash-card-title" style="margin-bottom: 20px;">
                        <i data-lucide="user" style="color: var(--accent-color);"></i>
                        <span>Profil Pribadi</span>
                    </h3>
                    
                    <?php if (!empty($message)): ?>
                        <div class="glass-card" style="background: rgba(114, 156, 81, 0.12); border-color: rgba(114, 156, 81, 0.25); color: #55793a; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                            <i data-lucide="check-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25); color: #c93535; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                            <i data-lucide="alert-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="settings.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="cropped_image_data" id="cropped_image_data" value="">

                        <!-- Profile Photo Upload Box -->
                        <div style="display:flex; align-items:center; gap:20px; margin-bottom:24px; padding-bottom:20px; border-bottom:1px solid rgba(0,0,0,0.06);">
                            <div class="profile-photo-picker" style="position:relative; width:88px; height:88px; border-radius:50%; flex-shrink:0; cursor:pointer; overflow:hidden; border:3px solid #729C51; box-shadow:0 0 16px rgba(114, 156, 81, 0.4), 0 4px 14px rgba(0,0,0,0.12);" onclick="document.getElementById('photo_input').click();">
                                <?php 
                                    $profile_img = (!empty($user['photo']) && file_exists(__DIR__ . '/../' . $user['photo']))
                                        ? '../' . htmlspecialchars($user['photo'])
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($user['name'] ?? 'U') . '&background=166534&color=fff&size=160';
                                ?>
                                <img id="avatar-preview" src="<?php echo $profile_img; ?>" alt="Foto Profil" style="width:100%; height:100%; object-fit:cover;">
                                <div class="photo-overlay" style="position:absolute; inset:0; background:rgba(0,0,0,0.5); display:flex; flex-direction:column; align-items:center; justify-content:center; color:#fff; opacity:0; transition:opacity 0.25s ease;">
                                    <i data-lucide="camera" style="width:20px; height:20px; margin-bottom:2px;"></i>
                                    <span style="font-size:0.65rem; font-weight:700;">Ubah</span>
                                </div>
                            </div>
                            <div>
                                <h4 style="font-family:var(--font-heading); font-size:0.95rem; margin-bottom:4px; font-weight:700;">Foto Profil Akun</h4>
                                <p style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:10px;">Format: JPG, PNG, WEBP (Maks 5MB)</p>
                                <button type="button" class="btn btn-secondary" style="font-size:0.8rem; padding:6px 14px; gap:6px; display:inline-flex; align-items:center;" onclick="document.getElementById('photo_input').click();">
                                    <i data-lucide="crop" style="width:15px; height:15px;"></i> Pilih & Sesuaikan Frame
                                </button>
                                <input type="file" name="photo" id="photo_input" accept="image/*" style="display:none;" onchange="previewProfilePhoto(this)">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="name">Nama Lengkap</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Alamat Email (Tidak dapat diubah)</label>
                            <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: rgba(0, 0, 0, 0.05); color: var(--text-secondary);">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Nomor Telepon / WhatsApp</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Kata Sandi Baru (Kosongkan jika tidak ingin diubah)</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Simpan Perubahan</button>
                    </form>
                </div>

                <!-- Card 2: Keamanan Akun & Verifikasi 2FA via Gmail -->
                <div class="glass-card" style="margin-top:24px;padding:24px;background:linear-gradient(135deg, rgba(13,93,49,0.04) 0%, rgba(34,197,94,0.04) 100%);border:1.5px solid rgba(13,93,49,0.18);">
                    <h3 class="dash-card-title" style="margin-bottom: 12px;display:flex;align-items:center;gap:10px;">
                        <i data-lucide="shield-check" style="color: #0D5D31;"></i>
                        <span>Keamanan 2FA via Email Gmail</span>
                    </h3>
                    <p style="font-size:0.86rem;color:var(--text-secondary);margin-bottom:18px;">
                        Lindungi akun Anda dari akses tidak sah. Saat 2FA aktif, setiap kali Anda login, sistem akan mengirimkan <strong>Kode OTP 6-Digit ke Inbox Gmail</strong> Anda.
                    </p>

                    <form method="POST" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;background:#fff;padding:14px 18px;border-radius:14px;border:1px solid rgba(0,0,0,0.08);">
                        <input type="hidden" name="action_2fa_toggle" value="1">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:38px;height:38px;border-radius:10px;background:rgba(13,93,49,0.1);color:#0D5D31;display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="mail-warning" style="width:20px;height:20px;"></i>
                            </div>
                            <div>
                                <span style="display:block;font-weight:700;font-size:0.88rem;color:var(--text-primary);">Verifikasi 2FA via Email Gmail</span>
                                <span style="display:block;font-size:0.78rem;color:var(--text-secondary);">
                                    Status 2FA: <strong style="color:<?php echo !empty($user['two_factor_enabled'])?'#15803d':'#dc2626'; ?>;"><?php echo !empty($user['two_factor_enabled'])?'AKTIF (Terproteksi 🔒)':'NON-AKTIF'; ?></strong>
                                </span>
                            </div>
                        </div>

                        <button type="submit" name="two_factor_value" value="<?php echo !empty($user['two_factor_enabled']) ? '0' : '1'; ?>"
                                class="btn" style="background:<?php echo !empty($user['two_factor_enabled'])?'#ef4444':'#0D5D31'; ?>;color:#fff;font-size:0.82rem;padding:9px 18px;border-radius:10px;font-weight:700;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                            <i data-lucide="<?php echo !empty($user['two_factor_enabled'])?'shield-off':'shield-check'; ?>" style="width:15px;height:15px;display:inline-block;vertical-align:middle;margin-right:4px;"></i>
                            <?php echo !empty($user['two_factor_enabled']) ? 'Non-aktifkan 2FA' : 'Aktifkan 2FA Email'; ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Kalibrasi Storage Reset & Info -->
            <div class="right-column">
                <div class="glass-card">
                    <h3 class="dash-card-title" style="margin-bottom: 16px;">
                        <i data-lucide="sliders" style="color: var(--accent-orange);"></i>
                        <span>Ringkasan Kalibrasi Lokal</span>
                    </h3>
                    <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 20px;">
                        Nilai offset kalibrasi sensor saat ini disimpan di browser Anda:
                    </p>
                    
                    <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.88rem; margin-bottom: 24px;">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 6px;">
                            <span style="color: var(--text-secondary);">Suhu Udara (BME680):</span>
                            <strong id="label-offset-temp">0.0°C</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 6px;">
                            <span style="color: var(--text-secondary);">Kelembapan (BME680):</span>
                            <strong id="label-offset-hum">0.0%</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 6px;">
                            <span style="color: var(--text-secondary);">Kualitas Udara (PMS5003):</span>
                            <strong id="label-offset-air">0</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 6px;">
                            <span style="color: var(--text-secondary);">Indeks UV (GUVA-S12SD):</span>
                            <strong id="label-offset-uv">0.0</strong>
                        </div>
                    </div>
                    
                    <button onclick="resetCalibration()" class="btn btn-secondary" style="width: 100%; border-color: rgba(239, 68, 68, 0.3); color: var(--danger-color);">
                        <i data-lucide="rotate-ccw" style="width: 16px; height: 16px;"></i> Reset Nilai Kalibrasi
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ── MODAL: Adjustment / Crop Frame Foto Profil ──────────────────────── -->
<div id="modal-crop-avatar" class="modal-overlay" style="display:none;" onclick="closeCropModal()">
    <div class="modal-box glass-card" style="max-width:520px; width:92%;" onclick="event.stopPropagation()">
        <div class="modal-header" style="border-bottom:1px solid rgba(0,0,0,0.06); padding-bottom:12px; margin-bottom:16px;">
            <h3><i data-lucide="crop" style="color:var(--accent-color);"></i> Sesuaikan Frame Foto Profil</h3>
            <button type="button" onclick="closeCropModal()" class="modal-close">✕</button>
        </div>
        
        <div style="max-height:360px; min-height:260px; background:#111; border-radius:14px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
            <img id="crop-image-element" src="" style="max-width:100%; max-height:360px; display:block;">
        </div>

        <!-- Controls toolbar: Zoom, Rotate, Reset -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:16px; gap:10px; flex-wrap:wrap; background:rgba(0,0,0,0.03); padding:10px 16px; border-radius:12px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:0.8rem; font-weight:600; color:var(--text-secondary);">Zoom:</span>
                <button type="button" class="btn btn-secondary" style="padding:4px 10px; font-size:0.8rem;" onclick="zoomCrop(-0.1)">-</button>
                <button type="button" class="btn btn-secondary" style="padding:4px 10px; font-size:0.8rem;" onclick="zoomCrop(0.1)">+</button>
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <button type="button" class="btn btn-secondary" style="padding:4px 12px; font-size:0.8rem; gap:4px; display:inline-flex; align-items:center;" onclick="rotateCrop(90)">
                    <i data-lucide="rotate-cw" style="width:14px; height:14px;"></i> Putar
                </button>
                <button type="button" class="btn btn-secondary" style="padding:4px 12px; font-size:0.8rem;" onclick="resetCrop()">
                    Reset
                </button>
            </div>
        </div>

        <p style="font-size:0.78rem; color:var(--text-secondary); text-align:center; margin-top:10px;">
            Geser dan atur letak foto Anda agar presisi di dalam frame lingkaran.
        </p>

        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:18px;">
            <button type="button" onclick="closeCropModal()" class="btn btn-secondary">Batal</button>
            <button type="button" onclick="applyCroppedAvatar()" class="btn btn-primary" style="gap:6px; display:inline-flex; align-items:center;">
                <i data-lucide="check" style="width:16px; height:16px;"></i> Terapkan Frame
            </button>
        </div>
    </div>
</div>

<!-- Cropper.js CDN Styles & Script -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
/* Circular Cropper viewport mask styling */
.cropper-view-box,
.cropper-face {
  border-radius: 50% !important;
  outline: 2.5px solid #729C51 !important;
}
.cropper-dashed, .cropper-line, .cropper-point {
  opacity: 0.5;
}
</style>

<?php 
$custom_js = [];
include '../includes/footer.php'; 
?>

<script>
    // Read and display browser offsets
    function loadOffsets() {
        const temp = parseFloat(localStorage.getItem('cal_offset_temperature') || 0.0);
        const hum = parseFloat(localStorage.getItem('cal_offset_humidity') || 0.0);
        const air = parseFloat(localStorage.getItem('cal_offset_air_quality') || 0.0);
        const uv = parseFloat(localStorage.getItem('cal_offset_uv') || 0.0);
        
        document.getElementById('label-offset-temp').innerText = (temp >= 0 ? '+' : '') + temp.toFixed(1) + '°C';
        document.getElementById('label-offset-hum').innerText = (hum >= 0 ? '+' : '') + hum.toFixed(1) + '%';
        document.getElementById('label-offset-air').innerText = (air >= 0 ? '+' : '') + air.toFixed(0);
        document.getElementById('label-offset-uv').innerText = (uv >= 0 ? '+' : '') + uv.toFixed(1);
    }
    loadOffsets();

    function resetCalibration() {
        if (confirm("Apakah Anda yakin ingin menyetel ulang semua nilai kalibrasi sensor kembali ke nol?")) {
            localStorage.setItem('cal_offset_temperature', 0.0);
            localStorage.setItem('cal_offset_humidity', 0.0);
            localStorage.setItem('cal_offset_air_quality', 0.0);
            localStorage.setItem('cal_offset_uv', 0.0);
            
            loadOffsets();
            alert("Nilai kalibrasi berhasil di-reset!");
        }
    }

    let cropperInstance = null;

    function previewProfilePhoto(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const cropImg = document.getElementById('crop-image-element');
                cropImg.src = e.target.result;
                
                // Show modal
                document.getElementById('modal-crop-avatar').style.display = 'flex';
                
                // Destroy previous cropper instance if exists
                if (cropperInstance) {
                    cropperInstance.destroy();
                }
                
                // Initialize Cropper.js with 1:1 circle mask
                cropperInstance = new Cropper(cropImg, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.85,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            };
            reader.readAsDataURL(file);
        }
    }

    function zoomCrop(ratio) {
        if (cropperInstance) {
            cropperInstance.zoom(ratio);
        }
    }

    function rotateCrop(degree) {
        if (cropperInstance) {
            cropperInstance.rotate(degree);
        }
    }

    function resetCrop() {
        if (cropperInstance) {
            cropperInstance.reset();
        }
    }

    function closeCropModal() {
        document.getElementById('modal-crop-avatar').style.display = 'none';
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }
    }

    function applyCroppedAvatar() {
        if (cropperInstance) {
            const canvas = cropperInstance.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
            
            if (canvas) {
                const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
                document.getElementById('avatar-preview').src = dataUrl;
                document.getElementById('cropped_image_data').value = dataUrl;
                closeCropModal();
            }
        }
    }
</script>

