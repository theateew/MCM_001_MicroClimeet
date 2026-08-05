<!-- Shared greenhouse form fields (used in both Add & Edit modals) -->
<div>
    <label class="form-label">Nama Greenhouse <span style="color:#ef4444;">*</span></label>
    <input type="text" name="name" class="form-input" placeholder="mis. Greenhouse A - Tomat Cherry" required>
</div>
<div>
    <label class="form-label">Komoditas Budidaya</label>
    <select name="commodity_select" class="form-input commodity-select-field" onchange="handleFormCommodityChange(this)">
        <option value="Melon Premium">Melon Premium</option>
        <option value="Tomat Ceri">Tomat Ceri</option>
        <option value="Paprika">Paprika</option>
        <option value="Stroberi">Stroberi</option>
        <option value="other">Lainnya (Isi Manual)</option>
    </select>
</div>
<div class="custom-commodity-wrapper" style="display:none;margin-top:4px;">
    <label class="form-label" style="font-size:0.78rem;">Nama Komoditas Lainnya</label>
    <input type="text" name="commodity_custom" class="form-input commodity-custom-input" placeholder="mis. Buah Naga / Cabai Hydroponic">
</div>

<!-- ── Panel Lokasi & Koordinat Presisi (UI/UX Pro Max) ── -->
<div class="location-precision-panel" style="background:#f8faf8;border:1.5px solid rgba(13,93,49,0.2);border-radius:16px;padding:18px;margin:16px 0;box-shadow:0 4px 16px rgba(0,0,0,0.02);">
    
    <!-- Header Title -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <label class="form-label" style="margin:0;font-weight:800;color:#0D5D31;font-size:0.92rem;display:flex;align-items:center;gap:8px;">
            <div style="width:28px;height:28px;border-radius:8px;background:rgba(13,93,49,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-lucide="map-pin" style="width:16px;height:16px;color:#0D5D31;"></i>
            </div>
            <span>Alamat & Lokasi Presisi Greenhouse</span>
        </label>
        <span class="loc-mode-badge" style="font-size:0.72rem;font-weight:700;padding:3px 9px;border-radius:20px;background:rgba(13,93,49,0.08);color:#0D5D31;">
            Mode Presisi Aktif
        </span>
    </div>

    <!-- Segmented Control Mode Tabs -->
    <div class="location-mode-tabs" style="display:flex;background:#e2e8f0;padding:4px;border-radius:12px;margin-bottom:14px;gap:4px;">
        <button type="button" onclick="selectLocationMode(this, 'gps')" class="loc-mode-btn" style="flex:1;padding:8px 10px;border-radius:9px;border:none;font-size:0.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:5px;transition:all 0.2s ease;background:transparent;color:#475569;">
            <i data-lucide="navigation" style="width:14px;height:14px;"></i> GPS Saya
        </button>
        <button type="button" onclick="selectLocationMode(this, 'map')" class="loc-mode-btn" style="flex:1;padding:8px 10px;border-radius:9px;border:none;font-size:0.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:5px;transition:all 0.2s ease;background:#0D5D31;color:#fff;box-shadow:0 2px 8px rgba(13,93,49,0.25);">
            <i data-lucide="map" style="width:14px;height:14px;"></i> Pilih di Peta
        </button>
        <button type="button" onclick="selectLocationMode(this, 'paste')" class="loc-mode-btn" style="flex:1;padding:8px 10px;border-radius:9px;border:none;font-size:0.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:5px;transition:all 0.2s ease;background:transparent;color:#475569;">
            <i data-lucide="clipboard" style="width:14px;height:14px;"></i> Tempel Link
        </button>
    </div>

    <!-- Form Input Alamat (Format: Desa, Kecamatan, Kab/Kota) -->
    <div style="margin-bottom:12px;">
        <label style="display:block;font-size:0.78rem;font-weight:700;color:var(--text-secondary);margin-bottom:4px;">
            Alamat Wilayah (Desa, Kecamatan, Kab/Kota)
        </label>
        <input type="text" name="location" class="form-input" placeholder="Contoh: Desa Makamhaji, Kec. Kartasura, Kab. Sukoharjo" style="background:#fff;font-weight:600;">
    </div>

    <!-- Form Input Latitude & Longitude -->
    <div class="form-row" style="margin-bottom:12px;">
        <div>
            <label style="display:block;font-size:0.78rem;font-weight:700;color:var(--text-secondary);margin-bottom:4px;">Latitude</label>
            <input type="number" name="latitude" class="form-input" placeholder="-7.558879" step="any" style="background:#fff;font-family:monospace;font-weight:700;color:#0D5D31;">
        </div>
        <div>
            <label style="display:block;font-size:0.78rem;font-weight:700;color:var(--text-secondary);margin-bottom:4px;">Longitude</label>
            <input type="number" name="longitude" class="form-input" placeholder="110.773807" step="any" style="background:#fff;font-family:monospace;font-weight:700;color:#0D5D31;">
        </div>
    </div>

    <!-- Clean Help Text Note Box -->
    <div style="background:rgba(255,255,255,0.85);border:1px solid rgba(0,0,0,0.06);border-radius:10px;padding:10px 12px;display:flex;align-items:center;gap:8px;">
        <i data-lucide="info" style="width:15px;height:15px;color:#0D5D31;flex-shrink:0;"></i>
        <span class="loc-help-text" style="font-size:0.76rem;color:var(--text-secondary);line-height:1.45;">
            Gunakan pilihan mode di atas untuk mengambil koordinat presisi dari GPS, Peta, atau Tempel Link.
        </span>
    </div>
