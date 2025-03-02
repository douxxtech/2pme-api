<?php
header('Content-Type: application/json');
require_once "../../utils/secrets.php";
require_once "../../utils/config.php"; 
require_once "../../utils/database.php";

apply_cors_headers();

if (!check_ip_whitelist()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'result' => 'Access denied: IP unauthorized']);
    exit();
}

$dbResult = getDbConnection();
if (!$dbResult['success']) {
    echo json_encode(['success' => false, 'result' => $dbResult['error']]);
    exit();
}
$conn = $dbResult['connection'];

$tableCheckQuery = "SHOW TABLES LIKE 'api_keys'";
$result = $conn->query($tableCheckQuery);

if ($result->num_rows === 0) {
    $createTableQuery = "
        CREATE TABLE api_keys (
            id INT AUTO_INCREMENT PRIMARY KEY,
            api_key VARCHAR(64) NOT NULL UNIQUE,
            last_used VARCHAR(45) DEFAULT NULL,
            times_used INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ";
    if (!$conn->query($createTableQuery)) {
        echo json_encode([
            'success' => false,
            'result' => 'Error creating table: ' . $conn->error
        ]);
        exit();
    }
}

do {
    $apiKey = bin2hex(random_bytes(16));
    $keyCheckQuery = $conn->prepare("SELECT COUNT(*) FROM api_keys WHERE api_key = ?");
    $keyCheckQuery->bind_param("s", $apiKey);
    $keyCheckQuery->execute();
    $keyCheckQuery->bind_result($keyCount);
    $keyCheckQuery->fetch();
    $keyCheckQuery->close();
} while ($keyCount > 0);

$insertQuery = $conn->prepare("
    INSERT INTO api_keys (api_key, last_used, times_used)
    VALUES (?, NULL, 0)
");
$insertQuery->bind_param("s", $apiKey);

if ($insertQuery->execute()) {
    echo json_encode([
        'success' => true,
        'result' => $apiKey
    ]);
} else {
    echo json_encode([
        'success' => false,
        'result' => 'Error inserting API key: ' . $insertQuery->error
    ]);
}

$insertQuery->close();

closeDbConnection($conn);
?>
