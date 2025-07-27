<?php
// includes/db.php

$host = 'localhost';                // Hostname of the database server
$db   = 'climasite';                // Name of the database
$user = '';                         // ⚠️ Replace with your database user
$pass = '';                         // ⚠️ Replace with your database password
$charset = 'utf8mb4';               // Character set for DB

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '+00:00'");  // Force MySQL session to UTC
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
