# 🌿 MicroClimeet — Smart Greenhouse & IoT Microclimate Monitoring System

![MicroClimeet Platform](assets/images/brand/dashboard_logo.png)

**MicroClimeet** adalah platform berbasis web dan Internet of Things (IoT) yang dirancang khusus untuk memantau serta mengelola ekosistem mikroklimat pada budidaya tanaman premium di *greenhouse* (Melon Premium, Paprika, Tomat Ceri, dan Stroberi) secara *realtime*.

---

## 🚀 Fitur Utama

- **Realtime Sensor Monitoring**: Integrasi Firebase Realtime Database & MySQL untuk membaca parameter Suhu, Kelembapan Udara (BME680), Kualitas Udara PM2.5 (PMS5003), serta Indeks UV (GUVA-S12SD).
- **Multi-Role User Access**:
  - **Petani**: Mengelola greenhouse milik sendiri, memantau riwayat grafik sensor, mengatur komoditas, dan melihat notifikasi ambang batas (alert).
  - **Admin**: Menyetujui pendaftaran petani baru (*Approval Flow*), memantau seluruh jaringan node IoT, mengkonfigurasi batas aman (threshold), serta melihat log aktivitas sistem.
- **Sistem Autentikasi & Keamanan**:
  - Login / Signup dengan Verifikasi Kode OTP 6-Digit via Gmail SMTP.
  - Dukungan Google Sign-In terintegrasi.
  - Sistem enkripsi password `bcrypt` & *session management*.
- **Visualisasi Data & Laporan**:
  - Grafik tren sensor interaktif berbasis Chart.js.
  - Fitur ekspor laporan otomatis berformat PDF.
  - Tampilan *Glassmorphism UI* modern, responsif, dan ramah pengguna.

---

## 📂 Struktur Direktori & Kegunaan File

```text
websiteMicroClimeet/
├── admin/                        # Panel Kontrol Khusus Administrator
│   ├── index.php                 # Overview dashboard statistik global admin
│   ├── users.php                 # Manajemen akun & persetujuan (approval) petani
│   ├── devices.php               # Manajemen perangkat & node IoT terdaftar
│   ├── thresholds.php            # Pengaturan batas aman (min/max) parameter sensor
│   ├── logs.php                  # Log aktivitas sistem & jejak audit
│   └── petani_dashboard.php      # Mode intip/supervisi dashboard milik petani
├── api/                          # Endpoints REST API & Backend Webhook
│   ├── check_approval_status.php # Endpoint cek real-time status persetujuan akun
│   ├── export_pdf_report.php     # Engine generator laporan rekapitulasi PDF
│   ├── export_telemetry.php      # Ekspor data riwayat sensor (CSV/JSON)
│   ├── firebase_webhook.php      # Webhook penyinkron data Firebase ke MySQL
│   ├── get_alerts.php            # Fetch peringatan/notifikasi aktif
│   ├── get_crop_cycle.php        # Fetch siklus tanam & komoditas
│   ├── get_history.php           # Fetch data historis telemetry sensor
│   ├── get_thresholds.php        # Fetch batas ambang kondisi tanaman
│   ├── google_auth.php           # Handler OAuth Google Sign-In
│   ├── push_subscribe.php        # Web Push notification subscription
│   ├── save_alert.php            # Simpan rekaman peringatan baru
│   └── save_telemetry.php        # Simpan payload telemetry sensor dari IoT
├── assets/                       # File Asset Statis Website
│   ├── css/                      # Stylesheet CSS (style.css, dashboard.css)
│   ├── js/                       # Script JavaScript Client-side & DB Simulator
│   └── images/                   # Asset Gambar (Brand Logo, Sensors, Backgrounds)
├── config/                       # Konfigurasi Server & Database
│   ├── database.php              # Koneksi MySQL PDO & Timezone Asia/Jakarta
│   ├── firebase_helper.php       # Helper SDK REST API Firebase Identity & RTDB
│   ├── mail_helper.php           # Helper pengiriman email OTP via Gmail SMTP
│   └── push_config.php          # Konfigurasi VAPID Push & WhatsApp Fonnte API
├── firmware/                     # Source Code Hardware Mikrokontroler
│   └── microclimeet_esp32/
│       └── microclimeet_esp32.ino # Script C++ Arduino untuk ESP32 + BME680 + PMS5003 + OLED
├── includes/                     # Komponen Modular PHP UI/UX
│   ├── auth_check.php            # Guard pembatas hak akses session (Role Middleware)
│   ├── header.php                # Top Navigation & Meta Tag Header
│   ├── sidebar.php               # Menu Samping Dashboard Petani/Admin
│   ├── footer.php                # Footer & Script Lucide Icon Loader
│   └── page_loader.php           # Komponen animasi loading indikator
├── petani/                       # Panel Kontrol Khusus Petani Terdaftar
│   ├── index.php                 # Dashboard utama monitoring live & status greenhouse
│   ├── greenhouse.php            # Detail & konfigurasi komoditas greenhouse
│   ├── history.php               # Rekapitulasi grafik historis sensor & ekspor data
│   ├── alerts.php                # Pusat notifikasi & histori peringatan bahaya
│   └── settings.php              # Pengaturan profil petani & password
├── complete_google_signup.php    # Form pelengkapan profil setelah daftar via Google
├── forgot_password.php           # Halaman pengajuan lupa password via OTP
├── index.php                     # Landing Page Publik MicroClimeet (Hero & Mini Dashboard)
├── login.php                     # Halaman Form Masuk Akun
├── logout.php                    # Handler pembatalan session / keluar akun
├── pending_approval.php          # Halaman tunggu konfirmasi admin bagi petani baru
├── reset_password.php            # Form ubah password baru setelah OTP terverifikasi
├── schema.sql                    # Skema dasar tabel MySQL
├── schema_v2.sql                 # Skema pembaruan & penambahan fitur tabel MySQL
├── signup.php                    # Halaman Form Pendaftaran Akun Petani Baru
├── verify_email_otp.php          # Form verifikasi 6-digit kode OTP email
└── .gitignore                    # Daftar pengecualian upload file sensitif/besar Git
```

