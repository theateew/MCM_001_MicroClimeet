<?php
/**
 * config/migrate_v5.php
 * Migration v5: Google OAuth 2.0, Email OTP Verification & Email 2FA
 */

require_once __DIR__ . '/database.php';

echo "<h2>Starting Migration v5 (Google Sign-In & Email OTP/2FA)...</h2>";

try {
    // 1. Add google_id, email_verified_at, two_factor_enabled to users table
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('google_id', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL AFTER password");
        echo "<p style='color:green;'>✓ Added column 'google_id' to users table.</p>";
    } else {
        echo "<p style='color:blue;'>ℹ Column 'google_id' already exists.</p>";
    }

    if (!in_array('email_verified_at', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER status");
        echo "<p style='color:green;'>✓ Added column 'email_verified_at' to users table.</p>";
    } else {
        echo "<p style='color:blue;'>ℹ Column 'email_verified_at' already exists.</p>";
    }

    if (!in_array('two_factor_enabled', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0 AFTER email_verified_at");
        echo "<p style='color:green;'>✓ Added column 'two_factor_enabled' to users table.</p>";
    } else {
        echo "<p style='color:blue;'>ℹ Column 'two_factor_enabled' already exists.</p>";
    }

    // 2. Create email_verifications table for 6-digit OTP codes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(191) NOT NULL,
            otp_code VARCHAR(10) NOT NULL,
            purpose ENUM('register', '2fa', 'reset') DEFAULT 'register',
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_otp (email, otp_code, purpose)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p style='color:green;'>✓ Table 'email_verifications' verified/created successfully.</p>";

    echo "<h3 style='color:green;'>Migration v5 Completed Successfully! 🎉</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>Migration Failed: " . htmlspecialchars($e->getMessage()) . "</h3>";
}
