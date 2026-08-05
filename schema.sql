CREATE DATABASE IF NOT EXISTS microclimeet;
USE microclimeet;

-- Disable foreign key checks to drop old tables safely
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS global_thresholds;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS greenhouses;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabel Users (Admin dan Petani)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    role ENUM('admin', 'petani') DEFAULT 'petani',
    status ENUM('active', 'inactive') DEFAULT 'inactive', -- Approval Admin
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Log Aktivitas (Untuk pemantauan Admin)
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Tabel Threshold Global (Ambang batas kondisi tanaman)
CREATE TABLE IF NOT EXISTS global_thresholds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_type ENUM('temperature', 'humidity', 'air_quality', 'uv') UNIQUE NOT NULL,
    min_val DECIMAL(5,2) NOT NULL,
    max_val DECIMAL(5,2) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seeding default thresholds
INSERT INTO global_thresholds (sensor_type, min_val, max_val) VALUES
('temperature', 20.00, 30.00),
('humidity', 60.00, 85.00),
('air_quality', 0.00, 50.00),
('uv', 0.00, 5.00)
ON DUPLICATE KEY UPDATE min_val=VALUES(min_val), max_val=VALUES(max_val);

-- Seeding default admin account (email: admin@microclimeet.com, password: admin123)
INSERT INTO users (name, email, password, phone, role, status) VALUES
('Admin MicroClimeet', 'admin@microclimeet.com', '$2y$12$7bskA6r.V9oNbJrVTxRGP.k8M5aeT6taz/9ibZPzbr5TC4TIvHEBO', '083829634058', 'admin', 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name), password=VALUES(password), status=VALUES(status);
