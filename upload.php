<?php
// Enable full error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === Database Configuration ===
$host = 'localhost';                // Hostname of the database server
$db   = 'climasite';                // Name of the database
$user = '';                         // ⚠️ Replace with your database user
$pass = '';                         // ⚠️ Replace with your database password
$charset = 'utf8mb4';               // Character set for DB

// === Configure PDO for database connection ===
$dsn = "mysql:host=$host;dbname=$db;charset=$charset"; // Data Source Name
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return rows as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

// === Read and decode incoming JSON from POST body ===
$raw = file_get_contents("php://input"); // Raw POST data
$data = json_decode($raw, true);         // Decode JSON to PHP array

// === Validate input ===
if (!isset($data['uuid']) || !isset($data['temperature'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing uuid or temperature']);
    exit;
}

// Sanitize and assign inputs
$uuid = $data['uuid'];                     // Unique ID of the sensor
$temperature = floatval($data['temperature']); // Ensure it's a float
$timestamp = date('Y-m-d H:i:s');          // Current server timestamp

try {
    // === Connect to the database ===
    $pdo = new PDO($dsn, $user, $pass, $options);

    // === Create table if it doesn't already exist ===
    $pdo->exec("CREATE TABLE IF NOT EXISTS temperatures (
        id INT AUTO_INCREMENT PRIMARY KEY,   -- Auto-incrementing row ID
        uuid VARCHAR(64) NOT NULL,           -- Sensor UUID
        temperature FLOAT NOT NULL,          -- Temperature reading
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP -- Time of reading
    )");

    // === Insert the validated data ===
    $stmt = $pdo->prepare("INSERT INTO temperatures (uuid, temperature, timestamp) VALUES (?, ?, ?)");
    $stmt->execute([$uuid, $temperature, $timestamp]);

    // === Respond with success JSON ===
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // === Handle any database or connection errors ===
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
