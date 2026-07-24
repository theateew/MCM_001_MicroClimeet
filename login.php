<?php
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect user if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: petani/index.php");
    }
    exit;
}

$page_title = "Masuk ke Sistem";
$custom_css = ['landing.css'];
$base_url = '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = "Email dan kata sandi wajib diisi!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'inactive') {
                    $error = "Akun Anda belum aktif. Hubungi Admin untuk melakukan persetujuan pendaftaran.";
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Log activity
                    $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?, ?)");
                    $log_stmt->execute([$user['id'], "Melakukan login ke dalam sistem"]);
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header("Location: admin/index.php");
                    } else {
                        header("Location: petani/index.php");
                    }
                    exit;
                }
            } else {
                $error = "Email atau kata sandi Anda salah!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 40px 20px; background: var(--bg-white);">
    <div class="glass-card" style="width: 100%; max-width: 440px; text-align: left;">
        <div style="text-align: center; margin-bottom: 28px;">
            <a href="index.php"><img src="assets/images/brand/logo.png" alt="MicroClimeet Logo" style="height: 46px; margin-bottom: 12px;"></a>
            <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 4px;">Selamat Datang <em style="font-family: var(--font-serif); font-weight: 400;">Kembali</em></h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Silakan masuk untuk mengakses dashboard pemantauan</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="glass-card" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25); color: #c93535; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem;">
                <i data-lucide="alert-circle" style="display: inline-block; vertical-align: middle; height: 16px; margin-right: 6px;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nama@email.com" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 14px;">
                <label for="password">Kata Sandi (Password)</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <label style="font-size: 0.85rem; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="checkbox" name="remember" style="accent-color: var(--accent-color);"> Ingat Saya
                </label>
                <a href="#" style="font-size: 0.85rem; color: var(--accent-orange); font-weight: 600;">Lupa Sandi?</a>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px 24px;">Masuk Sekarang</button>
        </form>
        
        <div style="text-align: center; margin-top: 24px; font-size: 0.9rem; color: var(--text-secondary);">
            Belum memiliki akun? <a href="signup.php" style="color: var(--accent-color); font-weight: 600;">Daftar di sini</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
