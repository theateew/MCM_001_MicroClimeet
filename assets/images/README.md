# Asset Images Management Guide (MicroClimeet)

Dokumen ini menjelaskan konvensi dan struktur pengorganisasian aset gambar untuk proyek **MicroClimeet**.

---

## 🗂️ Struktur Folder `assets/images/`

Semua file gambar dikelompokkan ke dalam subfolder spesifik berdasarkan fungsi dan konteks penggunaannya:

```text
assets/images/
├── brand/           # Aset identitas merek & logo
│   ├── logo.png             # Logo utama lengkap (full brand logo)
│   └── mini_logo.png        # Logo kecil / icon untuk kondisi sidebar collapsed
├── products/        # Gambar produk, hardware IoT, mockup perangkat
│   └── microclimeet.png     # Mockup/foto perangkat IoT MicroClimeet
├── backgrounds/     # Gambar latar belakang (hero section, banner, kartu)
│   └── greenhouse.png       # Background hero section landing page
├── screenshots/     # Tangkapan layar dokumentasi / pratinjau sistem
│   ├── Screenshot 2026-07-24 101902.png
│   └── Screenshot 2026-07-24 101933.png
├── avatars/         # (Opsional) Foto profil pengguna / admin / tim
└── icons/           # (Opsional) Ikon kustom berbentuk PNG/SVG jika tidak memakai font icon
```

---

## 📌 Panduan Pemanggilan di Kode

1. **Dalam File PHP (Root/Subfolder via `$base`):**
   ```php
   <!-- Contoh logo di sidebar (otomatis menyesuaikan path root) -->
   <img src="<?php echo $base; ?>assets/images/brand/logo.png" alt="MicroClimeet Logo">
   ```

2. **Dalam File CSS (`assets/css/*.css`):**
   ```css
   /* Relative path dari assets/css/ ke assets/images/ */
   background: url('../images/backgrounds/greenhouse.png') center/cover no-repeat;
   ```

3. **Dalam Halaman HTML / Root PHP (`index.php`, `login.php`):**
   ```html
   <img src="assets/images/brand/logo.png" alt="MicroClimeet Logo">
   <img src="assets/images/products/microclimeet.png" alt="IoT Device">
   ```

---

## 💡 Best Practices
- **Format File**: Gunakan WebP atau SVG untuk ikon/ilustrasi jika memungkinkan (efisiensi ukuran), PNG untuk gambar dengan transparansi, dan JPG/WebP untuk foto background.
- **Penamaan File**: Gunakan format `kebab-case` atau `snake_case` huruf kecil (contoh: `hero-bg-greenhouse.jpg`, `mini_logo.png`).
- **Kompresi**: Pastikan gambar berukuran besar sudah dikompresi agar loading page tetap kencang.
