<?php
// api/sensors.php

require_once(__DIR__ . '/../includes/db.php');

// Force PHP timezone to UTC
date_default_timezone_set('UTC');

$sql = "
SELECT
  rooms.roomName,
  sensors.lastTemp,
  sensors.lastSeen,
  UNIX_TIMESTAMP(sensors.lastSeen) AS lastSeen_unix
FROM sensors
JOIN rooms ON sensors.roomID = rooms.roomID
ORDER BY rooms.roomName ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$response = [
    'server_time' => time(),
    'sensors' => $sensors
];

header('Content-Type: application/json');
echo json_encode($response);
exit;