---

## 🛠️ Teknologi & Library

- **Backend Web**: PHP 8.x (Native PDO, Session Auth, Sockets SMTP)
- **Database**: MySQL / MariaDB & Firebase Realtime Database (RTDB)
- **Frontend**: HTML5, Vanilla CSS3 (Glassmorphism), JavaScript (ES6+)
- **Library Client**: Lucide Icons, Chart.js, HTML5 Web Push
- **Hardware / Firmware**: ESP32 Dev Module, Sensor BME680 (I2C), Sensor PMS5003 (Serial2 UART), Sensor GUVA-S12SD (ADC), Layar OLED SSD1306 0.96"

---

## ⚙️ Cara Instalasi & Penggunaan di Localhost

### 1. Prasyarat
- Environment Web Server Local: **Laragon** (Direkomendasikan) atau **XAMPP** dengan **PHP >= 8.0** & **MySQL**.
- Git Installed.

### 2. Clone Repositori
Buka terminal/command prompt, jalankan:
```bash
cd c:/laragon/www
git clone https://github.com/theateew/MCM_001_MicroClimeet.git websiteMicroClimeet
```

### 3. Setup Database MySQL
1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) atau HeidiSQL.
2. Buat database baru bernama `microclimeet`.
3. Import file `schema.sql` terlebih dahulu, lalu import `schema_v2.sql`.

### 4. Konfigurasi Database & Server
1. Buka file `config/database.php`, sesuaikan kredensial MySQL lokal kamu:
   ```php
   $host = 'localhost';
   $dbname = 'microclimeet';
   $username = 'root';
   $password = '';
   ```
2. Pastikan extension `pdo_mysql`, `curl`, dan `openssl` aktif di `php.ini`.

### 5. Menjalankan Website
Jalankan development server via Terminal di dalam folder project:
```bash
php -S localhost:8000
```
Buka browser dan akses: `http://localhost:8000`

---

## 📡 Setup Hardware ESP32 (Opsional)

1. Buka file `firmware/microclimeet_esp32/microclimeet_esp32.ino` di **Arduino IDE**.
2. Sesuaikan konfigurasi Wi-Fi & Firebase URL:
   ```cpp
   #define WIFI_SSID "NAMA_WIFI_ANDA"
   #define WIFI_PASSWORD "PASSWORD_WIFI"
   #define DATABASE_URL "https://microclimeet-7eff0-default-rtdb.asia-southeast1.firebasedatabase.app"
   ```
3. Upload kode ke papan mikrokontroler **ESP32 Dev Module**.

---

## 👨‍💻 Kontribusi & Pengembang

Dikembangkan oleh Tim **MicroClimeet** untuk solusi pertanian modern berbasis *Smart Greenhouse & Precision Agriculture*.
