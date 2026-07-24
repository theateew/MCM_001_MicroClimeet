<?php
define('AUTH_BASE', '../');
require_once '../config/database.php';
require_once '../includes/auth_check.php';
check_auth('petani');

$page_title = "Dashboard Petani";
$custom_css = ['dashboard.css'];
$base_url = '../';

// Retrieve global thresholds from database to initialize warning logic
$thresholds = [
    'temperature' => ['min' => 20, 'max' => 30],
    'humidity' => ['min' => 60, 'max' => 85],
    'air_quality' => ['min' => 0, 'max' => 50],
    'uv' => ['min' => 0, 'max' => 5]
];

try {
    $stmt = $pdo->query("SELECT * FROM global_thresholds");
    while ($row = $stmt->fetch()) {
        $thresholds[$row['sensor_type']] = [
            'min' => (float)$row['min_val'],
            'max' => (float)$row['max_val']
        ];
    }
} catch (PDOException $e) {
    // Fallback to defaults if query fails
}

include '../includes/header.php';
?>

<!-- Load Firebase SDKs and ApexCharts -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <?php 
    $active_menu = 'dashboard';
    include '../includes/sidebar.php'; 
    ?>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="welcome-msg">
                <h2>Halo, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                <p>Pantau dan kelola mikroklimat greenhouse tanaman premium Anda.</p>
            </div>
            
            <div class="header-controls">
                <!-- Status Pill -->
                <span id="status-pill" class="status-pill simulator">
                    <span class="indicator-dot"></span>
                    <span id="status-text">Mengevaluasi Koneksi...</span>
                </span>
                
                <!-- Toggle Simulator Button -->
                <button id="toggle-sim-btn" class="btn btn-secondary btn-text" style="padding: 8px 16px; border-radius: 12px; font-size: 0.85rem;">
                    <i data-lucide="play" style="width: 16px; height: 16px;"></i> Aktifkan Simulator
                </button>
            </div>
        </div>
        
        <!-- Dashboard Grid Layout -->
        <div class="dashboard-grid">
            <!-- Left Column: Sensors & Charts -->
            <div class="left-column">
                
                <!-- Telemetry Cards Grid -->
                <div class="telemetry-grid">
                    
                    <!-- 1. Suhu Udara Widget -->
                    <div class="glass-card widget-card" id="temp-card">
                        <div class="widget-header">
                            <div class="widget-title-box">
                                <div class="widget-icon">
                                    <i data-lucide="thermometer"></i>
                                </div>
                                <span class="widget-label">Suhu Udara</span>
                            </div>
                            <span id="temp-status" class="badge badge-success">Mencari...</span>
                        </div>
                        <div class="widget-value-box">
                            <span id="temp-val" class="widget-value">--.-</span>
                            <span class="widget-unit">°C</span>
                        </div>
                        <div class="widget-footer">
                            <span style="font-size: 0.75rem; color: var(--text-secondary);">
                                Kalibrasi: <strong id="temp-offset-label">0.0°C</strong>
                            </span>
                            <button onclick="openCalibration('temperature', 'Suhu Udara', '°C')" class="btn btn-secondary widget-btn">
                                <i data-lucide="sliders" style="width: 12px; height: 12px;"></i> Kalibrasi
                            </button>
                        </div>
                    </div>

                    <!-- 2. Kelembapan Widget -->
                    <div class="glass-card widget-card" id="hum-card">
                        <div class="widget-header">
                            <div class="widget-title-box">
                                <div class="widget-icon">
                                    <i data-lucide="droplet"></i>
                                </div>
                                <span class="widget-label">Kelembapan</span>
                            </div>
                            <span id="hum-status" class="badge badge-success">Mencari...</span>
                        </div>
                        <div class="widget-value-box">
                            <span id="hum-val" class="widget-value">--.-</span>
                            <span class="widget-unit">% RH</span>
                        </div>
                        <div class="widget-footer">
                            <span style="font-size: 0.75rem; color: var(--text-secondary);">
                                Kalibrasi: <strong id="hum-offset-label">0.0%</strong>
                            </span>
                            <button onclick="openCalibration('humidity', 'Kelembapan', '% RH')" class="btn btn-secondary widget-btn">
                                <i data-lucide="sliders" style="width: 12px; height: 12px;"></i> Kalibrasi
                            </button>
                        </div>
                    </div>

                    <!-- 3. Kualitas Udara Widget -->
                    <div class="glass-card widget-card" id="air-card">
                        <div class="widget-header">
                            <div class="widget-title-box">
                                <div class="widget-icon">
                                    <i data-lucide="wind"></i>
                                </div>
                                <span class="widget-label">Kualitas Udara</span>
                            </div>
                            <span id="air-status" class="badge badge-success">Mencari...</span>
                        </div>
                        <div class="widget-value-box">
                            <span id="air-val" class="widget-value">---</span>
                            <span class="widget-unit">PM2.5</span>
                        </div>
                        <div class="widget-footer">
                            <span style="font-size: 0.75rem; color: var(--text-secondary);">
                                Kalibrasi: <strong id="air-offset-label">0</strong>
                            </span>
                            <button onclick="openCalibration('air_quality', 'Kualitas Udara', 'PM2.5')" class="btn btn-secondary widget-btn">
                                <i data-lucide="sliders" style="width: 12px; height: 12px;"></i> Kalibrasi
                            </button>
                        </div>
                    </div>

                    <!-- 4. Indeks UV Widget -->
                    <div class="glass-card widget-card" id="uv-card">
                        <div class="widget-header">
                            <div class="widget-title-box">
                                <div class="widget-icon">
                                    <i data-lucide="sun-medium"></i>
                                </div>
                                <span class="widget-label">Indeks UV</span>
                            </div>
                            <span id="uv-status" class="badge badge-success">Mencari...</span>
                        </div>
                        <div class="widget-value-box">
                            <span id="uv-val" class="widget-value">--.-</span>
                            <span class="widget-unit">UV Index</span>
                        </div>
                        <div class="widget-footer">
                            <span style="font-size: 0.75rem; color: var(--text-secondary);">
                                Kalibrasi: <strong id="uv-offset-label">0.0</strong>
                            </span>
                            <button onclick="openCalibration('uv', 'Indeks UV', 'Index')" class="btn btn-secondary widget-btn">
                                <i data-lucide="sliders" style="width: 12px; height: 12px;"></i> Kalibrasi
                            </button>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Chart Card -->
                <div class="glass-card chart-card">
                    <div class="chart-header">
                        <h3>Grafik Tren Parameter Greenhouse</h3>
                        <div class="chart-filters">
                            <button class="filter-btn active" onclick="changeChartSpan(6)">6 Jam</button>
                            <button class="filter-btn" onclick="changeChartSpan(12)">12 Jam</button>
                            <button class="filter-btn" onclick="changeChartSpan(24)">24 Jam</button>
                        </div>
                    </div>
                    <div id="telemetry-chart"></div>
                </div>

            </div>

            <!-- Right Column: Crop Info & Recommendations -->
            <div class="right-column">
                <!-- Greenhouse Status Overview -->
                <div class="glass-card">
                    <h3 style="margin-bottom: 16px;"><i data-lucide="sprout" style="color: var(--accent-color); vertical-align: middle; margin-right: 8px;"></i> Profil Komoditas</h3>
                    <div style="background: rgba(114, 156, 81, 0.05); padding: 16px; border-radius: 12px; border: 1px solid rgba(114, 156, 81, 0.15); margin-bottom: 20px;">
                        <h4 style="color: var(--accent-color); margin-bottom: 4px;">Budidaya Melon Premium</h4>
                        <p style="font-size: 0.88rem; color: var(--text-secondary);">Jenis: Melon Jepang (Arisla / Premium Sweet)</p>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.88rem;">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 8px;">
                            <span style="color: var(--text-secondary);">Target Suhu:</span>
                            <strong>20.0°C - 30.0°C</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 8px;">
                            <span style="color: var(--text-secondary);">Target Kelembapan:</span>
                            <strong>60.0% - 85.0%</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 8px;">
                            <span style="color: var(--text-secondary);">Radiasi Maksimal:</span>
                            <strong>5.0 UV Index</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Tips Card -->
                <div class="glass-card">
                    <h3 style="margin-bottom: 16px;"><i data-lucide="lightbulb" style="color: var(--accent-orange); vertical-align: middle; margin-right: 8px;"></i> Panduan Kondisi</h3>
                    <div style="font-size: 0.88rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 16px;">
                        <div>
                            <strong style="color: var(--text-primary); display:block; margin-bottom: 4px;">Suhu Terlalu Panas (>30°C)</strong>
                            Lakukan aktivasi kipas ventilasi atau jaring peneduh / shading net untuk mendinginkan mikroklimat.
                        </div>
                        <div>
                            <strong style="color: var(--text-primary); display:block; margin-bottom: 4px;">Kelembapan Terlalu Lembab (>85%)</strong>
                            Meningkatkan risiko pembusukan akar dan jamur daun. Aktifkan blower aerasi atau buka blower atas.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Calibration Overlay (Ref: Modal Calibration Specification) -->
