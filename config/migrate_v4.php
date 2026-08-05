<?php
require_once __DIR__ . '/database.php';

try {
    // 1. Update status ENUM in users table to include 'suspended'
    $pdo->exec("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'inactive';");
    echo "Users table status updated to include 'suspended'.\n";

    // 2. Create crop_cycles table
    $sql = "CREATE TABLE IF NOT EXISTS crop_cycles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        greenhouse_id INT NOT NULL,
        crop_name VARCHAR(100) NOT NULL DEFAULT 'Melon Premium',
        planting_date DATE NOT NULL,
        estimated_harvest_date DATE NULL,
        target_ec DECIMAL(4,2) DEFAULT 2.00,
        target_ph DECIMAL(3,1) DEFAULT 6.5,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY gh_crop (greenhouse_id),
        FOREIGN KEY (greenhouse_id) REFERENCES greenhouses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Table 'crop_cycles' created successfully.\n";

    // Seed default crop cycle for existing greenhouses if empty
    $ghs = $pdo->query("SELECT id, commodity FROM greenhouses")->fetchAll();
    foreach ($ghs as $gh) {
        $pDate = date('Y-m-d', strtotime('-20 days')); // Default: planted 20 days ago
        $hDate = date('Y-m-d', strtotime('+55 days')); // Default: 75 days total cycle
        $crop  = !empty($gh['commodity']) ? $gh['commodity'] : 'Melon Premium';
        
        $pdo->prepare(
            "INSERT INTO crop_cycles (greenhouse_id, crop_name, planting_date, estimated_harvest_date, target_ec, target_ph)
             VALUES (?, ?, ?, ?, 2.0, 6.5)
             ON DUPLICATE KEY UPDATE crop_name=VALUES(crop_name)"
        )->execute([$gh['id'], $crop, $pDate, $hDate]);
    }
    echo "Default crop cycle seeded for greenhouses.\n";

} catch (PDOException $e) {
    echo "Migration v4 error: " . $e->getMessage() . "\n";
}
