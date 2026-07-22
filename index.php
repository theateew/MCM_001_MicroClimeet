<?php include 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Smart Greenhouse Monitoring System</title>
    <meta name="description" content="Sistem monitoring greenhouse berbasis IoT untuk optimalisasi budidaya tanaman premium">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- ===== HEADER ===== -->
<header id="header">
    <div class="container">
        <div class="header-inner">
            <div class="logo">
                <a href="#home">
                    <div class="logo-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <span class="logo-text">Micro<span>Climeet</span></span>
                </a>
            </div>
            <nav class="navbar">
                <ul class="nav-menu">
                    <li><a href="#home" class="active">Beranda</a></li>
                    <li><a href="#about">Tentang Kami</a></li>
                    <li><a href="#problem">Masalah</a></li>
                    <li><a href="#solution">Solusi</a></li>
                    <li><a href="#product">Produk</a></li>
                    <li><a href="#features">Fitur</a></li>
                    <li><a href="#how-it-works">Cara Kerja</a></li>
                    <li><a href="#dashboard">Dashboard</a></li>
                    <li><a href="#team">Tim</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="#contact" class="btn-nav">Hubungi</a></li>
                </ul>
                <div class="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </nav>
        </div>
    </div>
</header>

    <!-- ===== HOME / HERO ===== -->
<section id="home" class="hero">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-microchip"></i> IoT Smart Farming
            </div>
            <h1 class="hero-title">
                Pantau Greenhouse Anda<br>
                Secara Real-Time
            </h1>
            <p class="hero-desc">
                MicroClimeet adalah sistem monitoring mikroklimat berbasis IoT yang membantu 
                petani tanaman premium menjaga kestabilan suhu, kelembaban, kualitas udara, 
                dan indeks UV secara praktis dan akurat.
            </p>
            <div class="hero-buttons">
                <a href="#product" class="btn-primary">
                    <i class="fas fa-rocket"></i> Lihat Produk
                </a>
                <a href="#contact" class="btn-secondary">
                    <i class="fas fa-comment"></i> Konsultasi
                </a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number" data-target="13">0</span>
                    <span class="stat-label">Unit Terjual</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-target="10">0</span>
                    <span class="stat-label">Pelanggan Aktif</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-target="98">0</span>
                    <span class="stat-label">% Kepuasan</span>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-product-card">
                <img src="assets/images/homemicro.png" alt="MicroClimeet Logo" class="hero-logo-image">
            </div>
        </div>
    </div>
    <div class="hero-wave">
        <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
            <path d="M0,40 C360,80 720,0 1080,40 C1260,60 1380,30 1440,40 L1440,120 L0,120 Z"/>
        </svg>
    </div>
</section>

    <!-- ===== ABOUT ===== -->
