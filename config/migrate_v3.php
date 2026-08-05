<?php
require_once __DIR__ . '/database.php';

try {
    // 1. Create greenhouse_thresholds table for per-device threshold configuration
    $sql = "CREATE TABLE IF NOT EXISTS greenhouse_thresholds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        greenhouse_id INT NOT NULL,
        sensor_type ENUM('temperature', 'humidity', 'air_quality', 'uv') NOT NULL,
        min_val DECIMAL(5,2) NOT NULL,
        max_val DECIMAL(5,2) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY gh_sensor (greenhouse_id, sensor_type),
        FOREIGN KEY (greenhouse_id) REFERENCES greenhouses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Table 'greenhouse_thresholds' successfully created or already exists.\n";
} catch (PDOException $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
