<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Pantau Greenhouse Anda Secara Real-Time";
$custom_css = ['landing.css'];
$custom_js = ['carousel.js', 'animations.js'];
$base_url = '';
include 'includes/header.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['name'] : '';
$user_role = $is_logged_in ? $_SESSION['role'] : '';
$dashboard_url = ($user_role === 'admin') ? 'admin/index.php' : 'petani/index.php';
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
            <a href="<?php echo $dashboard_url; ?>" class="btn btn-secondary nav-user-btn">
                <i data-lucide="user"></i>
                <span class="user-name-text"><?php echo htmlspecialchars($user_name); ?></span>
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
                <a href="https://wa.me/6281234567890?text=Halo%20Admin%20MicroClimeet,%20saya%20tertarik%20dengan%20produk%20ini." target="_blank" class="btn btn-secondary">
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
                    
                    <!-- Slide 1: Live Monitoring -->
                    <div class="carousel-slide active">
                        <div class="carousel-slide-content">
                            <span class="carousel-badge"><i data-lucide="activity"></i> Fitur 01</span>
                            <h3 class="carousel-slide-title">Dashboard Live Telemetri Sensor</h3>
                            <p class="carousel-slide-desc">Pantau 4 parameter mikro utama (Suhu, Kelembaban, Kualitas Udara, UV) secara instan melalui kartu widget glassmorphic yang responsif.</p>
                            <ul class="carousel-feature-list">
                                <li><i data-lucide="check-circle-2"></i> Update data real-time tanpa reload halaman</li>
                                <li><i data-lucide="check-circle-2"></i> Visual indikator status (Normal / Waspada / Bahaya)</li>
                                <li><i data-lucide="check-circle-2"></i> Tampilan ultra-clean di mobile & desktop</li>
                            </ul>
                            <a href="signup.php" class="btn btn-primary" style="align-self: flex-start;">Uji Coba Demo Live</a>
                        </div>
                        <div class="carousel-slide-visual">
                            <div class="mockup-window">
                                <div class="mockup-header">
                                    <span class="mockup-dot red"></span>
                                    <span class="mockup-dot yellow"></span>
                                    <span class="mockup-dot green"></span>
                                    <span class="mockup-address">microclimeet.com/petani/dashboard</span>
                                </div>
                                <div class="mockup-body">
                                    <div class="preview-widget-grid">
                                        <div class="preview-mini-card">
                                            <div class="preview-card-title">Suhu Udara</div>
                                            <div class="preview-card-val">27.4 °C</div>
                                            <span class="preview-card-status status-normal">● Optimal</span>
                                        </div>
                                        <div class="preview-mini-card">
                                            <div class="preview-card-title">Kelembapan</div>
                                            <div class="preview-card-val">76.2 %</div>
                                            <span class="preview-card-status status-normal">● Ideal</span>
                                        </div>
                                        <div class="preview-mini-card">
                                            <div class="preview-card-title">Air Quality</div>
                                            <div class="preview-card-val">18 AQI</div>
                                            <span class="preview-card-status status-normal">● Bersih</span>
                                        </div>
                                        <div class="preview-mini-card">
                                            <div class="preview-card-title">Indeks UV</div>
                                            <div class="preview-card-val">3.2 UV</div>
                                            <span class="preview-card-status status-normal">● Aman</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2: ApexCharts Analytics -->
                    <div class="carousel-slide">
                        <div class="carousel-slide-content">
                            <span class="carousel-badge"><i data-lucide="trending-up"></i> Fitur 02</span>
                            <h3 class="carousel-slide-title">Grafik Analisis Tren Historis</h3>
                            <p class="carousel-slide-desc">Visualisasi grafik tren interaktif berbasis ApexCharts yang memudahkan evaluasi fluktuasi iklim mikro harian hingga mingguan.</p>
                            <ul class="carousel-feature-list">
                                <li><i data-lucide="check-circle-2"></i> Mode kurva halus & tooltip data presisi</li>
                                <li><i data-lucide="check-circle-2"></i> Zooming interaktif dan analisis riwayat</li>
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
                                    <span class="mockup-address">microclimeet.com/petani/analytics</span>
                                </div>
                                <div class="mockup-body" style="background: #ffffff; padding: 16px; text-align: center;">
                                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 8px;">Tren Suhu & Kelembapan (24 Jam)</div>
                                    <div style="height: 140px; width: 100%; border-bottom: 2px solid var(--accent-color); border-left: 2px solid var(--text-secondary); display: flex; align-items: flex-end; justify-content: space-around; padding: 10px 0;">
                                        <div style="height: 40%; width: 12%; background: rgba(114, 156, 81, 0.4); border-radius: 4px 4px 0 0;"></div>
                                        <div style="height: 65%; width: 12%; background: rgba(114, 156, 81, 0.6); border-radius: 4px 4px 0 0;"></div>
                                        <div style="height: 85%; width: 12%; background: var(--accent-color); border-radius: 4px 4px 0 0;"></div>
                                        <div style="height: 55%; width: 12%; background: rgba(114, 156, 81, 0.5); border-radius: 4px 4px 0 0;"></div>
                                        <div style="height: 70%; width: 12%; background: rgba(114, 156, 81, 0.7); border-radius: 4px 4px 0 0;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 3: Smart Alerts -->
                    <div class="carousel-slide">
                        <div class="carousel-slide-content">
                            <span class="carousel-badge" style="background: rgba(235, 80, 17, 0.12); color: #eb5011; border-color: rgba(235, 80, 17, 0.25);"><i data-lucide="bell-ring"></i> Fitur 03</span>
                            <h3 class="carousel-slide-title">Notifikasi & Warning System</h3>
                            <p class="carousel-slide-desc">Sistem deteksi dini yang langsung mengubah warna kartu dan menampilkan pesan peringatan saat parameter keluar dari ambang batas aman.</p>
                            <ul class="carousel-feature-list">
                                <li><i data-lucide="check-circle-2"></i> Peringatan cepat perubahan ekstrim cuaca</li>
                                <li><i data-lucide="check-circle-2"></i> Indikator visual dengan kode warna ramah mata</li>
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
                                    <span class="mockup-address">microclimeet.com/petani/alerts</span>
                                </div>
                                <div class="mockup-body">
                                    <div class="preview-mini-card" style="background: rgba(235, 80, 17, 0.1); border-color: rgba(235, 80, 17, 0.3); margin-bottom: 12px;">
                                        <div style="display: flex; align-items: center; gap: 8px; color: #eb5011; font-weight: 700; font-size: 0.9rem;">
                                            <i data-lucide="alert-triangle"></i> PERINGATAN WASPADA
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-primary); margin-top: 4px;">Suhu greenhouse mencapai 32.5°C (Melebihi ambang batas maks 30°C).</div>
                                    </div>
                                    <div class="preview-mini-card" style="background: rgba(255, 255, 255, 0.85);">
                                        <div style="display: flex; align-items: center; gap: 8px; color: #55793a; font-weight: 700; font-size: 0.9rem;">
                                            <i data-lucide="check-circle"></i> SENSOR LAINNYA AMAN
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">Kelembaban 72%, Kualitas Udara 15 AQI, UV 2.1.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 4: Admin Control -->
                    <div class="carousel-slide">
                        <div class="carousel-slide-content">
                            <span class="carousel-badge" style="background: rgba(37, 99, 235, 0.12); color: #2563eb; border-color: rgba(37, 99, 235, 0.25);"><i data-lucide="shield-check"></i> Fitur 04</span>
                            <h3 class="carousel-slide-title">Panel Admin & Threshold Control</h3>
                            <p class="carousel-slide-desc">Pusat kendali admin untuk menyetujui registrasi petani baru, memantau log aktivitas pengguna, dan mengonfigurasi batas sensor global.</p>
                            <ul class="carousel-feature-list">
                                <li><i data-lucide="check-circle-2"></i> Approval registrasi petani satu klik</li>
                                <li><i data-lucide="check-circle-2"></i> Log aktivitas audit trail pengguna</li>
                                <li><i data-lucide="check-circle-2"></i> Kalibrasi threshold global terpusat</li>
                            </ul>
                            <a href="login.php" class="btn btn-secondary" style="align-self: flex-start;">Akses Login Admin</a>
                        </div>
                        <div class="carousel-slide-visual">
                            <div class="mockup-window">
                                <div class="mockup-header">
                                    <span class="mockup-dot red"></span>
                                    <span class="mockup-dot yellow"></span>
                                    <span class="mockup-dot green"></span>
                                    <span class="mockup-address">microclimeet.com/admin/overview</span>
                                </div>
                                <div class="mockup-body">
                                    <div class="preview-mini-card" style="margin-bottom: 8px;">
                                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-primary);">Persetujuan Petani (2 Pending)</div>
                                        <div style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 2px;">Pak Budi - b.sutrisno@gmail.com</div>
                                    </div>
                                    <div class="preview-mini-card">
                                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-primary);">System Log Activity</div>
                                        <div style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 2px;">Admin memperbarui threshold suhu max = 30.00°C</div>
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
            <a href="https://wa.me/6281234567890?text=Halo%20Admin%20MicroClimeet,%20saya%20ingin%20memesan%20Paket%20Alat%20Mandiri." target="_blank" class="btn btn-secondary" style="width: 100%;">Pesan Sekarang</a>
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
            <a href="https://wa.me/6281234567890?text=Halo%20Admin%20MicroClimeet,%20saya%20ingin%20memesan%20Paket%20Lengkap%20Alat%20+%20Instalasi%20+%20Website%20Monitoring." target="_blank" class="btn btn-orange" style="width: 100%;">Pesan Paket Lengkap</a>
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
            <a href="https://wa.me/6281234567890?text=Halo%20Admin%20MicroClimeet,%20saya%20ingin%20memesan%20Paket%20Alat%20+%20Pemasangan." target="_blank" class="btn btn-secondary" style="width: 100%;">Pesan Sekarang</a>
        </div>
    </div>
</section>

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
                <li><i data-lucide="phone" style="display: inline-block; vertical-align: middle; height: 16px;"></i> +62 82135986515</li>
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
