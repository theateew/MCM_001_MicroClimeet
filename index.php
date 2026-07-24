<?php
$page_title = "Pantau Greenhouse Anda Secara Real-Time";
$custom_css = ['landing.css'];
$custom_js = ['carousel.js', 'animations.js'];
$base_url = '';
include 'includes/header.php';
?>

<!-- Ambient Glowing Background Blobs -->
<div class="bg-ambient-blob blob-1"></div>
<div class="bg-ambient-blob blob-2"></div>
<div class="bg-ambient-blob blob-3"></div>

<!-- Navbar Navigation -->
<nav class="navbar">
    <div class="nav-brand">
        <img src="assets/images/brand/logo.png" alt="MicroClimeet Logo">
    </div>
    <ul class="nav-links">
        <li><a href="#beranda" class="active">Beranda</a></li>
        <li><a href="#fitur">Fitur & Sensor</a></li>
        <li><a href="#showcase-carousel">Pratinjau Web</a></li>
        <li><a href="#harga">Harga</a></li>
        <li><a href="#tentang">Tentang Kami</a></li>
    </ul>
    <div class="nav-actions">
        <a href="login.php" class="btn btn-secondary">Masuk</a>
        <a href="signup.php" class="btn btn-primary">Coba Demo</a>
    </div>
</nav>

<!-- Hero Section -->
<header id="beranda" class="hero-section">
    <div class="hero-content">
        <div class="hero-badge">
            <i data-lucide="sprout"></i> Smart Greenhouse System
        </div>
        <h1 class="hero-title">
            Pantau Greenhouse Anda <br><em>Secara Real-Time</em>
        </h1>
        <p class="hero-desc">
            MicroClimeet membantu meningkatkan hasil budidaya tanaman premium Anda dengan pemantauan parameter mikroklimat presisi tinggi berbasis IoT, analisis data instan, dan sistem peringatan dini otomatis.
        </p>
        <div class="hero-cta">
            <a href="signup.php" class="btn btn-primary">
                Coba Akun Demo <i data-lucide="arrow-right"></i>
            </a>
            <a href="https://wa.me/6281234567890?text=Halo%20Admin%20MicroClimeet,%20saya%20tertarik%20dengan%20produk%20ini." target="_blank" class="btn btn-secondary">
                <i data-lucide="message-circle" style="color: #25d366;"></i> Hubungi WhatsApp
            </a>
        </div>
        <div class="hero-stats-row">
            <div class="hero-stat-badge"><i data-lucide="activity" style="color: var(--accent-color);"></i> 99.8% Uptime</div>
            <div class="hero-stat-badge"><i data-lucide="wifi" style="color: var(--accent-dark);"></i> Live Cloud Sync</div>
            <div class="hero-stat-badge"><i data-lucide="shield-alert" style="color: var(--accent-orange);"></i> Alert Otomatis</div>
        </div>
        <div class="hero-visual">
            <div class="hero-product-card">
                <div class="product-image-placeholder">
                    <i class="fas fa-microchip"></i>
                    <span>MicroClimeet</span>
                </div>
                <div class="product-badge">Coming Soon</div>
            </div>
        </div>
    </div>
</header>

<!-- Problem & Solution Section -->
<section id="fitur" class="section-wrapper reveal">
    <div class="section-header">
        <span class="section-tag"><i data-lucide="alert-triangle"></i> Tantangan & Solusi</span>
        <h2 class="section-title">Mengapa Menggunakan <em>MicroClimeet?</em></h2>
        <p class="section-desc">Budidaya tanaman premium (melon premium, tomat ceri, paprika, stroberi) membutuhkan stabilitas lingkungan yang mutlak untuk menjaga kualitas panen.</p>
    </div>
    
    <div class="problems-solutions-grid">
        <!-- Problems Card -->
        <div class="glass-card ps-card problems-box">
            <div class="ps-title-row">
                <div class="ps-icon-box">
                    <i data-lucide="alert-triangle"></i>
                </div>
                <h3>Metode Manual Berisiko Tinggi</h3>
            </div>
            <div class="about-image">
                <div class="about-image-placeholder">
                    <i class="fas fa-seedling"></i>
                    <span>Smart Greenhouse</span>
                </div>
            </div>
        </div>
    </div>
