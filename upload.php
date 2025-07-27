<?php
// Display errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use the shared DB connection
require_once(__DIR__ . '/../includes/db.php');

// Read and decode raw JSON input
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Validate input
if (!isset($data['uuid']) || !isset($data['temperature'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing uuid or temperature']);
    exit;
}

$uuid = $data['uuid'];
$temperature = floatval($data['temperature']);
$timestamp = gmdate('Y-m-d H:i:s'); // Use UTC

try {
    // Create temperatures table if it doesn't exist (optional for production)
    $pdo->exec("CREATE TABLE IF NOT EXISTS temperatures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uuid VARCHAR(64) NOT NULL,
        temperature FLOAT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert temperature reading
    $stmt = $pdo->prepare("INSERT INTO temperatures (uuid, temperature, timestamp) VALUES (?, ?, ?)");
    $stmt->execute([$uuid, $temperature, $timestamp]);

    // Update or insert into sensors table
    $stmt = $pdo->prepare("SELECT sensorID FROM sensors WHERE uuid = ?");
    $stmt->execute([$uuid]);
    $sensor = $stmt->fetch();

    if ($sensor) {
        $stmt = $pdo->prepare("UPDATE sensors SET lastTemp = ?, lastSeen = ? WHERE uuid = ?");
        $stmt->execute([$temperature, $timestamp, $uuid]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO sensors (uuid, lastTemp, lastSeen) VALUES (?, ?, ?)");
        $stmt->execute([$uuid, $temperature, $timestamp]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
