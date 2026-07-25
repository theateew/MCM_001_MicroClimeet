-- ============================================================
-- MicroClimeet Schema v2 — Migration
-- Run this AFTER schema.sql (tables users, activity_logs, global_thresholds already exist)
-- ============================================================

-- 4. Tabel Greenhouse / Perangkat IoT per Petani
CREATE TABLE IF NOT EXISTS greenhouses (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    name         VARCHAR(100) NOT NULL,
    location     VARCHAR(255) NULL,           -- Alamat teks
    latitude     DECIMAL(10,8) NULL,          -- Koordinat GPS
    longitude    DECIMAL(11,8) NULL,
    firebase_key VARCHAR(100) NULL,           -- Node key di Firebase Realtime DB
    device_code  VARCHAR(50) NULL,            -- Kode unik perangkat IoT
    status       ENUM('active','inactive','warning') DEFAULT 'active',
    photo        VARCHAR(255) NULL,           -- Path file foto greenhouse
    notes        TEXT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Tabel Riwayat Telemetri Sensor
CREATE TABLE IF NOT EXISTS telemetry_logs (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    greenhouse_id INT NOT NULL,
    temperature   DECIMAL(5,2) NULL,
    humidity      DECIMAL(5,2) NULL,
    air_quality   DECIMAL(6,2) NULL,
    uv            DECIMAL(4,2) NULL,
    recorded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (greenhouse_id) REFERENCES greenhouses(id) ON DELETE CASCADE,
    INDEX idx_gh_time (greenhouse_id, recorded_at)
);

-- 6. Tabel Alert / Peringatan Sensor
CREATE TABLE IF NOT EXISTS alerts (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    greenhouse_id  INT NOT NULL,
    user_id        INT NOT NULL,
    sensor_type    ENUM('temperature','humidity','air_quality','uv') NOT NULL,
    sensor_value   DECIMAL(6,2) NOT NULL,
    threshold_type ENUM('below_min','above_max') NOT NULL,
    message        VARCHAR(255) NOT NULL,
    is_read        TINYINT(1) DEFAULT 0,
    notif_sent     TINYINT(1) DEFAULT 0,
    triggered_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (greenhouse_id) REFERENCES greenhouses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
);

-- 7. Tabel Push Notification Subscriptions (Web Push VAPID)
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    endpoint   TEXT NOT NULL,
    p256dh     TEXT NOT NULL,
    auth_key   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
