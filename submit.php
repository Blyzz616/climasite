<?php
require_once('config.php');

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['uuid']) || !isset($data['temperature'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing uuid or temperature']);
    exit;
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if sensor exists
    $stmt = $pdo->prepare("SELECT id FROM sensors WHERE sensor_uuid = ?");
    $stmt->execute([$data['uuid']]);
    $sensor = $stmt->fetch();

    if (!$sensor) {
        // Insert new sensor
        $stmt = $pdo->prepare("INSERT INTO sensors (sensor_uuid, model, description) VALUES (?, ?, ?)");
        $stmt->execute([$data['uuid'], 'ESP32', 'Auto-added']);
        $sensor_id = $pdo->lastInsertId();
    } else {
        $sensor_id = $sensor['id'];
    }

    // Insert temperature reading
    $stmt = $pdo->prepare("INSERT INTO data (sensor_id, temperature) VALUES (?, ?)");
    $stmt->execute([$sensor_id, $data['temperature']]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