</div>
<div class="form-row" style="margin-top:8px;">
    <div>
        <label class="form-label" style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
            <span>Kode Perangkat IoT</span>
            <span style="font-size:0.68rem;font-weight:700;color:#475569;background:#e2e8f0;padding:2px 7px;border-radius:6px;white-space:nowrap;letter-spacing:0.2px;">🔒 Dikelola Admin</span>
        </label>
        <input type="text" name="device_code" class="form-input" placeholder="Belum Dialokasikan" readonly style="background:#f8fafc;color:#64748b;cursor:not-allowed;font-family:monospace;font-weight:700;border-color:#cbd5e1;">
    </div>
    <div>
        <label class="form-label" style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
            <span>Firebase Node Key</span>
            <span style="font-size:0.68rem;font-weight:700;color:#475569;background:#e2e8f0;padding:2px 7px;border-radius:6px;white-space:nowrap;letter-spacing:0.2px;">🔒 Dikelola Sistem</span>
        </label>
        <input type="text" name="firebase_key" class="form-input" placeholder="Dikelola Sistem/Admin" readonly style="background:#f8fafc;color:#64748b;cursor:not-allowed;font-family:monospace;border-color:#cbd5e1;">
    </div>
</div>
<div>
    <label class="form-label">Foto Greenhouse</label>
    <div style="display:flex;gap:12px;align-items:center;">
        <div class="photo-preview-box" style="width:80px;height:80px;border-radius:12px;border:1.5px dashed rgba(0,0,0,0.18);background:rgba(0,0,0,0.02);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;position:relative;">
            <i data-lucide="image" class="photo-placeholder-icon" style="width:28px;height:28px;color:var(--text-secondary);"></i>
            <img class="photo-preview-img" src="" alt="Preview" style="display:none;width:100%;height:100%;object-fit:cover;">
        </div>
        <div style="flex-grow:1;">
            <input type="file" name="photo" accept="image/*" class="form-input photo-input-field" style="padding:6px 10px;font-size:0.85rem;" onchange="handlePhotoPreview(this)">
            <p style="font-size:0.75rem;color:var(--text-secondary);margin-top:4px;">Format: JPG, PNG, WEBP, GIF (Maks. 5MB)</p>
        </div>
    </div>
</div>
<div>
    <label class="form-label">Catatan (opsional)</label>
    <textarea name="notes" class="form-input" rows="2" placeholder="Keterangan tambahan tentang greenhouse..."></textarea>
</div>

