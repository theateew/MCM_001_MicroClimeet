<?php
// Set default timezone to Asia/Jakarta (WIB GMT+7)
date_default_timezone_set('Asia/Jakarta');

$host = 'localhost';
$dbname = 'microclimeet';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Sync MySQL timezone to Asia/Jakarta WIB (+07:00)
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