<section id="about" class="about">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Tentang Kami</span>
            <h2 class="section-title">Mengapa <span>MicroClimeet</span>?</h2>
            <p class="section-desc">
                Kami hadir untuk membantu petani Indonesia beralih ke pertanian modern 
                yang lebih presisi, efisien, dan menguntungkan.
            </p>
        </div>
        <div class="about-grid">
            <div class="about-content">
                <div class="about-text">
                    <h3>Solusi Pemantauan Greenhouse Cerdas</h3>
                    <p>
                        MicroClimeet adalah inovasi dari mahasiswa Universitas Sebelas Maret 
                        yang peduli terhadap transformasi digital pertanian Indonesia. 
                        Kami menggabungkan teknologi Internet of Things (IoT) dengan 
                        kebutuhan nyata petani tanaman premium.
                    </p>
                    <div class="about-points">
                        <div class="point">
                            <i class="fas fa-check-circle"></i>
                            <span>Pantau suhu, kelembaban, kualitas udara & UV</span>
                        </div>
                        <div class="point">
                            <i class="fas fa-check-circle"></i>
                            <span>Akses real-time via display alat & website</span>
                        </div>
                        <div class="point">
                            <i class="fas fa-check-circle"></i>
                            <span>Peringatan dini kondisi greenhouse tidak ideal</span>
                        </div>
                        <div class="point">
                            <i class="fas fa-check-circle"></i>
                            <span>Ramah di kantong & mudah digunakan</span>
                        </div>
                    </div>
                    <a href="#product" class="btn-primary">
                        <i class="fas fa-arrow-right"></i> Pelajari Lebih Lanjut
                    </a>
                </div>
            </div>
            <div class="about-image">
                <!-- PERBAIKAN: Gambar About dengan ukuran pas -->
                <img src="assets/images/microclimeet.png" alt="Smart Greenhouse" class="about-image-img">
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
                    <h3>Pemantauan Manual</h3>
                    <p>Petani masih harus datang langsung ke greenhouse untuk mengecek kondisi, tidak efisien dan memakan waktu.</p>
                </div>
                <div class="problem-card">
                    <div class="problem-icon">
                        <i class="fas fa-temperature-high"></i>
                    </div>
                    <h3>Lingkungan Mudah Berubah</h3>
                    <p>Suhu, kelembaban, dan kualitas udara bisa berubah sewaktu-waktu dan sulit diprediksi.</p>
                </div>
                <div class="problem-card">
                    <div class="problem-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>Risiko Kerugian Tinggi</h3>
                    <p>Tanaman premium seperti melon dan stroberi sangat sensitif. Kondisi buruk = kualitas panen menurun.</p>
                </div>
                <div class="problem-card">
                    <div class="problem-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3>Data Sulit Terekam</h3>
                    <p>Tanpa sistem digital, petani kesulitan melihat riwayat data lingkungan untuk evaluasi budidaya.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SOLUTION ===== -->
    <section id="solution" class="solution">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Solusi</span>
                <h2 class="section-title">Ini <span>Jawabannya</span></h2>
                <p class="section-desc">
                    MicroClimeet hadir dengan solusi lengkap untuk semua masalah pemantauan 
                    greenhouse tanaman premium.
                </p>
            </div>
            <div class="solution-grid">
                <div class="solution-card">
                    <div class="solution-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3>Monitoring Real-Time</h3>
                    <p>Pantau suhu, kelembaban, kualitas udara, dan indeks UV secara langsung dari mana saja.</p>
                </div>
                <div class="solution-card">
                    <div class="solution-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Dua Mode Pemantauan</h3>
                    <p>Lihat data melalui display pada alat atau akses dari jarak jauh via website.</p>
                </div>
                <div class="solution-card">
                    <div class="solution-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Grafik Historis</h3>
                    <p>Lihat riwayat data harian/mingguan untuk evaluasi dan analisis budidaya.</p>
                </div>
                <div class="solution-card">
                    <div class="solution-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Peringatan Dini</h3>
                    <p>Notifikasi saat kondisi greenhouse tidak ideal: Normal, Waspada, atau Bahaya.</p>
                </div>
                <div class="solution-card">
                    <div class="solution-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Paket Fleksibel</h3>
                    <p>Pilih paket sesuai kebutuhan: Alat Mandiri, +Pemasangan, atau +Website Monitoring.</p>
                </div>
                <div class="solution-card">
                    <div class="solution-icon">
                        <i class="fas fa-sun"></i>
                    </div>
                    <h3>Energi Ramah Lingkungan</h3>
                    <p>Dilengkapi panel surya untuk operasional yang hemat energi dan ramah lingkungan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== PRODUCT ===== -->
