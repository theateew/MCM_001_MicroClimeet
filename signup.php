<?php
require_once 'config/database.php';

$page_title = "Pendaftaran Akun Petani";
$custom_css = ['landing.css'];
$base_url = '';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $error = "Semua kolom pendaftaran wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email sudah terdaftar! Gunakan email lain atau silakan masuk.";
            } else {
                // Insert new user as inactive petani
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, status) VALUES (?, ?, ?, ?, 'petani', 'inactive')");
                $stmt->execute([$name, $email, $hashed_password, $phone]);
                
                $user_id = $pdo->lastInsertId();
                
                // Log registration activity
                $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                $log_stmt->execute([$user_id, "Melakukan pendaftaran akun petani baru"]);
                
                $message = "Pendaftaran berhasil! Akun Anda dinonaktifkan sementara untuk peninjauan. Silakan hubungi Admin untuk persetujuan akun.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 40px 20px; background: var(--bg-white);">
    <div class="glass-card" style="width: 100%; max-width: 480px; text-align: left;">
        <div style="text-align: center; margin-bottom: 28px;">
            <a href="index.php"><img src="assets/images/brand/logo.png" alt="MicroClimeet Logo" style="height: 46px; margin-bottom: 12px;"></a>
            <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 4px;">Daftar Akun <em style="font-family: var(--font-serif); font-weight: 400;">Baru</em></h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola iklim mikro greenhouse Anda hari ini</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="glass-card" style="background: rgba(74, 222, 128, 0.1); border-color: rgba(74, 222, 128, 0.25); color: #166534; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                <i data-lucide="check-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25); color: #c93535; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                <i data-lucide="alert-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($message)): ?>
        <form action="signup.php" method="POST">
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Masukkan nama lengkap Anda" required>
            </div>
            
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nama@email.com" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Nomor Telepon / WhatsApp</label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="Contoh: 08123456789" required>
            </div>
            
            <div class="form-group">
                <label for="password">Kata Sandi (Password)</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 14px 24px;">Daftar Akun</button>
        </form>
        <?php else: ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="https://wa.me/6281234567890?text=Halo%20Admin%20MicroClimeet,%20saya%20baru%20saja%20mendaftar%20dengan%20email%20<?php echo urlencode($email); ?>.%20Mohon%20untuk%20disetujui%20akunnya." target="_blank" class="btn btn-orange" style="width: 100%; margin-bottom: 12px;">
                    <i data-lucide="message-circle"></i> Konfirmasi ke WhatsApp Admin
                </a>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 24px; font-size: 0.9rem; color: var(--text-secondary);">
            Sudah memiliki akun? <a href="login.php" style="color: var(--accent-color); font-weight: 600;">Masuk di sini</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
