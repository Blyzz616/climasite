<?php
// api.php — responds with JSON of room names + temps from the last 60 seconds

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

date_default_timezone_set('America/Vancouver');

// DB credentials
$host = 'localhost';
$db   = 'climasite';
$user = '';              //
$pass = '';              //
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Get current time and minus 60 seconds
    $cutoff = date('Y-m-d H:i:s', time() - 60);

    // Get latest temp readings from last 60s, join with rooms
    $sql = "
        SELECT r.name AS room_name, t.temperature
        FROM temperatures t
        INNER JOIN groups g ON t.uuid = g.sensor_id
        INNER JOIN rooms r ON g.room_id = r.roomID
        WHERE t.timestamp >= :cutoff
          AND (g.end_time IS NULL OR g.end_time >= t.timestamp)
          AND (g.start_time IS NULL OR g.start_time <= t.timestamp)
        ORDER BY r.name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['cutoff' => $cutoff]);
    $result = $stmt->fetchAll();

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