<section id="product" class="product">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Pilih Paket</span>
            <h2 class="section-title">Paket <span>MicroClimeet</span></h2>
            <p class="section-desc">
                Pilih paket yang sesuai dengan kebutuhan monitoring greenhouse Anda.
            </p>
        </div>
        <div class="product-grid">
            <!-- Paket 1: Mandiri -->
            <div class="product-card">
                <div class="product-badge-card">Populer</div>
                <div class="product-image">
                    <div class="product-img-placeholder">
                        <i class="fas fa-microchip"></i>
                        <span>Paket Mandiri</span>
                    </div>
                </div>
                <div class="product-info">
                    <h3>Paket Alat Mandiri</h3>
                    <p>Perangkat sensor lengkap dengan display pada alat. Cocok untuk pemantauan langsung di lokasi.</p>
                    <ul class="product-features">
                        <li><i class="fas fa-check"></i> Sensor Suhu & Kelembaban</li>
                        <li><i class="fas fa-check"></i> Sensor Kualitas Udara</li>
                        <li><i class="fas fa-check"></i> Sensor Indeks UV</li>
                        <li><i class="fas fa-check"></i> Display LCD 20x4</li>
                        <li><i class="fas fa-check"></i> Panel Surya & Baterai</li>
                    </ul>
                    <div class="product-price-wrapper">
                        <span class="product-price">Rp 3.000.000</span>
                        <span class="product-price-note">/unit</span>
                    </div>
                    <a href="#contact" class="btn-order">
                        <span>Pesan Sekarang</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Paket 2: + Pemasangan -->
            <div class="product-card featured">
                <div class="product-badge-card">Best Value</div>
                <div class="product-image">
                    <div class="product-img-placeholder">
                        <i class="fas fa-tools"></i>
                        <span>Paket + Pemasangan</span>
                    </div>
                </div>
                <div class="product-info">
                    <h3>Paket Alat + Pemasangan</h3>
                    <p>Lengkap dengan jasa pemasangan oleh tim ahli. Siap pakai tanpa ribet.</p>
                    <ul class="product-features">
                        <li><i class="fas fa-check"></i> Semua fitur Paket Mandiri</li>
                        <li><i class="fas fa-check"></i> Jasa Pemasangan Profesional</li>
                        <li><i class="fas fa-check"></i> Kalibrasi Sensor</li>
                        <li><i class="fas fa-check"></i> Garansi 3 Bulan</li>
                    </ul>
                    <div class="product-price-wrapper">
                        <span class="product-price">Rp 3.200.000</span>
                        <span class="product-price-note">/unit</span>
                    </div>
                    <a href="#contact" class="btn-order">
                        <span>Pesan Sekarang</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Paket 3: Lengkap -->
            <div class="product-card">
                <div class="product-badge-card">Premium</div>
                <div class="product-image">
                    <div class="product-img-placeholder">
                        <i class="fas fa-globe"></i>
                        <span>Paket Lengkap</span>
                    </div>
                </div>
                <div class="product-info">
                    <h3>Paket Alat + Pemasangan + Website</h3>
                    <p>Solusi lengkap dengan akses dashboard monitoring jarak jauh dan fitur premium.</p>
                    <ul class="product-features">
                        <li><i class="fas fa-check"></i> Semua fitur Paket + Pemasangan</li>
                        <li><i class="fas fa-check"></i> Dashboard Website Real-Time</li>
                        <li><i class="fas fa-check"></i> Riwayat Data Historis</li>
                        <li><i class="fas fa-check"></i> Peringatan Dini</li>
                        <li><i class="fas fa-check"></i> Akses dari Mana Saja</li>
                    </ul>
                    <div class="product-price-wrapper">
                        <span class="product-price">Rp 3.350.000</span>
                        <span class="product-price-note">/unit</span>
                    </div>
                    <a href="#contact" class="btn-order">
                        <span>Pesan Sekarang</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- ===== FEATURES ===== -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Fitur Unggulan</span>
                <h2 class="section-title">Kenapa Pilih <span>MicroClimeet</span>?</h2>
                <p class="section-desc">
                    Kami menghadirkan fitur-fitur canggih yang memudahkan Anda mengelola 
                    greenhouse dengan lebih presisi.
                </p>
            </div>
            <div class="features-grid">
                <div class="features-card">
                    <div class="features-icon">
                        <i class="fas fa-thermometer-half"></i>
                    </div>
                    <h3>Sensor Lengkap</h3>
                    <p>Suhu, kelembaban, kualitas udara (PM2.5), dan indeks UV dalam satu perangkat.</p>
                </div>
                <div class="features-card">
                    <div class="features-icon">
                        <i class="fas fa-display"></i>
                    </div>
                    <h3>Display Langsung</h3>
                    <p>Lihat data di lokasi tanpa perlu membuka aplikasi. Praktis dan cepat.</p>
                </div>
                <div class="features-card">
                    <div class="features-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Pemantauan Jarak Jauh</h3>
                    <p>Akses dashboard dari smartphone atau laptop di mana saja.</p>
                </div>
                <div class="features-card">
                    <div class="features-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Riwayat Data</h3>
                    <p>Simpan dan analisis data harian untuk evaluasi budidaya.</p>
                </div>
                <div class="features-card">
                    <div class="features-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Notifikasi Cerdas</h3>
                    <p>Peringatan saat kondisi tidak ideal. Status: Normal, Waspada, Bahaya.</p>
                </div>
                <div class="features-card">
                    <div class="features-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Hemat Energi</h3>
                    <p>Didukung panel surya dan baterai untuk operasional mandiri.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== HOW IT WORKS ===== -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Cara Kerja</span>
                <h2 class="section-title">Bagaimana <span>MicroClimeet</span> Bekerja?</h2>
                <p class="section-desc">
                    Hanya 4 langkah sederhana untuk memulai pemantauan greenhouse cerdas Anda.
                </p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3>Pasang Perangkat</h3>
                    <p>Pasang MicroClimeet di greenhouse Anda. Mudah dan praktis.</p>
                </div>
                <div class="step-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3>Sensor Aktif</h3>
                    <p>Sensor membaca suhu, kelembaban, kualitas udara, dan indeks UV secara real-time.</p>
                </div>
                <div class="step-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3>Data Terkirim</h3>
                    <p>Data dikirim ke dashboard website melalui koneksi internet.</p>
                </div>
                <div class="step-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Pantau & Ambil Tindakan</h3>
                    <p>Lihat data dari mana saja dan ambil keputusan tepat.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== DASHBOARD ===== -->
    <section id="dashboard" class="dashboard">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Dashboard</span>
                <h2 class="section-title">Lihat <span>Data</span> Anda</h2>
                <p class="section-desc">
                    Dashboard MicroClimeet menyajikan data lingkungan greenhouse secara 
                    visual, informatif, dan mudah dipahami.
                </p>
            </div>
            <div class="dashboard-preview">
                <div class="dashboard-card">
                    <div class="dashboard-header">
                        <div class="dashboard-title">
                            <i class="fas fa-seedling"></i>
                            <span>Greenhouse 1 - Melon Premium</span>
                        </div>
                        <div class="dashboard-status status-normal">
                            <i class="fas fa-check-circle"></i>
                            Normal
                        </div>
                    </div>
                    <div class="dashboard-metrics">
                        <div class="metric">
                            <div class="metric-icon">
                                <i class="fas fa-thermometer-half"></i>
                            </div>
                            <div class="metric-info">
                                <span class="metric-label">Suhu</span>
                                <span class="metric-value">28.5°C</span>
                                <span class="metric-range">Ideal: 25-30°C</span>
                            </div>
                        </div>
                        <div class="metric">
                            <div class="metric-icon">
                                <i class="fas fa-tint"></i>
                            </div>
                            <div class="metric-info">
                                <span class="metric-label">Kelembaban</span>
                                <span class="metric-value">72%</span>
                                <span class="metric-range">Ideal: 60-80%</span>
                            </div>
                        </div>
                        <div class="metric">
                            <div class="metric-icon">
                                <i class="fas fa-wind"></i>
                            </div>
                            <div class="metric-info">
                                <span class="metric-label">Kualitas Udara</span>
                                <span class="metric-value">12 µg/m³</span>
                                <span class="metric-range">Baik: &lt; 35</span>
                            </div>
                        </div>
                        <div class="metric">
                            <div class="metric-icon">
                                <i class="fas fa-sun"></i>
                            </div>
                            <div class="metric-info">
                                <span class="metric-label">Indeks UV</span>
                                <span class="metric-value">3.2</span>
                                <span class="metric-range">Moderat: 3-5</span>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-graph">
                        <div class="graph-placeholder">
                            <i class="fas fa-chart-line"></i>
                            <span>Grafik Historis - 7 Hari Terakhir</span>
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
                        <!-- PERBAIKAN: Foto Tim dengan ukuran pas -->
                        <img src="assets/images/Rizal.png" alt="Muhammad Rizal Alfarisyi" class="team-photo">
                    </div>
                    <div class="team-info">
                        <h3>Muhammad Rizal Alfarisyi</h3>
                        <p class="team-role">Ketua Tim / Penanggung Jawab</p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-image">
                        <img src="assets/images/rosyid.png" alt="Rosyid Hanafitri Utomo" class="team-photo">
                    </div>
                    <div class="team-info">
                        <h3>Rosyid Hanafitri Utomo</h3>
                        <p class="team-role">Divisi Perangkat Keras & Sensor</p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-image">
                        <img src="assets/images/bitul.png" alt="Azzam Tsabitul Jamil" class="team-photo">
                    </div>
                    <div class="team-info">
                        <h3>Azzam Tsabitul Jamil</h3>
                        <p class="team-role">Divisi Sistem Monitoring & Antarmuka</p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-image">
                        <img src="assets/images/angger.png" alt="Anggertha Nurrosyid" class="team-photo">
                    </div>
                    <div class="team-info">
                        <h3>Anggertha Nurrosyid</h3>
                        <p class="team-role">Divisi Pemasaran & Kemitraan</p>
                    </div>
                </div>
                <div class="team-card">
                    <div class="team-image">
                        <img src="assets/images/arum.png" alt="Fathiika Arum Maulida" class="team-photo">
                    </div>
                    <div class="team-info">
                        <h3>Fathiika Arum Maulida</h3>
                        <p class="team-role">Divisi Keuangan & Administrasi</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FAQ ===== -->
    <section id="faq" class="faq">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">FAQ</span>
                <h2 class="section-title">Pertanyaan <span>Umum</span></h2>
                <p class="section-desc">
                    Temukan jawaban dari pertanyaan-pertanyaan yang sering diajukan tentang 
                    MicroClimeet.
                </p>
            </div>
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Apa itu MicroClimeet?</h3>
                        <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>MicroClimeet adalah sistem monitoring mikroklimat greenhouse berbasis IoT yang membantu petani memantau suhu, kelembaban, kualitas udara, dan indeks UV secara real-time untuk tanaman premium.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Apakah alatnya sulit dipasang?</h3>
                        <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Tidak sama sekali! MicroClimeet dirancang mudah dipasang. Kami juga menyediakan layanan pemasangan jika Anda membutuhkan bantuan.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Berapa biaya berlangganan website?</h3>
                        <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Biaya langganan website adalah Rp 150.000 per bulan. Anda mendapatkan akses dashboard, riwayat data, dan peringatan dini.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Apakah harus punya internet?</h3>
                        <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Untuk mengirim data ke website diperlukan koneksi internet. Namun, Anda tetap bisa melihat data melalui display pada alat.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Tanaman apa saja yang cocok?</h3>
                        <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>MicroClimeet sangat cocok untuk tanaman premium seperti melon, stroberi, tomat cherry, paprika, anggur, selada hidroponik, dan semangka tanpa biji.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Bagaimana cara membeli?</h3>
                        <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Anda bisa menghubungi kami melalui form kontak atau media sosial. Tim kami akan membantu proses pemesanan dan konsultasi.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CONTACT ===== -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Hubungi Kami</span>
                <h2 class="section-title">Mari <span>Berkonsultasi</span></h2>
                <p class="section-desc">
                    Tertarik dengan MicroClimeet? Hubungi kami untuk konsultasi, demo, 
                    atau pemesanan produk.
                </p>
            </div>
            <div class="contact-grid">
                <div class="contact-info">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-detail">
                            <h4>Alamat</h4>
                            <p>Universitas Sebelas Maret<br>Surakarta, Jawa Tengah</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-detail">
                            <h4>Email</h4>
                            <p>microclimeet@uns.ac.id</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-detail">
                            <h4>Telepon</h4>
                            <p>+62 812-3456-7890</p>
                        </div>
                    </div>
                    <div class="social-media">
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-tiktok"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="contact-form">
                    <form action="#" method="POST">
                        <div class="form-group">
                            <input type="text" placeholder="Nama Lengkap" required>
                        </div>
                        <div class="form-group">
                            <input type="email" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <input type="text" placeholder="No. Telepon">
                        </div>
                        <div class="form-group">
                            <select>
                                <option value="">Pilih Paket</option>
                                <option value="mandiri">Paket Alat Mandiri</option>
                                <option value="pemasangan">Paket + Pemasangan</option>
                                <option value="website">Paket + Pemasangan + Website</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <textarea placeholder="Pesan / Pertanyaan" rows="4"></textarea>
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i> Kirim Pesan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== MAP ===== -->
     <section class="map-section">
        <div class="container">
            <div class="map-container">
                <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3954.282265581424!2d110.831599514772!3d-7.622549294590707!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7a5e1d6b5c8f8d%3A0x6f7a5e1d6b5c8f8d!2sUniversitas%20Sebelas%20Maret!5e0!3m2!1sid!2sid!4v1700000000000" 
                width="100%" 
                height="300" 
                style="border:0; border-radius: 16px;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-social">
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-tiktok"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <div class="footer-info">
                    <p>&copy; 2026 MicroClimeet. All rights reserved. Dikembangkan oleh Tim WIBAWA UNS</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- ===== SCROLL TO TOP ===== -->
    <button class="scroll-top" id="scrollTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="assets/js/script.js"></script>
</body>
</html>