<div class="modal-overlay" id="calibration-modal">
    <div class="glass-card modal-content">
        <h3 id="modal-title" style="margin-bottom: 12px;">Kalibrasi Sensor</h3>
        <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 20px;">
            Atur nilai offset untuk menyesuaikan pembacaan mentah sensor jika ada perbedaan dengan alat ukur kalibrasi eksternal.
        </p>
        
        <form id="calibration-form" onsubmit="saveCalibration(event)">
            <input type="hidden" id="cal-sensor-type">
            <div class="form-group">
                <label for="cal-offset-input">Nilai Offset (<span id="modal-unit"></span>)</label>
                <input type="number" step="0.1" id="cal-offset-input" class="form-control" placeholder="0.0" required>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" onclick="closeCalibration()" class="btn btn-secondary" style="padding: 10px 20px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php 
$custom_js = ['firebase-config.js', 'db-simulator.js'];
include '../includes/footer.php'; 
?>

<!-- Dashboard Logic Integration -->
<script>
    // Load database thresholds from PHP
    const thresholds = <?php echo json_encode($thresholds); ?>;
    
    // Calibration offsets storage
    const offsets = {
        temperature: parseFloat(localStorage.getItem('cal_offset_temperature') || 0.0),
        humidity: parseFloat(localStorage.getItem('cal_offset_humidity') || 0.0),
        air_quality: parseFloat(localStorage.getItem('cal_offset_air_quality') || 0.0),
        uv: parseFloat(localStorage.getItem('cal_offset_uv') || 0.0)
    };
    
    // Update calibration labels in UI
    function updateOffsetLabels() {
        document.getElementById('temp-offset-label').innerText = (offsets.temperature >= 0 ? '+' : '') + offsets.temperature.toFixed(1) + '°C';
        document.getElementById('hum-offset-label').innerText = (offsets.humidity >= 0 ? '+' : '') + offsets.humidity.toFixed(1) + '%';
        document.getElementById('air-offset-label').innerText = (offsets.air_quality >= 0 ? '+' : '') + offsets.air_quality.toFixed(1);
        document.getElementById('uv-offset-label').innerText = (offsets.uv >= 0 ? '+' : '') + offsets.uv.toFixed(1);
    }
    updateOffsetLabels();

    // Chart initialization
    const chartOptions = {
        series: [
            { name: 'Suhu (°C)', data: [] },
            { name: 'Kelembapan (%)', data: [] },
            { name: 'Kualitas Udara (PM)', data: [] },
            { name: 'Indeks UV', data: [] }
        ],
        chart: {
            height: 350,
            type: 'area',
            background: 'transparent',
            toolbar: { show: false }
        },
        colors: ['#eb5011', '#c7e0ed', '#729c51', '#e0c243'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.25,
                opacityTo: 0.05,
                stops: [0, 90, 100]
            }
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        xaxis: {
            type: 'datetime',
            labels: {
                style: { colors: '#4a554f' }
            }
        },
        yaxis: {
            labels: {
                style: { colors: '#4a554f' }
            }
        },
        grid: {
            borderColor: 'rgba(0, 0, 0, 0.05)'
        },
        tooltip: { x: { format: 'dd/MM/yyyy HH:mm' } }
    };
    const chart = new ApexCharts(document.querySelector("#telemetry-chart"), chartOptions);
    chart.render();

    // Feed telemetry to UI
    let chartHistory = [];
    
    function updateDashboardUI(rawData, statuses) {
        // Apply calibration offsets
        const displayData = {
            temperature: parseFloat((rawData.temperature + offsets.temperature).toFixed(1)),
            humidity: parseFloat((rawData.humidity + offsets.humidity).toFixed(1)),
            air_quality: parseFloat((rawData.air_quality + offsets.air_quality).toFixed(1)),
            uv: parseFloat((rawData.uv + offsets.uv).toFixed(1))
        };
        
        // Re-evaluate statuses post-calibration
        const calStatuses = {};
        for (const [key, value] of Object.entries(displayData)) {
            calStatuses[key] = simulatorInstance.evaluateStatus(key, value);
        }

        // Update Text values
        document.getElementById('temp-val').innerText = displayData.temperature.toFixed(1);
        document.getElementById('hum-val').innerText = displayData.humidity.toFixed(1);
        document.getElementById('air-val').innerText = displayData.air_quality.toFixed(0);
        document.getElementById('uv-val').innerText = displayData.uv.toFixed(1);

        // Update status badges
        updateBadge('temp-status', calStatuses.temperature);
        updateBadge('hum-status', calStatuses.humidity);
        updateBadge('air-status', calStatuses.air_quality);
        updateBadge('uv-status', calStatuses.uv);

        // Add to chart history
        const timestamp = new Date().getTime();
        chartHistory.push({
            time: timestamp,
            temp: displayData.temperature,
            hum: displayData.humidity,
            air: displayData.air_quality,
            uv: displayData.uv
        });

        // Limit chart history to 20 points
        if (chartHistory.length > 20) {
            chartHistory.shift();
        }

        // Update ApexChart series
        chart.updateSeries([
            { name: 'Suhu (°C)', data: chartHistory.map(d => [d.time, d.temp]) },
            { name: 'Kelembapan (%)', data: chartHistory.map(d => [d.time, d.hum]) },
            { name: 'Kualitas Udara (PM)', data: chartHistory.map(d => [d.time, d.air]) },
            { name: 'Indeks UV', data: chartHistory.map(d => [d.time, d.uv]) }
        ]);
    }

    function updateBadge(elementId, status) {
        const badge = document.getElementById(elementId);
        badge.innerText = status.text;
        badge.className = 'badge ' + status.class;
    }

    // Modal Calibration handlers
    function openCalibration(sensorType, sensorName, unit) {
        document.getElementById('cal-sensor-type').value = sensorType;
        document.getElementById('modal-title').innerText = 'Kalibrasi ' + sensorName;
        document.getElementById('modal-unit').innerText = unit;
        document.getElementById('cal-offset-input').value = offsets[sensorType];
        document.getElementById('calibration-modal').style.display = 'flex';
    }

    function closeCalibration() {
        document.getElementById('calibration-modal').style.display = 'none';
    }

    function saveCalibration(event) {
        event.preventDefault();
        const sensorType = document.getElementById('cal-sensor-type').value;
        const val = parseFloat(document.getElementById('cal-offset-input').value) || 0.0;
        
        // Save locally
        offsets[sensorType] = val;
        localStorage.setItem('cal_offset_' + sensorType, val);
        
        updateOffsetLabels();
        closeCalibration();
        
        // Trigger manual tick to update immediately if simulator is active
        if (!firebaseActive) {
            simulatorInstance.tick();
        }
    }

    // Active state manager (Firebase vs Simulator)
    const simulatorInstance = new TelemetrySimulator(thresholds, updateDashboardUI);
    let simulatorActive = false;
    
    function setStatus(mode) {
        const pill = document.getElementById('status-pill');
        const text = document.getElementById('status-text');
        
        if (mode === 'live') {
            pill.className = 'status-pill live';
            text.innerText = 'Koneksi Live Firebase';
            document.getElementById('toggle-sim-btn').style.display = 'none';
        } else {
            pill.className = 'status-pill simulator';
            text.innerText = 'Simulator Aktif';
            document.getElementById('toggle-sim-btn').innerHTML = '<i data-lucide="square" style="width: 16px; height: 16px;"></i> Matikan Simulator';
            document.getElementById('toggle-sim-btn').className = 'btn btn-orange';
            lucide.createIcons();
        }
    }

    // Check configuration and start monitoring
    window.onload = function() {
        if (firebaseActive && telemetryRef) {
            setStatus('live');
            // Listen for telemetry node updates in real-time
            telemetryRef.on('value', (snapshot) => {
                const data = snapshot.val();
                if (data) {
                    updateDashboardUI(data);
                }
            });
        } else {
            // Default to running simulator
            setStatus('sim');
            simulatorInstance.start();
            simulatorActive = true;
        }
    };

    // Toggle Simulator button click handler
    document.getElementById('toggle-sim-btn').addEventListener('click', function() {
        if (simulatorActive) {
            simulatorInstance.stop();
            simulatorActive = false;
            this.innerHTML = '<i data-lucide="play" style="width: 16px; height: 16px;"></i> Aktifkan Simulator';
            this.className = 'btn btn-secondary btn-text';
            document.getElementById('status-text').innerText = 'Simulator Berhenti';
            document.getElementById('status-pill').className = 'status-pill';
            
            // Reset values
            document.getElementById('temp-val').innerText = '--.-';
            document.getElementById('hum-val').innerText = '--.-';
            document.getElementById('air-val').innerText = '---';
            document.getElementById('uv-val').innerText = '--.-';
        } else {
            setStatus('sim');
            simulatorInstance.start();
            simulatorActive = true;
        }
        lucide.createIcons();
    });
</script>
