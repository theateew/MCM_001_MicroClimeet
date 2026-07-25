<!-- Shared greenhouse form fields (used in both Add & Edit modals) -->
<div>
    <label class="form-label">Nama Greenhouse <span style="color:#ef4444;">*</span></label>
    <input type="text" name="name" class="form-input" placeholder="mis. Greenhouse A - Tomat Cherry" required>
</div>
<div>
    <label class="form-label">Alamat / Lokasi</label>
    <input type="text" name="location" class="form-input" placeholder="mis. Desa Colomadu, Karanganyar">
</div>
<div class="form-row">
    <div>
        <label class="form-label">Latitude</label>
        <input type="number" name="latitude" class="form-input" placeholder="-7.558879" step="any">
    </div>
    <div>
        <label class="form-label">Longitude</label>
        <input type="number" name="longitude" class="form-input" placeholder="110.773807" step="any">
    </div>
</div>
<p style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px;display:flex;align-items:center;gap:5px;">
    <i data-lucide="info" style="width:12px;height:12px;"></i>
    Buka <a href="https://maps.google.com" target="_blank" style="color:var(--accent-color);text-decoration:underline;">Google Maps</a>,
    klik kanan lokasi → salin koordinat.
</p>
<div class="form-row" style="margin-top:4px;">
    <div>
        <label class="form-label">Kode Perangkat IoT</label>
        <input type="text" name="device_code" class="form-input" placeholder="MCM-001">
    </div>
    <div>
        <label class="form-label">Firebase Node Key</label>
        <input type="text" name="firebase_key" class="form-input" placeholder="sensors/greenhouse_01">
    </div>
</div>
<div>
    <label class="form-label">Catatan (opsional)</label>
    <textarea name="notes" class="form-input" rows="2" placeholder="Keterangan tambahan tentang greenhouse..."></textarea>
</div>
