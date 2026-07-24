<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // 1. Connect to MySQL host to create database
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connecting to MySQL server...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS microclimeet");
    echo "Database 'microclimeet' verified/created.\n";
    
    // 2. Re-connect with dbname to execute schema
    $pdo = new PDO("mysql:host=$host;dbname=microclimeet;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $schemaPath = __DIR__ . '/schema.sql';
    if (!file_exists($schemaPath)) {
        die("Error: schema.sql file not found at $schemaPath\n");
    }
    
    $sql = file_get_contents($schemaPath);
    echo "Executing schema.sql...\n";
    $pdo->exec($sql);
    echo "Database setup successfully completed!\n";
    echo "Default Admin: admin@microclimeet.com (password: admin123)\n";
    
} catch (PDOException $e) {
    die("Error during database initialization: " . $e->getMessage() . "\n");
}
?>
