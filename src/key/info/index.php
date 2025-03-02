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

$apiKey = $_GET['key'] ?? null;


if (!$apiKey) {
    echo json_encode([
        'success' => false,
        'result' => 'Missing required parameter: key.'
    ]);
    exit();
}

$keyCheckQuery = $conn->prepare("
    SELECT api_key, last_used, times_used, created_at 
    FROM api_keys 
    WHERE api_key = ?
");
$keyCheckQuery->bind_param("s", $apiKey);
$keyCheckQuery->execute();
$result = $keyCheckQuery->get_result();

if ($result->num_rows === 0) {
    $keyCheckQuery->close();
    $conn->close();
    echo json_encode([
        'success' => false,
        'result' => 'API key not found'
    ]);
    exit();
}

$keyData = $result->fetch_assoc();
$keyCheckQuery->close();

echo json_encode([
    'success' => true,
    'result' => [
        'key' => $keyData['api_key'],
        'last_used' => $keyData['last_used'],
        'times_used' => $keyData['times_used'],
        'created_at' => $keyData['created_at']
    ]
]);

closeDbConnection($conn);
?>
