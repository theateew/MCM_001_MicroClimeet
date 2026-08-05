<?php
/**
 * pending_approval.php
 * Halaman Informasi Menunggu Persetujuan (Approval) Admin
 */

define('AUTH_BASE', '');
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$email = $_GET['email'] ?? ($_SESSION['last_sent_otp_email'] ?? '');

$page_title = "Menunggu Persetujuan Admin — MicroClimeet";
$custom_css = ['auth.css'];
include 'includes/header.php';
?>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; position: relative; background: url('assets/images/backgrounds/melonbg_clean.png') no-repeat center center / cover fixed;">
    <!-- Organic Emerald Tint Overlay (Soft & Seamless Blending) -->
    <div style="position: absolute; inset: 0; background: linear-gradient(135deg, rgba(13, 93, 49, 0.40) 0%, rgba(6, 44, 23, 0.65) 100%); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 1;"></div>

    <div class="auth-card glass-card" style="position: relative; z-index: 2; width: 100%; max-width: 500px; padding: 36px; border-radius: 28px; background: rgba(255, 255, 255, 0.60); backdrop-filter: blur(28px) saturate(190%); -webkit-backdrop-filter: blur(28px) saturate(190%); border: 1.5px solid rgba(255, 255, 255, 0.65); box-shadow: 0 30px 80px rgba(6, 44, 23, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.8); text-align: center;">

        <div style="width:72px;height:72px;border-radius:50%;background:rgba(245,158,11,0.12);color:#d97706;display:inline-flex;align-items:center;justify-content:center;margin-bottom:18px;">
            <i data-lucide="clock" style="width:36px;height:36px;"></i>
        </div>

        <h2 style="font-family:var(--font-heading);font-weight:800;color:#0D5D31;margin:0 0 8px 0;font-size:1.45rem;">
            Pendaftaran Diterima! ⏳
        </h2>
        
        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);border-radius:12px;padding:10px 14px;margin-bottom:20px;color:#15803d;font-size:0.86rem;font-weight:700;">
            ✓ Verifikasi Email OTP Gmail Berhasil
        </div>

        <p style="font-size:0.92rem;color:var(--text-secondary);line-height:1.6;margin-bottom:24px;">
            Email Anda <strong style="color:var(--text-primary);"><?php echo htmlspecialchars($email ?: 'Anda'); ?></strong> telah terverifikasi.<br>
            Saat ini pendaftaran akun petani Anda sedang dalam proses <strong>persetujuan (approval) oleh Administrator MicroClimeet</strong>.
        </p>

        <div style="background:rgba(13,93,49,0.05);border-radius:16px;padding:16px;margin-bottom:24px;text-align:left;font-size:0.85rem;color:#334155;line-height:1.5;">
            <strong>💡 Tahap Selanjutnya:</strong>
            <ul style="margin:8px 0 0 0;padding-left:20px;">
                <li>Admin akan mengecek dan menyetujui akun Anda.</li>
                <li>Jika Anda sudah membeli perangkat IoT, Admin akan memberikan <strong>Kode Akses Perangkat</strong> untuk didaftarkan ke akun Anda.</li>
                <li>Silakan konfirmasi ke Admin via WhatsApp untuk mempercepat proses persetujuan.</li>
            </ul>
        </div>

        <a href="https://wa.me/6283829634058?text=Halo%20Admin%20MicroClimeet,%20email%20saya%20<?php echo urlencode($email); ?>%20sudah%20terverifikasi.%20Mohon%20disetujui%20akun%20petani%20saya." target="_blank" class="btn btn-primary" style="width:100%;padding:13px;border-radius:30px;font-size:0.92rem;font-weight:800;background:#0D5D31!important;color:#fff!important;border:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 6px 20px rgba(13,93,49,0.3);text-decoration:none;margin-bottom:14px;">
            💬 Konfirmasi Approval ke WhatsApp Admin
        </a>

        <div id="approval-status-badge" style="display:inline-flex;align-items:center;gap:6px;font-size:0.8rem;color:#059669;background:#ecfdf5;padding:6px 14px;border-radius:20px;margin-top:10px;font-weight:700;">
            <span style="width:8px;height:8px;border-radius:50%;background:#10b981;animation:pulse 1.5s infinite;"></span>
            Mengecek persetujuan Admin secara realtime...
        </div>

    </div>
</div>

<!-- Success Overlay Modal when Approved -->
<div id="approved-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(13,93,49,0.85);backdrop-filter:blur(8px);z-index:9999;align-items:center;justify-content:center;color:#fff;text-align:center;padding:20px;">
    <div style="background:#fff;color:#1e293b;border-radius:24px;padding:36px;max-width:420px;width:100%;box-shadow:0 25px 50px rgba(0,0,0,0.3);animation:popIn 0.5s ease-out;">
        <div style="width:72px;height:72px;border-radius:50%;background:rgba(34,197,94,0.15);color:#16a34a;display:inline-flex;align-items:center;justify-content:center;margin:0 auto 18px auto;">
            <i data-lucide="check-circle-2" style="width:44px;height:44px;"></i>
        </div>
        <h3 style="font-family:var(--font-heading);font-weight:900;color:#0D5D31;margin:0 0 10px 0;font-size:1.4rem;">
            Selamat Datang! 🎉
        </h3>
        <p style="font-size:0.92rem;color:#475569;line-height:1.5;margin-bottom:20px;">
            Akun Anda telah <strong>Disetujui oleh Admin MicroClimeet</strong>. Mengalihkan Anda langsung ke Dashboard Petani...
        </p>
        <div style="display:flex;align-items:center;justify-content:center;gap:10px;font-size:0.85rem;font-weight:700;color:#0D5D31;">
            <div style="width:20px;height:20px;border:3px solid #0D5D31;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
            Membuka Dashboard...
        </div>
    </div>
</div>

<style>
@keyframes pulse { 0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; } }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
@keyframes popIn { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userEmail = <?php echo json_encode($email); ?>;
    
    function checkStatus() {
        fetch('api/check_approval_status.php?email=' + encodeURIComponent(userEmail))
            .then(res => res.json())
            .then(data => {
                if (data.approved) {
                    // Show success modal
                    const modal = document.getElementById('approved-modal');
                    modal.style.display = 'flex';
                    if (window.lucide) lucide.createIcons();
                    
                    // Redirect after 1.5 seconds
                    setTimeout(function() {
                        window.location.href = data.redirect || 'petani/index.php';
                    }, 1500);
                }
            })
            .catch(err => console.log('Polling approval status...'));
    }

    // Check every 3 seconds
    setInterval(checkStatus, 3000);
    checkStatus();
});
</script>

<?php include 'includes/footer.php'; ?>
