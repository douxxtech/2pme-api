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
$subdomain = $_GET['subdomain'] ?? null;

$ipForLogging = process_ip_for_logging($_SERVER['REMOTE_ADDR']);
logapi($apiKey, $ipForLogging);

if (!$apiKey || !$subdomain) {
    echo json_encode(['success' => false, 'result' => 'Missing required parameters: key or subdomain.']);
    exit();
}

$getSubdomainQuery = $conn->prepare("SELECT dns_record_id, api_key FROM subdomains WHERE name = ?");
$getSubdomainQuery->bind_param("s", $subdomain);
$getSubdomainQuery->execute();
$getSubdomainQuery->bind_result($dnsRecordId, $dbApiKey);
$getSubdomainQuery->fetch();
$getSubdomainQuery->close();

if (!$dnsRecordId) {
    echo json_encode(['success' => false, 'result' => 'Subdomain does not exist.']);
    exit();
}

if ($apiKey !== $dbApiKey) {
    echo json_encode(['success' => false, 'result' => 'API key does not match the subdomain.']);
    exit();
}

$cloudflare_api_url = $cloudflare_api_url . '/' . $dnsRecordId;

$options = [
    'http' => [
        'method' => 'DELETE',
        'header' => [
            'Authorization: Bearer ' . $cloudflare_api_key
        ],
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($cloudflare_api_url, false, $context);
$responseData = json_decode($response, true);

if (!$responseData || !$responseData['success']) {
    echo json_encode(['success' => false, 'result' => 'Failed to delete DNS record on Cloudflare.']);
    exit();
}

$deleteQuery = $conn->prepare("DELETE FROM subdomains WHERE name = ?");
$deleteQuery->bind_param("s", $subdomain);
if (!$deleteQuery->execute()) {
    echo json_encode(['success' => false, 'result' => 'Failed to delete subdomain from database.']);
    exit();
}
$deleteQuery->close();

echo json_encode(['success' => true, 'result' => "Subdomain [$subdomain] successfully deleted."]);

closeDbConnection($conn);
?>
