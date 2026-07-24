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

// Get current user details from DB
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error = "Terjadi kesalahan memuat profil: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $new_pass = trim($_POST['password']);
    
    if (empty($name) || empty($phone)) {
        $error = "Nama dan nomor telepon wajib diisi!";
    } else {
        try {
            if (!empty($new_pass)) {
                // Update with password change
                $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $hashed_password, $userId]);
            } else {
                // Update without password change
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $userId]);
            }
            
            // Log activity
            $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
            $log_stmt->execute([$userId, "Melakukan pembaruan pengaturan profil akun"]);
            
            // Update session
            $_SESSION['name'] = $name;
            
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
                <h2>Pengaturan Akun & Kalibrasi</h2>
                <p>Kelola profil personal Anda dan atur preferensi pemantauan.</p>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <!-- Left Column: Form Settings -->
            <div class="left-column">
                <div class="glass-card">
                    <h3 style="margin-bottom: 20px;"><i data-lucide="user" style="vertical-align: middle; margin-right: 8px; color: var(--accent-color);"></i> Profil Pribadi</h3>
                    
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
                    
                    <form action="settings.php" method="POST">
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
            </div>
            
            <!-- Right Column: Kalibrasi Storage Reset & Info -->
            <div class="right-column">
                <div class="glass-card">
                    <h3 style="margin-bottom: 16px;"><i data-lucide="sliders" style="vertical-align: middle; margin-right: 8px; color: var(--accent-orange);"></i> Ringkasan Kalibrasi Lokal</h3>
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
</script>
