<?php
header('Content-Type: application/json');
require_once "../../utils/logsapi.php";
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

$apiKey = $_GET['key'] ?? null;

$ipForLogging = process_ip_for_logging($_SERVER['REMOTE_ADDR']);
logapi($apiKey, $ipForLogging);

if (!$apiKey) {
    echo json_encode(['success' => false, 'result' => 'Missing required parameter: key.']);
    exit();
}

$keyCheckQuery = $conn->prepare("SELECT id FROM api_keys WHERE api_key = ?");
$keyCheckQuery->bind_param("s", $apiKey);
$keyCheckQuery->execute();
$keyCheckQuery->store_result();
if ($keyCheckQuery->num_rows === 0) {
    echo json_encode(['success' => false, 'result' => 'Invalid API key.']);
    exit();
}
$keyCheckQuery->close();

$getSubdomainsQuery = $conn->prepare("SELECT name, type, value, created_at FROM subdomains WHERE api_key = ?");
$getSubdomainsQuery->bind_param("s", $apiKey);
$getSubdomainsQuery->execute();
$getSubdomainsQuery->bind_result($name, $type, $value, $createdAt);

$subdomains = [];
while ($getSubdomainsQuery->fetch()) {
    $subdomains[] = [
        'name' => $name,
        'type' => $type,
        'value' => $value,
        'created_at' => $createdAt
    ];
}
$getSubdomainsQuery->close();

echo json_encode(['success' => true, 'subdomains' => $subdomains]);

closeDbConnection($conn);
?>
