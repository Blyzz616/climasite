<?php
header("Content-Type: application/json");

// --- Configuration ---
$db_host = 'localhost';
$db_name = 'yourdbname';
$db_user = 'youruser';
$db_pass = 'yourpass';

// --- Read and parse incoming JSON ---
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// --- Validate payload ---
if (!isset($data['ip']) || !isset($data['uid'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing 'ip' or 'uid'"]);
    exit;
}

$ip = $data['ip'];
$uid = $data['uid'];

// --- Connect to MariaDB ---
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed", "details" => $e->getMessage()]);
    exit;
}

// --- Insert or update sensor record ---
$stmt = $pdo->prepare("SELECT ID FROM sensors WHERE UUID = ?");
$stmt->execute([$uid]);
$sensor = $stmt->fetch();

if ($sensor) {
    $stmt = $pdo->prepare("UPDATE sensors SET `IP Address` = ?, `First Seen` = NOW() WHERE UUID = ?");
    $stmt->execute([$ip, $uid]);
} else {
    $stmt = $pdo->prepare("INSERT INTO sensors (`UUID`, `IP Address`, `First Seen`) VALUES (?, ?, NOW())");
    $stmt->execute([$uid, $ip]);
}

// --- Done ---
http_response_code(200);
echo json_encode(["status" => "ok"]);