</section>
    <!-- ===== PROBLEM ===== -->
    <section id="problem" class="problem">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Masalah</span>
                <h2 class="section-title">Apa yang <span>Mengganggu</span> Petani?</h2>
                <p class="section-desc">
                    Berikut adalah tantangan utama yang sering dihadapi petani greenhouse 
                    dalam menjaga kualitas tanaman premium.
                </p>
            </div>
            <div class="problem-grid">
                <div class="problem-card">
                    <div class="problem-icon">
                        <i class="fas fa-hand"></i>
                    </div>
                </li>
                <li>
                    <i data-lucide="x-circle" style="color: var(--danger-color);"></i>
                    <div>
                        <strong>Perubahan Cuaca Instan:</strong> Keterlambatan mengetahui perubahan ekstrim dapat menyebabkan stres pada tanaman.
                    </div>
                </li>
                <li>
                    <i data-lucide="x-circle" style="color: var(--danger-color);"></i>
                    <div>
                        <strong>Kerugian Finansial:</strong> Kegagalan mengendalikan parameter mikroklimat berakibat langsung pada gagal panen premium.
                    </div>
                </li>
            </ul>
        </div>

        <!-- Solutions Card -->
        <div class="glass-card ps-card solutions-box">
            <div class="ps-title-row">
                <div class="ps-icon-box">
                    <i data-lucide="check-circle-2"></i>
                </div>
                <h3>Solusi Otomatis MicroClimeet</h3>
            </div>
            <ul class="ps-list">
                <li>
                    <i data-lucide="check" style="color: var(--accent-color);"></i>
                    <div>
                        <strong>Pemantauan Jarak Jauh:</strong> Akses parameter penting dari mana saja menggunakan smartphone atau laptop.
                    </div>
                </li>
                <li>
                    <i data-lucide="check" style="color: var(--accent-color);"></i>
                    <div>
                        <strong>Sistem Peringatan Dini:</strong> Dapatkan notifikasi peringatan saat suhu, kelembapan, udara, atau UV keluar dari batas ideal.
                    </div>
                </li>
                <li>
                    <i data-lucide="check" style="color: var(--accent-color);"></i>
                    <div>
                        <strong>Data Historis Lengkap:</strong> Evaluasi riwayat mingguan/harian untuk optimalisasi pola pemeliharaan tanaman.
                    </div>
                </li>
            </ul>
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
            <div class="glass-card sensor-card">
                <div class="sensor-icon">
                    <i data-lucide="thermometer"></i>
                </div>
                <div class="sensor-name">Suhu Udara</div>
                <div class="sensor-desc">Sensor presisi BME680 membaca suhu untuk menjaga kestabilan iklim mikro.</div>
            </div>
            
            <div class="glass-card sensor-card">
                <div class="sensor-icon">
                    <i data-lucide="droplet"></i>
                </div>
                <div class="sensor-name">Kelembapan</div>
                <div class="sensor-desc">Memantau tingkat kelembapan relatif (% RH) guna mencegah jamur & dehidrasi.</div>
            </div>
            
            <div class="glass-card sensor-card">
                <div class="sensor-icon">
                    <i data-lucide="wind"></i>
                </div>
                <div class="sensor-name">Kualitas Udara</div>
                <div class="sensor-desc">Sensor PMS5003 membaca partikulat debu/asap untuk kesehatan aerasi tanaman.</div>
            </div>
            
            <div class="glass-card sensor-card">
                <div class="sensor-icon">
                    <i data-lucide="sun-medium"></i>
                </div>
                <div class="sensor-name">Indeks UV</div>
                <div class="sensor-desc">Mendeteksi tingkat radiasi ultraviolet (GUVA-S12SD) untuk optimalisasi fotosintesis.</div>
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
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== TEAM ===== -->
    <section id="team" class="team">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Tim Kami</span>
                <h2 class="section-title">Di Balik <span>MicroClimeet</span></h2>
                <p class="section-desc">
                    Kami adalah mahasiswa Universitas Sebelas Maret yang berkomitmen 
                    menghadirkan solusi pertanian modern.
                </p>
            </div>
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-image">
                        <div class="team-img-placeholder">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    <div class="team-info">
                        <h3>Muhammad Rizal Alfarisyi</h3>
                        <p class="team-role">Ketua Tim / Penanggung Jawab</p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-image">
                        <div class="team-img-placeholder">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    <div class="team-info">
                        <h3>Rosyid Hanafitri Utomo</h3>
                        <p class="team-role">Divisi Perangkat Keras & Sensor</p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-image">
                        <div class="team-img-placeholder">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    <div class="team-info">
                        <h3>Azzam Tsabitul Jamil</h3>
                        <p class="team-role">Divisi Sistem Monitoring & Antarmuka</p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-image">
                        <div class="team-img-placeholder">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    <div class="team-info">
                        <h3>Anggertha Nurrosyid</h3>
                        <p class="team-role">Divisi Pemasaran & Kemitraan</p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-image">
                        <div class="team-img-placeholder">
                            <i class="fas fa-user-circle"></i>
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

<!-- Footer -->
<footer id="tentang" class="footer">
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
                <li><a href="signup.php">Coba Demo Gratis</a></li>
            </ul>
        </div>
        <div>
            <h4 class="footer-title">Hubungi Kami</h4>
            <ul class="footer-links">
                <li><i data-lucide="map-pin" style="display: inline-block; vertical-align: middle; height: 16px;"></i> Universitas Sebelas Maret, Surakarta</li>
                <li><i data-lucide="mail" style="display: inline-block; vertical-align: middle; height: 16px;"></i> support@microclimeet.com</li>
                <li><i data-lucide="phone" style="display: inline-block; vertical-align: middle; height: 16px;"></i> +62 8XXXXXXXXXX</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> MicroClimeet. All rights reserved.</p>
        <p>Program Pendanaan Kategori Wirausaha Mahasiswa WIBAWA UNS 2026</p>
    </div>
</footer>

<?php include 'includes/footer.php'; ?>
