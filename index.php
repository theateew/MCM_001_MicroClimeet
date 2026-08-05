<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

$page_title = "Pantau Greenhouse Anda Secara Real-Time";
$custom_css = ['landing.css'];
$custom_js = ['carousel.js', 'animations.js'];
$base_url = '';
include 'includes/header.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['name'] : '';
$user_role = $is_logged_in ? $_SESSION['role'] : '';
$dashboard_url = $is_logged_in
    ? ($user_role === 'admin' ? 'admin/index.php' : 'petani/index.php')
    : 'login.php';
// Fetch Realtime Admin Metrics for Mini Dashboard Bar
$live_total_petani = 0;
$live_total_devices = 0;
$commodity_counts = [
    'Melon Premium' => 0,
    'Paprika'       => 0,
    'Tomat Ceri'    => 0,
    'Stroberi'      => 0
];

try {
    if (isset($pdo)) {
        $live_total_petani = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'petani' AND status = 'active'")->fetchColumn();
        $live_total_devices = (int)$pdo->query("SELECT COUNT(*) FROM greenhouses WHERE status = 'active'")->fetchColumn();

        $c_stmt = $pdo->query("SELECT commodity, COUNT(*) as cnt FROM greenhouses WHERE status = 'active' GROUP BY commodity");
        while ($row = $c_stmt->fetch()) {
            $c_raw = trim($row['commodity']);
            if (empty($c_raw)) {
                $commodity_counts['Melon Premium'] += (int)$row['cnt'];
                continue;
            }
            $matched = false;
            foreach ($commodity_counts as $k => $v) {
                if (strcasecmp($k, $c_raw) === 0) {
                    $commodity_counts[$k] += (int)$row['cnt'];
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $commodity_counts[$c_raw] = (int)$row['cnt'];
            }
        }
    }
} catch (Exception $e) {
    // Graceful fallback
}

// Include Ultra-Premium 3D Zero-Gravity Splash Screen (runs once per session on Landing Page)
include 'includes/splash_screen.php';
?>

<!-- Ambient Glowing Background Blobs -->
<div class="bg-ambient-blob blob-1"></div>
<div class="bg-ambient-blob blob-2"></div>
<div class="bg-ambient-blob blob-3"></div>

<!-- Floating Translucent Glassmorphism Navbar -->
<nav class="navbar" id="mainNavbar">
    <!-- LEFT: Logo -->
    <div class="nav-brand">
        <a href="index.php"><img src="assets/images/brand/logo.png" alt="MicroClimeet Logo"></a>
    </div>

    <!-- CENTER: Desktop nav links + Mobile hamburger toggle (centered) -->
    <div class="nav-center">
        <!-- Desktop links (hidden on mobile, shown on desktop) -->
        <ul class="nav-links" id="navLinks">
            <li><a href="#beranda" class="active">Beranda</a></li>
            <li><a href="#fitur">Fitur & Sensor</a></li>
            <li><a href="#showcase-carousel">Pratinjau Web</a></li>
            <li><a href="#harga">Harga</a></li>
            <li><a href="#tentang">Tentang Kami</a></li>
        </ul>

        <!-- Mobile hamburger toggle (centered, hidden on desktop) -->
        <button class="mobile-toggle" id="mobileToggle" aria-label="Buka Menu Navigasi" aria-expanded="false">
            <i data-lucide="menu" class="icon-menu"></i>
            <i data-lucide="x" class="icon-close" style="display:none;"></i>
        </button>

        <!-- Mobile dropdown menu (slides down from hamburger) -->
        <div class="mobile-dropdown" id="mobileDropdown" aria-hidden="true">
            <ul class="mobile-nav-links">
                <li><a href="#beranda" class="active">Beranda</a></li>
                <li><a href="#fitur">Fitur & Sensor</a></li>
                <li><a href="#showcase-carousel">Pratinjau Web</a></li>
                <li><a href="#harga">Harga</a></li>
                <li><a href="#tentang">Tentang Kami</a></li>
            </ul>
        </div>
    </div>

    <!-- RIGHT: Actions (login / dashboard) -->
    <div class="nav-actions">
        <?php if ($is_logged_in): ?>
            <?php
            $nav_photo = $_SESSION['photo'] ?? null;
            if ($nav_photo && file_exists(__DIR__ . '/' . $nav_photo)) {
                $nav_avatar = htmlspecialchars($nav_photo);
            } elseif ($nav_photo && file_exists(__DIR__ . '/assets/uploads/avatars/' . $nav_photo)) {
                $nav_avatar = 'assets/uploads/avatars/' . htmlspecialchars($nav_photo);
            } else {
                $nav_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=166534&color=fff&size=80';
            }
            ?>
            <a href="<?php echo $dashboard_url; ?>" class="btn btn-secondary nav-user-btn" style="display:inline-flex; align-items:center; gap:10px; padding:4px 16px 4px 6px; border-radius:30px;">
                <div class="nav-avatar-wrapper" style="width:34px; height:34px; border-radius:50%; overflow:hidden; flex-shrink:0; border:2px solid #729C51; box-shadow:0 0 10px rgba(114, 156, 81, 0.4), 0 2px 6px rgba(0,0,0,0.12);">
                    <img src="<?php echo $nav_avatar; ?>" alt="<?php echo htmlspecialchars($user_name); ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
                </div>
                <span class="user-name-text" style="font-weight:600; font-size:0.88rem;"><?php echo htmlspecialchars($user_name); ?></span>
            </a>
            <a href="<?php echo $dashboard_url; ?>" class="btn btn-primary nav-dashboard-btn">
                <span>Dashboard</span> <i data-lucide="arrow-right"></i>
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary nav-login-btn">Masuk</a>
        <?php endif; ?>
    </div>
</nav>


<!-- Hero Section (Full Viewport 100vh) -->
<header id="beranda" class="hero-section">

    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-badge">
                <i data-lucide="sprout"></i> Smart Greenhouse System
            </div>
            <h1 class="hero-title">
                <span class="hero-title-single">Pantau Greenhouse Anda</span> <br><em>Secara Real-Time</em>
            </h1>
            <p class="hero-desc">
                MicroClimeet membantu meningkatkan hasil budidaya tanaman premium Anda dengan pemantauan parameter mikroklimat presisi tinggi berbasis IoT, analisis data instan, dan sistem peringatan dini otomatis.
            </p>
            <div class="hero-cta">
                <?php if ($is_logged_in): ?>
                    <a href="<?php echo $dashboard_url; ?>" class="btn btn-primary">
                        Ke Dashboard <i data-lucide="arrow-right"></i>
                    </a>
                <?php else: ?>
                    <a href="signup.php" class="btn btn-primary">
                        Beli Langganan <i data-lucide="arrow-right"></i>
                    </a>
                <?php endif; ?>
                <a href="https://wa.me/6283829634058?text=Halo%20Admin%20MicroClimeet,%20saya%20tertarik%20dengan%20produk%20ini." target="_blank" class="btn btn-secondary">
                    <i data-lucide="message-circle"></i> Hubungi WhatsApp
                </a>
            </div>
            <div class="hero-stats-row">
                <div class="hero-stat-badge"><i data-lucide="activity"></i> 99.8% Uptime</div>
                <div class="hero-stat-badge"><i data-lucide="wifi"></i> Live Cloud Sync</div>
                <div class="hero-stat-badge"><i data-lucide="shield-alert"></i> Alert Otomatis</div>
            </div>
        </div>
        <div class="hero-image-container">
            <div class="hero-image-wrapper">
                <img src="assets/images/products/microclimeet.png" alt="MicroClimeet IoT Device" class="hero-img">
            </div>
        </div>
    </div>
</header>

<!-- Partner & Support Infinite Marquee Loop Section -->
<section class="marquee-section">
    <div class="marquee-label">DIDUKUNG OLEH & MITRA KOLABORASI</div>
    <div class="marquee-container">
        <div class="marquee-track">
            <!-- Half 1 (Set A) -->
            <div class="marquee-item"><img src="assets/images/brand/disaintek.png" alt="Diktisaintek Berdampak"></div>
            <div class="marquee-item"><img src="assets/images/brand/uns.png" alt="Universitas Sebelas Maret"></div>
            <div class="marquee-item"><img src="assets/images/brand/ptik.png" alt="PTIK UNS"></div>
            <div class="marquee-item"><img src="assets/images/brand/edutic.png" alt="Edutic.id"></div>
            <div class="marquee-item"><img src="assets/images/brand/disaintek.png" alt="Diktisaintek Berdampak"></div>
            <div class="marquee-item"><img src="assets/images/brand/uns.png" alt="Universitas Sebelas Maret"></div>
            <div class="marquee-item"><img src="assets/images/brand/ptik.png" alt="PTIK UNS"></div>
            <div class="marquee-item"><img src="assets/images/brand/edutic.png" alt="Edutic.id"></div>

            <!-- Half 2 (Set B - Identical clone for 100% seamless infinite loop) -->
            <div class="marquee-item"><img src="assets/images/brand/disaintek.png" alt="Diktisaintek Berdampak"></div>
            <div class="marquee-item"><img src="assets/images/brand/uns.png" alt="Universitas Sebelas Maret"></div>
            <div class="marquee-item"><img src="assets/images/brand/ptik.png" alt="PTIK UNS"></div>
            <div class="marquee-item"><img src="assets/images/brand/edutic.png" alt="Edutic.id"></div>
            <div class="marquee-item"><img src="assets/images/brand/disaintek.png" alt="Diktisaintek Berdampak"></div>
            <div class="marquee-item"><img src="assets/images/brand/uns.png" alt="Universitas Sebelas Maret"></div>
            <div class="marquee-item"><img src="assets/images/brand/ptik.png" alt="PTIK UNS"></div>
            <div class="marquee-item"><img src="assets/images/brand/edutic.png" alt="Edutic.id"></div>
        </div>
    </div>
</section>

<!-- Mini Realtime Monitoring Dashboard Section (Data Live Sync dari Dashboard Admin) -->
<section id="mini-realtime-dashboard" class="section-wrapper reveal" style="padding-top: 10px; padding-bottom: 30px;">
    <!-- Section Header (Konsisten dengan Typography & Style Bagian Lain) -->
    <div class="section-header text-center" style="margin-bottom: 28px;">
        <span class="section-tag"><i data-lucide="activity"></i> Dashboard Mini</span>
        <h2 class="section-title">Monitoring Realtime <em>Ekosistem</em></h2>
        <p class="section-desc">Statistik aktif petani terdaftar dan jaringan perangkat IoT yang terhubung secara live dengan Database Server MicroClimeet.</p>
    </div>
    <!-- Main Outer Container Card -->
    <div class="glass-card" style="padding: 32px; border-radius: var(--radius-xl); background: rgba(255, 255, 255, 0.96); box-shadow: 0 16px 40px rgba(0,0,0,0.06); border: 1.5px solid rgba(13,93,49,0.18);">

        <!-- Metric Cards Grid (Gaya Modern Dashboard Sesuai Gambar Referensi) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px;">

            <!-- Card 1: Total Petani -->
            <div style="position: relative; background: #ffffff; border-radius: 18px; padding: 18px 20px; border: 1px solid rgba(13,93,49,0.15); box-shadow: 0 4px 18px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;">
                <!-- Left Vertical Color Bar -->
                <div style="position: absolute; left: 0; top: 12px; bottom: 12px; width: 4px; border-radius: 0 4px 4px 0; background: #0D5D31;"></div>

                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; padding-left: 6px;">
                    <span style="font-size: 0.82rem; font-weight: 700; color: #475569; font-family: var(--font-main);">Total Petani</span>
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(13, 93, 49, 0.12); color: #0D5D31; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="users" style="width: 18px; height: 18px;"></i>
                    </div>
                </div>

                <div style="padding-left: 6px;">
                    <div style="font-size: 1.85rem; font-weight: 900; font-family: var(--font-heading); color: #0f172a; line-height: 1; margin-bottom: 4px;">
                        <?php echo $live_total_petani; ?>
                    </div>
                    <div style="font-size: 0.76rem; font-weight: 600; color: #64748b;">
                        Petani Terverifikasi
                    </div>
                </div>
            </div>

            <!-- Card 2: Total Perangkat -->
            <div style="position: relative; background: #ffffff; border-radius: 18px; padding: 18px 20px; border: 1px solid rgba(2,132,199,0.18); box-shadow: 0 4px 18px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;">
                <!-- Left Vertical Color Bar -->
                <div style="position: absolute; left: 0; top: 12px; bottom: 12px; width: 4px; border-radius: 0 4px 4px 0; background: #0284c7;"></div>

                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; padding-left: 6px;">
                    <span style="font-size: 0.82rem; font-weight: 700; color: #475569; font-family: var(--font-main);">Total Perangkat</span>
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(2, 132, 199, 0.12); color: #0284c7; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="cpu" style="width: 18px; height: 18px;"></i>
                    </div>
                </div>

                <div style="padding-left: 6px;">
                    <div style="font-size: 1.85rem; font-weight: 900; font-family: var(--font-heading); color: #0f172a; line-height: 1; margin-bottom: 4px;">
                        <?php echo $live_total_devices; ?>
                    </div>
                    <div style="font-size: 0.76rem; font-weight: 600; color: #64748b;">
                        Greenhouse Aktif
                    </div>
                </div>
            </div>

            <!-- Card 3: Melon Premium -->
            <div style="position: relative; background: #ffffff; border-radius: 18px; padding: 18px 20px; border: 1px solid rgba(22,163,74,0.18); box-shadow: 0 4px 18px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;">
                <!-- Left Vertical Color Bar -->
                <div style="position: absolute; left: 0; top: 12px; bottom: 12px; width: 4px; border-radius: 0 4px 4px 0; background: #16a34a;"></div>

                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; padding-left: 6px;">
                    <span style="font-size: 0.82rem; font-weight: 700; color: #475569; font-family: var(--font-main);">Melon Premium</span>
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(22, 163, 74, 0.12); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        🍈
                    </div>
                </div>

                <div style="padding-left: 6px;">
                    <div style="font-size: 1.85rem; font-weight: 900; font-family: var(--font-heading); color: #0f172a; line-height: 1; margin-bottom: 4px;">
                        <?php echo $commodity_counts['Melon Premium'] ?? 0; ?>
                    </div>
                    <div style="font-size: 0.76rem; font-weight: 600; color: #64748b;">
                        Perangkat Terhubung
                    </div>
                </div>
            </div>

            <!-- Card 4: Paprika -->
            <div style="position: relative; background: #ffffff; border-radius: 18px; padding: 18px 20px; border: 1px solid rgba(220,38,38,0.18); box-shadow: 0 4px 18px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;">
                <!-- Left Vertical Color Bar -->
                <div style="position: absolute; left: 0; top: 12px; bottom: 12px; width: 4px; border-radius: 0 4px 4px 0; background: #dc2626;"></div>

                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; padding-left: 6px;">
                    <span style="font-size: 0.82rem; font-weight: 700; color: #475569; font-family: var(--font-main);">Paprika</span>
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(220, 38, 38, 0.12); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        🫑
                    </div>
                </div>

                <div style="padding-left: 6px;">
                    <div style="font-size: 1.85rem; font-weight: 900; font-family: var(--font-heading); color: #0f172a; line-height: 1; margin-bottom: 4px;">
                        <?php echo $commodity_counts['Paprika'] ?? 0; ?>
                    </div>
                    <div style="font-size: 0.76rem; font-weight: 600; color: #64748b;">
                        Perangkat Terhubung
                    </div>
                </div>
            </div>

            <!-- Card 5: Tomat Ceri -->
            <div style="position: relative; background: #ffffff; border-radius: 18px; padding: 18px 20px; border: 1px solid rgba(217,119,6,0.18); box-shadow: 0 4px 18px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;">
                <!-- Left Vertical Color Bar -->
                <div style="position: absolute; left: 0; top: 12px; bottom: 12px; width: 4px; border-radius: 0 4px 4px 0; background: #d97706;"></div>

                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; padding-left: 6px;">
                    <span style="font-size: 0.82rem; font-weight: 700; color: #475569; font-family: var(--font-main);">Tomat Ceri</span>
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(217, 119, 6, 0.12); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        🍅
                    </div>
                </div>

                <div style="padding-left: 6px;">
                    <div style="font-size: 1.85rem; font-weight: 900; font-family: var(--font-heading); color: #0f172a; line-height: 1; margin-bottom: 4px;">
                        <?php echo $commodity_counts['Tomat Ceri'] ?? 0; ?>
                    </div>
                    <div style="font-size: 0.76rem; font-weight: 600; color: #64748b;">
                        Perangkat Terhubung
                    </div>
                </div>
            </div>

            <!-- Card 6: Stroberi -->
            <div style="position: relative; background: #ffffff; border-radius: 18px; padding: 18px 20px; border: 1px solid rgba(147,51,234,0.18); box-shadow: 0 4px 18px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;">
                <!-- Left Vertical Color Bar -->
                <div style="position: absolute; left: 0; top: 12px; bottom: 12px; width: 4px; border-radius: 0 4px 4px 0; background: #9333ea;"></div>

                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; padding-left: 6px;">
                    <span style="font-size: 0.82rem; font-weight: 700; color: #475569; font-family: var(--font-main);">Stroberi</span>
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(147, 51, 234, 0.12); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        🍓
                    </div>
                </div>

                <div style="padding-left: 6px;">
                    <div style="font-size: 1.85rem; font-weight: 900; font-family: var(--font-heading); color: #0f172a; line-height: 1; margin-bottom: 4px;">
                        <?php echo $commodity_counts['Stroberi'] ?? 0; ?>
                    </div>
                    <div style="font-size: 0.76rem; font-weight: 600; color: #64748b;">
                        Perangkat Terhubung
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Problem & Solution Section -->
<section id="fitur" class="section-wrapper reveal">
    <div class="section-header">
        <span class="section-tag"><i data-lucide="alert-triangle"></i> Tantangan & Solusi</span>
        <h2 class="section-title">Mengapa Menggunakan <em>MicroClimeet?</em></h2>
        <p class="section-desc">Budidaya tanaman premium (melon premium, tomat ceri, paprika, stroberi) membutuhkan stabilitas lingkungan yang mutlak untuk menjaga kualitas panen.</p>
    </div>

    <div class="problems-solutions-grid">
        <!-- Problems Card -->
        <div class="ps-card problems-box">
            <!-- Content on the left -->
            <div class="ps-content">
                <h3 class="ps-card-title">Metode Manual Berisiko Tinggi</h3>
                <ul class="ps-list">
                    <li>
                        <svg class="ai-icon ai-cross" viewBox="0 0 24 24" fill="none" xmlns="http://www/w3.org/2000/svg">
                            <defs>
                                <linearGradient id="crossGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#FFA1A1" />
                                    <stop offset="100%" stop-color="#EF4444" />
                                </linearGradient>
                                <filter id="crossGlow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#EF4444" flood-opacity="0.25" />
                                </filter>
                            </defs>
                            <circle cx="12" cy="12" r="10" fill="url(#crossGrad)" fill-opacity="0.1" stroke="url(#crossGrad)" stroke-width="1.5" />
                            <path d="M9 9L15 15M15 9L9 15" stroke="url(#crossGrad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" filter="url(#crossGlow)" />
                        </svg>
                        <div>
                            <strong>Pemantauan Manual Lambat:</strong> Harus datang ke lokasi secara langsung untuk memeriksa kondisi suhu dan kelembaban.
                        </div>
                    </li>
                    <li>
                        <svg class="ai-icon ai-cross" viewBox="0 0 24 24" fill="none" xmlns="http://www/w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" fill="url(#crossGrad)" fill-opacity="0.1" stroke="url(#crossGrad)" stroke-width="1.5" />
                            <path d="M9 9L15 15M15 9L9 15" stroke="url(#crossGrad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" filter="url(#crossGlow)" />
                        </svg>
                        <div>
                            <strong>Perubahan Cuaca Instan:</strong> Keterlambatan mengetahui perubahan ekstrim dapat menyebabkan stres pada tanaman.
                        </div>
                    </li>
                    <li>
                        <svg class="ai-icon ai-cross" viewBox="0 0 24 24" fill="none" xmlns="http://www/w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" fill="url(#crossGrad)" fill-opacity="0.1" stroke="url(#crossGrad)" stroke-width="1.5" />
                            <path d="M9 9L15 15M15 9L9 15" stroke="url(#crossGrad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" filter="url(#crossGlow)" />
                        </svg>
                        <div>
                            <strong>Kerugian Finansial:</strong> Kegagalan mengendalikan parameter mikroklimat berakibat langsung pada gagal panen premium.
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Badge on the right (middle of the grid) -->
            <div class="ps-badge">
                <span class="ps-badge-text">TANTANGAN</span>
                <div class="ps-badge-icon-wrap">
                    <svg class="ps-badge-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www/w3.org/2000/svg">
                        <defs>
                            <linearGradient id="thumbDownGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#FF8A8A" />
                                <stop offset="100%" stop-color="#EF4444" />
                            </linearGradient>
                            <filter id="thumbDownGlow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#EF4444" flood-opacity="0.3" />
                            </filter>
                        </defs>
                        <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3" stroke="url(#thumbDownGrad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" filter="url(#thumbDownGlow)" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Solutions Card -->
        <div class="ps-card solutions-box">
            <!-- Badge on the left (middle of the grid) -->
            <div class="ps-badge">
                <div class="ps-badge-icon-wrap">
                    <svg class="ps-badge-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www/w3.org/2000/svg">
                        <defs>
                            <linearGradient id="thumbUpGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#A7F3D0" />
                                <stop offset="100%" stop-color="#10B981" />
                            </linearGradient>
                            <filter id="thumbUpGlow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#10B981" flood-opacity="0.3" />
                            </filter>
                        </defs>
                        <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3" stroke="url(#thumbUpGrad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" filter="url(#thumbUpGlow)" />
                    </svg>
                </div>
                <span class="ps-badge-text">SOLUSI</span>
            </div>

            <!-- Content on the right -->
            <div class="ps-content">
                <h3 class="ps-card-title">Solusi Otomatis MicroClimeet</h3>
                <ul class="ps-list">
                    <li>
                        <svg class="ai-icon ai-check" viewBox="0 0 24 24" fill="none" xmlns="http://www/w3.org/2000/svg">
                            <defs>
                                <linearGradient id="checkGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#A7F3D0" />
                                    <stop offset="100%" stop-color="#10B981" />
                                </linearGradient>
                                <filter id="checkGlow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#10B981" flood-opacity="0.25" />
                                </filter>
                            </defs>
                            <circle cx="12" cy="12" r="10" fill="url(#checkGrad)" fill-opacity="0.1" stroke="url(#checkGrad)" stroke-width="1.5" />
                            <path d="M8 12L11 15L16 9" stroke="url(#checkGrad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" filter="url(#checkGlow)" />
                        </svg>
                        <div>
                            <strong>Pemantauan Jarak Jauh:</strong> Akses parameter penting dari mana saja menggunakan smartphone atau laptop.
                        </div>
                    </li>
                    <li>
                        <svg class="ai-icon ai-check" viewBox="0 0 24 24" fill="none" xmlns="http://www/w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" fill="url(#checkGrad)" fill-opacity="0.1" stroke="url(#checkGrad)" stroke-width="1.5" />
                            <path d="M8 12L11 15L16 9" stroke="url(#checkGrad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" filter="url(#checkGlow)" />
                        </svg>
                        <div>
                            <strong>Sistem Peringatan Dini:</strong> Dapatkan notifikasi peringatan saat suhu, kelembapan, udara, atau UV keluar dari batas ideal.
                        </div>
                    </li>
                    <li>
                        <svg class="ai-icon ai-check" viewBox="0 0 24 24" fill="none" xmlns="http://www/w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" fill="url(#checkGrad)" fill-opacity="0.1" stroke="url(#checkGrad)" stroke-width="1.5" />
                            <path d="M8 12L11 15L16 9" stroke="url(#checkGrad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" filter="url(#checkGlow)" />
                        </svg>
                        <div>
                            <strong>Data Historis Lengkap:</strong> Evaluasi riwayat mingguan/harian untuk optimalisasi pola pemeliharaan tanaman.
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Product & Sensor Showcase Section -->
<section class="section-wrapper reveal">
    <div class="showcase-grid">
        <div>
            <span class="section-tag"><i data-lucide="cpu"></i> Spesifikasi Teknologi</span>
            <h2 class="section-title">Perangkat IoT & Sensor <em>Presisi Tinggi</em></h2>
            <p class="section-desc" style="margin-bottom: 24px;">
                MicroClimeet menggunakan mikrokontroler hemat energi berbasis <strong>ESP32</strong> dengan konektivitas Wi-Fi terintegrasi, ditenagai oleh <strong>panel surya</strong> mandiri untuk operasional berkelanjutan.
            </p>
            <ul class="device-features">
                <li><i data-lucide="sun" style="color: var(--accent-orange);"></i> Didukung Baterai & Panel Surya (Ramah Lingkungan)</li>
                <li><i data-lucide="smartphone" style="color: var(--accent-color);"></i> Koneksi Wi-Fi Instan ke Cloud Realtime Database</li>
                <li><i data-lucide="monitor" style="color: var(--accent-color);"></i> Display LCD Fisik 20x4 di lokasi greenhouse</li>
                <li><i data-lucide="shield-check" style="color: var(--accent-color);"></i> Casing kokoh hasil 3D Print pelindung cuaca</li>
            </ul>
        </div>

        <div class="sensors-grid">
            <!-- Sensor 1: Suhu Udara -->
            <div class="glass-card sensor-card p-6 rounded-2xl flex flex-col relative overflow-hidden transition-all duration-500 cursor-pointer">
                <div class="sensor-header-row flex items-center justify-between mb-3">
                    <div class="sensor-icon w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-800 flex items-center justify-center transition-all duration-300">
                        <i data-lucide="thermometer" class="w-5 h-5"></i>
                    </div>
                    <span class="sensor-chip-tag text-[11px] font-extrabold px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-800 border border-emerald-500/30">BME680</span>
                </div>
                <h4 class="sensor-name font-extrabold text-base md:text-lg text-emerald-950 mb-1.5">Suhu Udara</h4>

                <!-- Sensor Image (above description) -->
                <div class="sensor-img-box w-full relative flex items-center justify-center transition-all duration-500">
                    <div class="sensor-glow absolute w-28 h-28 bg-emerald-400/30 rounded-full blur-xl transition-all duration-500"></div>
                    <img src="assets/images/sensors/BME680.png" alt="Sensor BME680 Suhu Udara" class="sensor-img relative z-10 max-h-36 object-contain transition-all duration-500">
                </div>

                <p class="sensor-desc text-xs md:text-sm text-slate-600 leading-relaxed">
                    Sensor presisi BME680 membaca suhu untuk menjaga kestabilan iklim mikro.
                </p>
            </div>

            <!-- Sensor 2: Kelembapan -->
            <div class="glass-card sensor-card p-6 rounded-2xl flex flex-col relative overflow-hidden transition-all duration-500 cursor-pointer">
                <div class="sensor-header-row flex items-center justify-between mb-3">
                    <div class="sensor-icon w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-800 flex items-center justify-center transition-all duration-300">
                        <i data-lucide="droplet" class="w-5 h-5"></i>
                    </div>
                    <span class="sensor-chip-tag text-[11px] font-extrabold px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-800 border border-emerald-500/30">BME680</span>
                </div>
                <h4 class="sensor-name font-extrabold text-base md:text-lg text-emerald-950 mb-1.5">Kelembapan</h4>

                <!-- Sensor Image (above description) -->
                <div class="sensor-img-box w-full relative flex items-center justify-center transition-all duration-500">
                    <div class="sensor-glow absolute w-28 h-28 bg-emerald-400/30 rounded-full blur-xl transition-all duration-500"></div>
                    <img src="assets/images/sensors/BME680.png" alt="Sensor BME680 Kelembapan" class="sensor-img relative z-10 max-h-36 object-contain transition-all duration-500">
                </div>

                <p class="sensor-desc text-xs md:text-sm text-slate-600 leading-relaxed">
                    Memantau tingkat kelembapan relatif (% RH) guna mencegah jamur & dehidrasi.
                </p>
            </div>

            <!-- Sensor 3: Kualitas Udara -->
            <div class="glass-card sensor-card p-6 rounded-2xl flex flex-col relative overflow-hidden transition-all duration-500 cursor-pointer">
                <div class="sensor-header-row flex items-center justify-between mb-3">
                    <div class="sensor-icon w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-800 flex items-center justify-center transition-all duration-300">
                        <i data-lucide="wind" class="w-5 h-5"></i>
                    </div>
                    <span class="sensor-chip-tag text-[11px] font-extrabold px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-800 border border-emerald-500/30">PMS5003</span>
                </div>
                <h4 class="sensor-name font-extrabold text-base md:text-lg text-emerald-950 mb-1.5">Kualitas Udara</h4>

                <!-- Sensor Image (above description) -->
                <div class="sensor-img-box w-full relative flex items-center justify-center transition-all duration-500">
                    <div class="sensor-glow absolute w-28 h-28 bg-emerald-400/30 rounded-full blur-xl transition-all duration-500"></div>
                    <img src="assets/images/sensors/PMS.png" alt="Sensor PMS5003 Kualitas Udara" class="sensor-img relative z-10 max-h-36 object-contain transition-all duration-500">
                </div>

                <p class="sensor-desc text-xs md:text-sm text-slate-600 leading-relaxed">
                    Sensor PMS5003 membaca partikulat debu/asap untuk kesehatan aerasi tanaman.
                </p>
            </div>

            <!-- Sensor 4: Indeks UV -->
            <div class="glass-card sensor-card p-6 rounded-2xl flex flex-col relative overflow-hidden transition-all duration-500 cursor-pointer">
                <div class="sensor-header-row flex items-center justify-between mb-3">
                    <div class="sensor-icon w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-800 flex items-center justify-center transition-all duration-300">
                        <i data-lucide="sun-medium" class="w-5 h-5"></i>
                    </div>
                    <span class="sensor-chip-tag text-[11px] font-extrabold px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-800 border border-emerald-500/30">GUVA-S12SD</span>
                </div>
                <h4 class="sensor-name font-extrabold text-base md:text-lg text-emerald-950 mb-1.5">Indeks UV</h4>

                <!-- Sensor Image (above description) -->
                <div class="sensor-img-box w-full relative flex items-center justify-center transition-all duration-500">
                    <div class="sensor-glow absolute w-28 h-28 bg-emerald-400/30 rounded-full blur-xl transition-all duration-500"></div>
                    <img src="assets/images/sensors/GUVA.png" alt="Sensor GUVA-S12SD Indeks UV" class="sensor-img relative z-10 max-h-36 object-contain transition-all duration-500">
                </div>

                <p class="sensor-desc text-xs md:text-sm text-slate-600 leading-relaxed">
                    Mendeteksi tingkat radiasi ultraviolet (GUVA-S12SD) untuk optimalisasi fotosintesis.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Interactive Website Showcase Carousel Section -->
<section id="showcase-carousel" class="section-wrapper reveal">
    <div class="section-header">
        <span class="section-tag"><i data-lucide="monitor"></i> Pratinjau Dashboard</span>
        <h2 class="section-title">Antarmuka Monitoring <em>Modern & Presisi</em></h2>
        <p class="section-desc">Jelajahi fitur utama platform website MicroClimeet yang dirancang khusus untuk kenyamanan dan presisi analisis pemantauan petani hortikultura.</p>
    </div>

    <div class="carousel-wrapper">
        <div class="glass-carousel">
            <div class="carousel-track-container">
                <div class="carousel-track">

                    <!-- Slide 1: Live Monitoring Sensor (Real Petani Dashboard Replica) -->
                    <div class="carousel-slide active">
                        <div class="carousel-slide-content">
                            <span class="carousel-badge"><i data-lucide="activity"></i> Fitur 01</span>
                            <h3 class="carousel-slide-title">Dashboard Live Telemetri Sensor</h3>
                            <p class="carousel-slide-desc">Pantau 4 parameter mikro utama (Suhu, Kelembaban, Kualitas Udara, UV) secara instan melalui kartu widget glassmorphic yang responsif dan terhubung ke IoT ESP32.</p>
                            <ul class="carousel-feature-list">
                                <li><i data-lucide="check-circle-2"></i> Update data real-time tanpa reload halaman</li>
                                <li><i data-lucide="check-circle-2"></i> Indikator status presisi (Optimal / Waspada / Kritis)</li>
                                <li><i data-lucide="check-circle-2"></i> Tampilan ultra-clean di mobile &amp; desktop</li>
                            </ul>
                            <a href="signup.php" class="btn btn-primary" style="align-self: flex-start;">Uji Coba Demo Live</a>
                        </div>
                        <div class="carousel-slide-visual">
                            <div class="mockup-window">
                                <div class="mockup-header">
                                    <span class="mockup-dot red"></span>
                                    <span class="mockup-dot yellow"></span>
                                    <span class="mockup-dot green"></span>
                                    <span class="mockup-address">microclimeet.com/petani/index.php</span>
                                </div>
                                <div class="mockup-body" style="background:#f8faf8;padding:16px;">
                                    <!-- Mini Topbar Replica -->
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid rgba(0,0,0,0.06);">
                                        <div style="display:flex;align-items:center;gap:6px;font-size:0.75rem;font-weight:700;color:#0D5D31;">
                                            <i class="bi bi-house-door-fill" style="color:#0D5D31;"></i> MCM_001_PTIK (ESP32_GH_001)
                                        </div>
                                        <span style="font-size:0.68rem;font-weight:700;padding:2px 8px;border-radius:12px;background:rgba(34,197,94,0.12);color:#15803d;border:1px solid rgba(34,197,94,0.3);display:inline-flex;align-items:center;gap:4px;">
                                            <span style="width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block;"></span> Terhubung Live
                                        </span>
                                    </div>
                                    <!-- 4 Widget Cards Grid (Real Replica) -->
                                    <div class="preview-widget-grid" style="grid-template-columns:repeat(2,1fr);gap:10px;">
                                        <!-- Suhu Udara -->
                                        <div class="preview-mini-card" style="background:#fff;border-radius:12px;padding:12px;border:1px solid rgba(0,0,0,0.08);box-shadow:0 2px 6px rgba(0,0,0,0.02);">
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                                <span style="font-size:0.7rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;">Suhu Udara</span>
                                                <i data-lucide="thermometer" style="width:13px;height:13px;color:#0D5D31;"></i>
                                            </div>
                                            <div style="font-size:1.3rem;font-weight:800;color:#0D5D31;line-height:1;">26.9 <span style="font-size:0.72rem;font-weight:600;">°C</span></div>
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;font-size:0.68rem;">
                                                <span style="background:rgba(34,197,94,0.12);color:#15803d;padding:1px 6px;border-radius:8px;font-weight:700;">OPTIMAL</span>
                                                <span style="color:var(--text-secondary);">Target: 20–30°C</span>
                                            </div>
                                        </div>
                                        <!-- Kelembapan -->
                                        <div class="preview-mini-card" style="background:#fff;border-radius:12px;padding:12px;border:1px solid rgba(0,0,0,0.08);box-shadow:0 2px 6px rgba(0,0,0,0.02);">
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                                <span style="font-size:0.7rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;">Kelembapan</span>
                                                <i data-lucide="droplets" style="width:13px;height:13px;color:#0284c7;"></i>
                                            </div>
                                            <div style="font-size:1.3rem;font-weight:800;color:#0284c7;line-height:1;">65.8 <span style="font-size:0.72rem;font-weight:600;">% RH</span></div>
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;font-size:0.68rem;">
                                                <span style="background:rgba(34,197,94,0.12);color:#15803d;padding:1px 6px;border-radius:8px;font-weight:700;">OPTIMAL</span>
                                                <span style="color:var(--text-secondary);">Target: 60–85%</span>
                                            </div>
                                        </div>
                                        <!-- Kualitas Udara -->
                                        <div class="preview-mini-card" style="background:#fff;border-radius:12px;padding:12px;border:1px solid rgba(0,0,0,0.08);box-shadow:0 2px 6px rgba(0,0,0,0.02);">
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                                <span style="font-size:0.7rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;">Kualitas Udara</span>
                                                <i data-lucide="wind" style="width:13px;height:13px;color:#16a34a;"></i>
                                            </div>
                                            <div style="font-size:1.3rem;font-weight:800;color:#16a34a;line-height:1;">35 <span style="font-size:0.72rem;font-weight:600;">PM2.5</span></div>
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;font-size:0.68rem;">
                                                <span style="background:rgba(34,197,94,0.12);color:#15803d;padding:1px 6px;border-radius:8px;font-weight:700;">OPTIMAL</span>
                                                <span style="color:var(--text-secondary);">Target: 0–40 PM2.5</span>
                                            </div>
                                        </div>
                                        <!-- Indeks UV -->
                                        <div class="preview-mini-card" style="background:#fef2f2;border-radius:12px;padding:12px;border:1.5px solid #fca5a5;box-shadow:0 2px 6px rgba(239,68,68,0.08);">
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                                <span style="font-size:0.7rem;font-weight:700;color:#991b1b;text-transform:uppercase;">Indeks UV</span>
                                                <i data-lucide="sun" style="width:13px;height:13px;color:#dc2626;"></i>
                                            </div>
                                            <div style="font-size:1.3rem;font-weight:800;color:#dc2626;line-height:1;">10.8 <span style="font-size:0.72rem;font-weight:600;">UV Index</span></div>
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;font-size:0.68rem;">
                                                <span style="background:rgba(239,68,68,0.15);color:#b91c1c;padding:1px 6px;border-radius:8px;font-weight:700;">KRITIS</span>
                                                <span style="color:#7f1d1d;">Target: 0–5 UV</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2: Timeline Siklus Tanam & Fase Pertumbuhan (FITUR BARU) -->
                    <div class="carousel-slide">
                        <div class="carousel-slide-content">
                            <span class="carousel-badge" style="background:rgba(13,93,49,0.12);color:#0D5D31;border-color:rgba(13,93,49,0.25);"><i data-lucide="sprout"></i> Fitur 02</span>
                            <h3 class="carousel-slide-title">Timeline Siklus Tanam Presisi</h3>
                            <p class="carousel-slide-desc">Pelacakan fase budidaya tanaman (Penyemaian, Vegetatif, Generatif, Panen) secara proposional lengkap dengan hitung tanggal panen otomatis.</p>
                            <ul class="carousel-feature-list">
                                <li><i data-lucide="check-circle-2"></i> Sinkronisasi dua arah real-time Admin &amp; Petani</li>
                                <li><i data-lucide="check-circle-2"></i> Indikator progress bar fase pertumbuhan otomatis</li>
                                <li><i data-lucide="check-circle-2"></i> Preset komoditas (Melon Premium, Tomat Ceri, Paprika, dll.)</li>
                            </ul>
                            <a href="signup.php" class="btn btn-primary" style="align-self: flex-start;">Kelola Siklus Tanam</a>
                        </div>
                        <div class="carousel-slide-visual">
                            <div class="mockup-window">
                                <div class="mockup-header">
                                    <span class="mockup-dot red"></span>
                                    <span class="mockup-dot yellow"></span>
                                    <span class="mockup-dot green"></span>
                                    <span class="mockup-address">microclimeet.com/petani/crop-cycle</span>
                                </div>
                                <div class="mockup-body" style="background:#f8faf8;padding:16px;">
                                    <div style="background:#fff;border-radius:14px;padding:16px;border:1px solid rgba(0,0,0,0.08);box-shadow:0 4px 12px rgba(0,0,0,0.03);">
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                            <div>
                                                <h4 style="font-family:var(--font-heading);font-weight:800;font-size:0.95rem;color:#0D5D31;margin:0;">Melon Premium (Inthanon)</h4>
                                                <p style="font-size:0.75rem;color:var(--text-secondary);margin:2px 0 0 0;">Fase Vegetatif II — Hari ke-35 dari 75 Hari Target</p>
                                            </div>
                                            <span style="font-size:0.75rem;font-weight:800;color:#0D5D31;background:rgba(13,93,49,0.1);padding:4px 10px;border-radius:20px;">46.6%</span>
                                        </div>
                                        <!-- Progress bar -->
                                        <div style="height:8px;width:100%;background:#e2e8f0;border-radius:10px;overflow:hidden;margin:10px 0;">
                                            <div style="height:100%;width:46.6%;background:linear-gradient(90deg, #10b981, #0D5D31);border-radius:10px;"></div>
                                        </div>
                                        <!-- Phase milestone grid -->
                                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-top:12px;text-align:center;font-size:0.68rem;">
                                            <div style="background:#dcfce7;color:#15803d;padding:6px;border-radius:8px;font-weight:700;">01. Semai ✓</div>
                                            <div style="background:#0D5D31;color:#fff;padding:6px;border-radius:8px;font-weight:700;">02. Vegetatif ▶</div>
                                            <div style="background:#f1f5f9;color:#64748b;padding:6px;border-radius:8px;font-weight:600;">03. Generatif</div>
                                            <div style="background:#f1f5f9;color:#64748b;padding:6px;border-radius:8px;font-weight:600;">04. Panen</div>
                                        </div>
                                        <!-- Date details -->
                                        <div style="margin-top:12px;padding-top:8px;border-top:1px dashed rgba(0,0,0,0.1);display:flex;justify-content:space-between;font-size:0.72rem;color:var(--text-secondary);">
                                            <span><strong>Tanam:</strong> Kamis, 26 Jun 2026</span>
                                            <span><strong>Panen:</strong> Rabu, 09 Sep 2026</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 3: ApexCharts Analytics -->
                    <div class="carousel-slide">
                        <div class="carousel-slide-content">
                            <span class="carousel-badge" style="background:rgba(2,132,199,0.12);color:#0284c7;border-color:rgba(2,132,199,0.25);"><i data-lucide="trending-up"></i> Fitur 03</span>
                            <h3 class="carousel-slide-title">Grafik Analisis Tren Historis</h3>
                            <p class="carousel-slide-desc">Visualisasi grafik tren interaktif berbasis ApexCharts yang memudahkan evaluasi fluktuasi iklim mikro harian hingga mingguan.</p>
                            <ul class="carousel-feature-list">
                                <li><i data-lucide="check-circle-2"></i> Mode kurva halus &amp; tooltip data presisi</li>
                                <li><i data-lucide="check-circle-2"></i> Filter interval waktu harian, mingguan, &amp; bulanan</li>
                                <li><i data-lucide="check-circle-2"></i> Penandaan batas ambang (*threshold*) ideal</li>
                            </ul>
                            <a href="signup.php" class="btn btn-primary" style="align-self: flex-start;">Pelajari Analytics</a>
                        </div>
                        <div class="carousel-slide-visual">
                            <div class="mockup-window">
                                <div class="mockup-header">
                                    <span class="mockup-dot red"></span>
                                    <span class="mockup-dot yellow"></span>
                                    <span class="mockup-dot green"></span>
                                    <span class="mockup-address">microclimeet.com/petani/history.php</span>
                                </div>
                                <div class="mockup-body" style="background:#fff;padding:16px;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                        <span style="font-size:0.78rem;font-weight:800;color:#0D5D31;">Telemetri Suhu &amp; Kelembapan (24 Jam)</span>
                                        <span style="font-size:0.68rem;color:var(--text-secondary);background:#f1f5f9;padding:2px 8px;border-radius:10px;">24 Jam Terakhir</span>
                                    </div>
                                    <!-- High Precision SVG Chart Mockup -->
                                    <svg viewBox="0 0 320 140" style="width:100%;height:130px;overflow:visible;">
                                        <defs>
                                            <linearGradient id="chartGrad1" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#10b981" stop-opacity="0.35" />
                                                <stop offset="100%" stop-color="#10b981" stop-opacity="0.0" />
                                            </linearGradient>
                                            <linearGradient id="chartGrad2" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#0284c7" stop-opacity="0.25" />
                                                <stop offset="100%" stop-color="#0284c7" stop-opacity="0.0" />
                                            </linearGradient>
                                        </defs>
                                        <!-- Grid lines -->
                                        <line x1="0" y1="20" x2="320" y2="20" stroke="#f1f5f9" stroke-dasharray="3,3" />
                                        <line x1="0" y1="60" x2="320" y2="60" stroke="#f1f5f9" stroke-dasharray="3,3" />
                                        <line x1="0" y1="100" x2="320" y2="100" stroke="#f1f5f9" stroke-dasharray="3,3" />

                                        <!-- Area 2 (Humidity) -->
                                        <path d="M0,90 Q40,40 80,75 T160,50 T240,65 T320,45 L320,130 L0,130 Z" fill="url(#chartGrad2)" />
                                        <path d="M0,90 Q40,40 80,75 T160,50 T240,65 T320,45" fill="none" stroke="#0284c7" stroke-width="2.5" />

                                        <!-- Area 1 (Temperature) -->
                                        <path d="M0,110 Q40,80 80,95 T160,70 T240,85 T320,60 L320,130 L0,130 Z" fill="url(#chartGrad1)" />
                                        <path d="M0,110 Q40,80 80,95 T160,70 T240,85 T320,60" fill="none" stroke="#10b981" stroke-width="2.5" />

                                        <!-- Active data point highlight -->
                                        <circle cx="160" cy="70" r="4" fill="#10b981" stroke="#fff" stroke-width="2" />
                                    </svg>
                                    <div style="display:flex;justify-content:center;gap:14px;margin-top:8px;font-size:0.7rem;color:var(--text-secondary);">
                                        <span style="display:flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:50%;background:#10b981;"></span> Suhu Udara (°C)</span>
                                        <span style="display:flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:50%;background:#0284c7;"></span> Kelembapan (%RH)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 4: Smart Alerts -->
                    <div class="carousel-slide">
                        <div class="carousel-slide-content">
                            <span class="carousel-badge" style="background:rgba(235,80,17,0.12);color:#eb5011;border-color:rgba(235,80,17,0.25);"><i data-lucide="bell-ring"></i> Fitur 04</span>
                            <h3 class="carousel-slide-title">Notifikasi &amp; Warning System Anti-Spam</h3>
                            <p class="carousel-slide-desc">Sistem deteksi dini yang pintar menyaring lonjakan noise dengan 3x pengecekan database dan 5x notifikasi Web Push ke browser/smartphone.</p>
                            <ul class="carousel-feature-list">
                                <li><i data-lucide="check-circle-2"></i> Peringatan cepat perubahan ekstrim cuaca</li>
                                <li><i data-lucide="check-circle-2"></i> Integrasi Web Push PWA &amp; WhatsApp Alert</li>
                                <li><i data-lucide="check-circle-2"></i> Tindakan mitigasi cepat mencegah gagal panen</li>
                            </ul>
                            <a href="signup.php" class="btn btn-orange" style="align-self: flex-start;">Lihat System Alert</a>
                        </div>
                        <div class="carousel-slide-visual">
                            <div class="mockup-window">
                                <div class="mockup-header">
                                    <span class="mockup-dot red"></span>
                                    <span class="mockup-dot yellow"></span>
                                    <span class="mockup-dot green"></span>
                                    <span class="mockup-address">microclimeet.com/petani/index.php</span>
                                </div>
                                <div class="mockup-body" style="background:#f8faf8;padding:16px;">
                                    <!-- Mini Topbar Replica -->
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid rgba(0,0,0,0.06);">
                                        <div style="display:flex;align-items:center;gap:6px;font-size:0.75rem;font-weight:700;color:#0D5D31;">
                                            <i class="bi bi-house-door-fill" style="color:#0D5D31;"></i> MCM_001_PTIK (ESP32_GH_001)
                                        </div>
                                        <span style="font-size:0.65rem;font-weight:800;padding:2px 8px;border-radius:12px;background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;display:inline-flex;align-items:center;gap:4px;">
                                            <span style="width:6px;height:6px;border-radius:50%;background:#ef4444;display:inline-block;"></span> 1 Peringatan Kritis
                                        </span>
                                    </div>

                                    <!-- Emergency Alert Banner Replica -->
                                    <div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:8px 12px;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
                                        <i data-lucide="bell-ring" style="width:16px;height:16px;color:#dc2626;flex-shrink:0;"></i>
                                        <div style="font-size:0.72rem;color:#7f1d1d;font-weight:700;line-height:1.3;">
                                            🚨 Indeks UV mencapai 10.8 UV Index (Melebihi ambang batas maks 5.0 UV Index)!
                                        </div>
                                    </div>

                                    <!-- 4 Widget Cards Grid (Real Replica with Active Alert Card) -->
                                    <div class="preview-widget-grid" style="grid-template-columns:repeat(2,1fr);gap:8px;">
                                        <!-- Suhu Udara -->
                                        <div class="preview-mini-card" style="background:#fff;border-radius:10px;padding:10px;border:1px solid rgba(0,0,0,0.08);box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                                                <span style="font-size:0.68rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;">Suhu Udara</span>
                                                <i data-lucide="thermometer" style="width:12px;height:12px;color:#0D5D31;"></i>
                                            </div>
                                            <div style="font-size:1.15rem;font-weight:800;color:#0D5D31;line-height:1;">26.9 <span style="font-size:0.65rem;font-weight:600;">°C</span></div>
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:0.64rem;">
                                                <span style="background:rgba(34,197,94,0.12);color:#15803d;padding:1px 5px;border-radius:6px;font-weight:700;">OPTIMAL</span>
                                                <span style="color:var(--text-secondary);">Target: 20–30°C</span>
                                            </div>
                                        </div>
                                        <!-- Kelembapan -->
                                        <div class="preview-mini-card" style="background:#fff;border-radius:10px;padding:10px;border:1px solid rgba(0,0,0,0.08);box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                                                <span style="font-size:0.68rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;">Kelembapan</span>
                                                <i data-lucide="droplets" style="width:12px;height:12px;color:#0284c7;"></i>
                                            </div>
                                            <div style="font-size:1.15rem;font-weight:800;color:#0284c7;line-height:1;">65.8 <span style="font-size:0.65rem;font-weight:600;">% RH</span></div>
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:0.64rem;">
                                                <span style="background:rgba(34,197,94,0.12);color:#15803d;padding:1px 5px;border-radius:6px;font-weight:700;">OPTIMAL</span>
                                                <span style="color:var(--text-secondary);">Target: 60–85%</span>
                                            </div>
                                        </div>
                                        <!-- Kualitas Udara -->
                                        <div class="preview-mini-card" style="background:#fff;border-radius:10px;padding:10px;border:1px solid rgba(0,0,0,0.08);box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                                                <span style="font-size:0.68rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;">Kualitas Udara</span>
                                                <i data-lucide="wind" style="width:12px;height:12px;color:#16a34a;"></i>
                                            </div>
                                            <div style="font-size:1.15rem;font-weight:800;color:#16a34a;line-height:1;">35 <span style="font-size:0.65rem;font-weight:600;">PM2.5</span></div>
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:0.64rem;">
                                                <span style="background:rgba(34,197,94,0.12);color:#15803d;padding:1px 5px;border-radius:6px;font-weight:700;">OPTIMAL</span>
                                                <span style="color:var(--text-secondary);">Target: 0–40 PM2.5</span>
                                            </div>
                                        </div>
                                        <!-- Indeks UV (DANGER ALERT STATE) -->
                                        <div class="preview-mini-card" style="background:#fef2f2;border-radius:10px;padding:10px;border:1.5px solid #fca5a5;box-shadow:0 4px 12px rgba(239,68,68,0.15);">
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                                                <span style="font-size:0.68rem;font-weight:800;color:#991b1b;text-transform:uppercase;">Indeks UV</span>
                                                <i data-lucide="sun" style="width:12px;height:12px;color:#dc2626;"></i>
                                            </div>
                                            <div style="font-size:1.15rem;font-weight:800;color:#dc2626;line-height:1;">10.8 <span style="font-size:0.65rem;font-weight:600;">UV Index</span></div>
                                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:0.64rem;">
                                                <span style="background:rgba(239,68,68,0.2);color:#b91c1c;padding:1px 5px;border-radius:6px;font-weight:800;">KRITIS</span>
                                                <span style="color:#7f1d1d;font-weight:600;">Target: 0–5 UV</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 5: Ambang Batas (Threshold) & Kalibrasi Presisi (FITUR BARU) -->
                    <div class="carousel-slide">
                        <div class="carousel-slide-content">
                            <span class="carousel-badge" style="background:rgba(37,99,235,0.12);color:#2563eb;border-color:rgba(37,99,235,0.25);"><i data-lucide="sliders"></i> Fitur 05</span>
                            <h3 class="carousel-slide-title">Ambang Batas &amp; Kalibrasi Custom</h3>
                            <p class="carousel-slide-desc">Konfigurasi batas aman sensor (min-max) kustom per greenhouse dan fitur kalibrasi offset presisi untuk hasil pemantauan terpercaya.</p>
                            <ul class="carousel-feature-list">
                                <li><i data-lucide="check-circle-2"></i> Custom threshold spesifik per greenhouse / komoditas</li>
                                <li><i data-lucide="check-circle-2"></i> Kalibrasi offset sensor suhu, kelembapan, AQI &amp; UV</li>
                                <li><i data-lucide="check-circle-2"></i> Preset otomatis tanaman premium (Melon, Tomat, dll.)</li>
                            </ul>
                            <a href="signup.php" class="btn btn-primary" style="align-self: flex-start;">Atur Threshold Custom</a>
                        </div>
                        <div class="carousel-slide-visual">
                            <div class="mockup-window">
                                <div class="mockup-header">
                                    <span class="mockup-dot red"></span>
                                    <span class="mockup-dot yellow"></span>
                                    <span class="mockup-dot green"></span>
                                    <span class="mockup-address">microclimeet.com/admin/thresholds.php</span>
                                </div>
                                <div class="mockup-body" style="background:#f8faf8;padding:16px;">
                                    <div style="background:#fff;border-radius:12px;padding:12px 14px;border:1px solid rgba(0,0,0,0.08);margin-bottom:8px;">
                                        <div style="display:flex;justify-content:space-between;font-size:0.75rem;font-weight:700;color:var(--text-primary);margin-bottom:6px;">
                                            <span>Suhu Udara</span>
                                            <span style="color:#0D5D31;">20.00°C – 30.00°C</span>
                                        </div>
                                        <div style="height:6px;background:#e2e8f0;border-radius:6px;overflow:hidden;position:relative;">
                                            <div style="position:absolute;left:25%;width:50%;height:100%;background:#0D5D31;border-radius:6px;"></div>
                                        </div>
                                    </div>
                                    <div style="background:#fff;border-radius:12px;padding:12px 14px;border:1px solid rgba(0,0,0,0.08);margin-bottom:8px;">
                                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.75rem;font-weight:700;color:var(--text-primary);margin-bottom:6px;">
                                            <span>Kelembapan</span>
                                            <span style="color:#0284c7;">60.00% – 85.00%</span>
                                        </div>
                                        <div style="height:6px;background:#e2e8f0;border-radius:6px;overflow:hidden;position:relative;">
                                            <div style="position:absolute;left:40%;width:45%;height:100%;background:#0284c7;border-radius:6px;"></div>
                                        </div>
                                    </div>
                                    <div style="background:rgba(13,93,49,0.06);border-radius:10px;padding:8px 12px;border:1px solid rgba(13,93,49,0.15);font-size:0.72rem;color:#0D5D31;display:flex;align-items:center;justify-content:space-between;">
                                        <span><i data-lucide="sliders" style="width:12px;height:12px;display:inline-block;vertical-align:middle;"></i> Offset Kalibrasi Suhu: <strong>+0.20°C</strong></span>
                                        <span style="font-weight:800;color:#15803d;">Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Controls -->
            <div class="carousel-controls">
                <div class="carousel-dots">
                    <button class="carousel-dot active" aria-label="Slide 1"></button>
                    <button class="carousel-dot" aria-label="Slide 2"></button>
                    <button class="carousel-dot" aria-label="Slide 3"></button>
                    <button class="carousel-dot" aria-label="Slide 4"></button>
                    <button class="carousel-dot" aria-label="Slide 5"></button>
                </div>

                <div class="carousel-arrow-group">
                    <button class="carousel-btn prev" aria-label="Previous slide">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <button class="carousel-btn next" aria-label="Next slide">
                        <i data-lucide="chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

</div>
</div>
</div>
</section>

<!-- Pricing Section -->
<section id="harga" class="section-wrapper reveal">
    <div class="section-header">
        <span class="section-tag"><i data-lucide="tag"></i> Paket Investasi</span>
        <h2 class="section-title">Harga yang Transparan <em>dan Terjangkau</em></h2>
        <p class="section-desc">Mulai demokratisasi teknologi pertanian Anda secara bertahap sesuai dengan skala usaha Anda.</p>
    </div>

    <div class="pricing-grid">
        <!-- Paket 1 -->
        <div class="glass-card price-card">
            <div class="price-header">
                <div class="price-name">Alat Mandiri</div>
                <div class="price-amount">Rp 3.000.000</div>
                <div class="price-period">Sekali Bayar</div>
            </div>
            <ul class="price-features">
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> 1 Set Alat MicroClimeet</li>
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> Layar LCD Fisik di Alat</li>
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> Panel Surya & Baterai</li>
                <li class="unavailable"><i data-lucide="x" style="color: var(--danger-color);"></i> Website Dashboard Monitoring</li>
                <li class="unavailable"><i data-lucide="x" style="color: var(--danger-color);"></i> Notifikasi Peringatan Bahaya</li>
            </ul>
            <a href="https://wa.me/6283829634058?text=Halo%20Admin%20MicroClimeet,%20saya%20ingin%20memesan%20Paket%20Alat%20Mandiri." target="_blank" class="btn btn-secondary" style="width: 100%;">Pesan Sekarang</a>
        </div>

        <!-- Paket 2 (Featured) -->
        <div class="glass-card price-card featured">
            <div class="featured-tag">Paling Populer</div>
            <div class="price-header">
                <div class="price-name">Alat + Pemasangan + Web</div>
                <div class="price-amount">Rp 3.350.000</div>
                <div class="price-period">Alat & Instalasi Terpasang</div>
                <div class="price-period" style="font-weight: 700; margin-top: 5px; color: var(--accent-orange);">+ Langganan Web Rp 150k/bln</div>
            </div>
            <ul class="price-features">
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> 1 Set Alat MicroClimeet</li>
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> Jasa Instalasi di Greenhouse</li>
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> Akses Dashboard Web Real-Time</li>
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> Notifikasi Peringatan via Web</li>
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> Histori Grafik Data Tren</li>
            </ul>
            <a href="https://wa.me/6283829634058?text=Halo%20Admin%20MicroClimeet,%20saya%20ingin%20memesan%20Paket%20Lengkap%20Alat%20+%20Instalasi%20+%20Website%20Monitoring." target="_blank" class="btn btn-orange" style="width: 100%;">Pesan Paket Lengkap</a>
        </div>

        <!-- Paket 3 -->
        <div class="glass-card price-card">
            <div class="price-header">
                <div class="price-name">Alat + Pemasangan</div>
                <div class="price-amount">Rp 3.200.000</div>
                <div class="price-period">Sekali Bayar</div>
            </div>
            <ul class="price-features">
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> 1 Set Alat MicroClimeet</li>
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> Jasa Instalasi & Kalibrasi Awal</li>
                <li><i data-lucide="check" style="color: var(--accent-color);"></i> Layar LCD Fisik di Alat</li>
                <li class="unavailable"><i data-lucide="x" style="color: var(--danger-color);"></i> Website Dashboard Monitoring</li>
                <li class="unavailable"><i data-lucide="x" style="color: var(--danger-color);"></i> Notifikasi Peringatan Jarak Jauh</li>
            </ul>
            <a href="https://wa.me/6283829634058?text=Halo%20Admin%20MicroClimeet,%20saya%20ingin%20memesan%20Paket%20Alat%20+%20Pemasangan." target="_blank" class="btn btn-secondary" style="width: 100%;">Pesan Sekarang</a>
        </div>
    </div>
</section>

<!-- Interactive FAQ Section -->
<section id="faq" class="section-wrapper reveal">
    <div class="section-header">
        <span class="section-tag"><i data-lucide="help-circle"></i> Tanya Jawab</span>
        <h2 class="section-title">Pertanyaan Sering <em>Diajukan (FAQ)</em></h2>
        <p class="section-desc">Temukan jawaban cepat atas pertanyaan seputar penggunaan, garansi, dan instalasi MicroClimeet.</p>
    </div>

    <div style="max-width: 820px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px;">
        <!-- FAQ 1 -->
        <div class="glass-card" style="border-radius: 16px; overflow: hidden; background: rgba(255,255,255,0.95); border: 1px solid rgba(0,0,0,0.06);">
            <button type="button" onclick="toggleFaq(this)" style="width: 100%; padding: 18px 20px; text-align: left; background: transparent; border: none; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: #0D5D31; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <span> Apakah perangkat MicroClimeet mudah dipasang di greenhouse?</span>
                <i data-lucide="chevron-down" class="faq-icon" style="transition: transform 0.3s ease; color: var(--text-secondary);"></i>
            </button>
            <div class="faq-body" style="display: none; padding: 0 20px 18px 20px; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6; border-top: 1px dashed rgba(0,0,0,0.06); padding-top: 12px;">
                Sangat mudah! Alat dirancang *Plug &amp; Play*. Anda cukup memasang unit alat di tiang greenhouse dan menghubungkannya ke sumber listrik/panel surya serta koneksi Wi-Fi. Jika memesan Paket Lengkap, tim kami siap datang menginstalasi langsung ke lokasi Anda.
            </div>
        </div>

        <!-- FAQ 2 -->
        <div class="glass-card" style="border-radius: 16px; overflow: hidden; background: rgba(255,255,255,0.95); border: 1px solid rgba(0,0,0,0.06);">
            <button type="button" onclick="toggleFaq(this)" style="width: 100%; padding: 18px 20px; text-align: left; background: transparent; border: none; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: #0D5D31; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <span> Bagaimana jika sinyal Wi-Fi di greenhouse kurang stabil?</span>
                <i data-lucide="chevron-down" class="faq-icon" style="transition: transform 0.3s ease; color: var(--text-secondary);"></i>
            </button>
            <div class="faq-body" style="display: none; padding: 0 20px 18px 20px; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6; border-top: 1px dashed rgba(0,0,0,0.06); padding-top: 12px;">
                MicroClimeet dilengkapi dengan **Layar OLED Fisik 0.96 Inch** pada unit alat hardware. Jadi meskipun Wi-Fi terputus sementara, data suhu, kelembapan, PM2.5, dan UV tetap tampil secara offline pada layar fisik alat.
            </div>
        </div>

        <!-- FAQ 3 -->
        <div class="glass-card" style="border-radius: 16px; overflow: hidden; background: rgba(255,255,255,0.95); border: 1px solid rgba(0,0,0,0.06);">
            <button type="button" onclick="toggleFaq(this)" style="width: 100%; padding: 18px 20px; text-align: left; background: transparent; border: none; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: #0D5D31; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <span> Apakah ada garansi hardware dan dukungan layanan pelanggan?</span>
                <i data-lucide="chevron-down" class="faq-icon" style="transition: transform 0.3s ease; color: var(--text-secondary);"></i>
            </button>
            <div class="faq-body" style="display: none; padding: 0 20px 18px 20px; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6; border-top: 1px dashed rgba(0,0,0,0.06); padding-top: 12px;">
                Ya, setiap pembelian unit mendapatkan **Garansi Resmi 1 Tahun** dan layanan bantuan teknis tim pengembang via WhatsApp Admin.
            </div>
        </div>

        <!-- FAQ 4 -->
        <div class="glass-card" style="border-radius: 16px; overflow: hidden; background: rgba(255,255,255,0.95); border: 1px solid rgba(0,0,0,0.06);">
            <button type="button" onclick="toggleFaq(this)" style="width: 100%; padding: 18px 20px; text-align: left; background: transparent; border: none; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: #0D5D31; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <span> Bisakah saya mengatur ambang batas (threshold) suhu sesuai kebutuhan tanaman saya?</span>
                <i data-lucide="chevron-down" class="faq-icon" style="transition: transform 0.3s ease; color: var(--text-secondary);"></i>
            </button>
            <div class="faq-body" style="display: none; padding: 0 20px 18px 20px; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6; border-top: 1px dashed rgba(0,0,0,0.06); padding-top: 12px;">
                Tentu saja! Melalui dashboard web MicroClimeet, Anda memiliki akses penuh untuk mengatur nilai batas minimum &amp; maksimum suhu, kelembapan, AQI, dan UV sesuai spesifikasi varietas tanaman yang Anda budidayakan.
            </div>
        </div>
    </div>
</section>

<script>
    function toggleFaq(btn) {
        const body = btn.nextElementSibling;
        const icon = btn.querySelector('.faq-icon');
        const isOpen = body.style.display === 'block';

        document.querySelectorAll('.faq-body').forEach(b => b.style.display = 'none');
        document.querySelectorAll('.faq-icon').forEach(i => i.style.transform = 'rotate(0deg)');

        if (!isOpen) {
            body.style.display = 'block';
            if (icon) icon.style.transform = 'rotate(180deg)';
        }
    }
</script>

<!-- Team / Tentang Kami Section -->
<section id="tentang" class="section-wrapper reveal">
    <div class="section-header">
        <span class="section-tag"><i data-lucide="users"></i> Tim Pengembang</span>
        <h2 class="section-title">Mengenal Lebih Dekat <em>Tim MicroClimeet</em></h2>
        <p class="section-desc">Inovasi smart greenhouse IoT ini dikembangkan oleh tim mahasiswa Prodi Pendidikan Tenknik Informatika dan Komputer Universitas Sebelas Maret (UNS) 2026.</p>
    </div>

    <div class="team-grid">
        <!-- Member 1: Rizal -->
        <div class="team-card card-dark">
            <div class="team-photo-wrapper">
                <img src="assets/images/team/Rizal.png" alt="Muhammad Rizal Alfarisyi" class="team-photo">
            </div>
            <div class="team-info-badge">
                <h4 class="team-name">MUHAMMAD RIZAL ALFARISYI</h4>
                <p class="team-role">Ketua Tim</p>
            </div>
        </div>

        <!-- Member 2: Rosyid -->
        <div class="team-card card-light">
            <div class="team-photo-wrapper">
                <img src="assets/images/team/Rosyid.png" alt="Rosyid Hanafitri Utomo" class="team-photo">
            </div>
            <div class="team-info-badge">
                <h4 class="team-name">ROSYID HANAFITRI UTOMO</h4>
                <p class="team-role">Divisi Perangkat Keras dan Sensor</p>
            </div>
        </div>

        <!-- Member 3: Bitul -->
        <div class="team-card card-dark">
            <div class="team-photo-wrapper">
                <img src="assets/images/team/Bitul.png" alt="'Azzam Tsabitul Jamil" class="team-photo">
            </div>
            <div class="team-info-badge">
                <h4 class="team-name">'AZZAM TSABITUL JAMIL</h4>
                <p class="team-role">Divisi Sistem Membuat program antarmuka Pengguna</p>
            </div>
        </div>

        <!-- Member 4: Teta -->
        <div class="team-card card-light">
            <div class="team-photo-wrapper">
                <img src="assets/images/team/Teta.png" alt="Anggertha Nurrosyid" class="team-photo">
            </div>
            <div class="team-info-badge">
                <h4 class="team-name">ANGGERTHA NURROSYID</h4>
                <p class="team-role">Divisi Pemasaran dan Kemitraan</p>
            </div>
        </div>

        <!-- Member 5: Arum -->
        <div class="team-card card-dark">
            <div class="team-photo-wrapper">
                <img src="assets/images/team/Arum.png" alt="Fathiika Arum Maulida" class="team-photo">
            </div>
            <div class="team-info-badge">
                <h4 class="team-name">FATHIIKA ARUM MAULIDA</h4>
                <p class="team-role">Divisi Keuangan dan Administrasi</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-info">
            <img src="assets/images/brand/logo.png" alt="MicroClimeet Logo">
            <p class="footer-text">
                Smart Greenhouse Monitoring System Berbasis IoT untuk optimalisasi budidaya tanaman hortikultura premium skala kecil hingga menengah.
            </p>
            <p style="font-size: 0.88rem; font-weight: bold; color: var(--text-secondary);">
                Dikembangkan oleh Tim Mahasiswa UNS 2026.
            </p>
        </div>
        <div>
            <h4 class="footer-title">Tautan Cepat</h4>
            <ul class="footer-links">
                <li><a href="#beranda">Beranda</a></li>
                <li><a href="#fitur">Fitur & Sensor</a></li>
                <li><a href="#harga">Harga</a></li>
                <li><a href="#tentang">Tentang Kami</a></li>
            </ul>
        </div>
        <div>
            <h4 class="footer-title">Hubungi Kami</h4>
            <ul class="footer-links">
                <li><i data-lucide="map-pin" style="display: inline-block; vertical-align: middle; height: 16px;"></i> FKIP JPTK UNS Kampus V, Surakarta</li>
                <li><i data-lucide="mail" style="display: inline-block; vertical-align: middle; height: 16px;"></i> microclimeet123@gmail.com</li>
                <li><i data-lucide="phone" style="display: inline-block; vertical-align: middle; height: 16px;"></i> +62 83829634058</li>
            </ul>
        </div>
        <div class="footer-maps-column">
            <h4 class="footer-title">Lokasi Kami</h4>
            <div class="gmaps-iframe-wrapper">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d668.4615961375797!2d110.77380679445514!3d-7.558879788620106!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7a14450edd210d%3A0xa6ea1f9494841e84!2sFKIP%20JPTK%20UNS%20Kampus%20V!5e0!3m2!1sen!2sid!4v1784938431768!5m2!1sen!2sid" width="100%" height="180" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> MicroClimeet. All rights reserved.</p>
        <p>Program Pendanaan Kategori Wirausaha Mahasiswa WIBAWA UNS 2026</p>
    </div>
</footer>

<?php include 'includes/footer.php'; ?>