<?php
/**
 * Configuration file for the API
 * Contains settings that can be modified without changing the core code
 */


$auto_update = true;                  // Enable/disable automatic update every day

// CORS Configuration
$config_cors = [
    'enabled' => false,               // Enable/disable CORS headers
    'allow_origin' => '*',            // Allowed origins (*, specific domain, or array of domains)
    'allow_methods' => 'GET, POST',   // Allowed HTTP methods
    'allow_headers' => 'Content-Type, Authorization, X-Requested-With',  // Allowed headers
    'max_age' => 86400                // Preflight cache time in seconds (1 day)
];

// IP Logging Configuration
$config_ip_logging = [
    'enabled' => true,                // Enable/disable IP logging
    'anonymize' => false              // Anonymize IPs (replace last octet with 0)
];

// Subdomain Limits
$config_subdomain_limits = [
    'enabled' => false,                // Enable/disable subdomain limits
    'max_per_key' => 10               // Maximum number of subdomains per API key
];

// IP Whitelisting
$config_ip_whitelist = [
    'enabled' => false,               // Enable IP whitelist (only listed IPs can access)
    'whitelist' => [                  // List of whitelisted IPs
        '127.0.0.1',
        '::1'
        // Add more IPs here
    ]
];

/**
 * Helper function to apply CORS headers
 */
function apply_cors_headers() {
    global $config_cors;
    
    if (!$config_cors['enabled']) {
        return;
    }
    
    header('Access-Control-Allow-Origin: ' . $config_cors['allow_origin']);
    header('Access-Control-Allow-Methods: ' . $config_cors['allow_methods']);
    header('Access-Control-Allow-Headers: ' . $config_cors['allow_headers']);
    header('Access-Control-Max-Age: ' . $config_cors['max_age']);
    
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        header('HTTP/1.1 204 No Content');
        exit();
    }
}

/**
 * Helper function to check IP whitelist
 * @return bool True if IP is allowed, false if blocked
 */
function check_ip_whitelist() {
    global $config_ip_whitelist;
    
    if (!$config_ip_whitelist['enabled']) {
        return true;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    
    if (in_array($ip, $config_ip_whitelist['whitelist'])) {
        return true;
    }
    
    return false;
}

/**
 * Helper function to check subdomain limits
 * @param string $apiKey The API key to check limits for
 * @param mysqli $conn Database connection
 * @return array Result with success and message
 */
function check_subdomain_limits($apiKey, $conn) {
    global $config_subdomain_limits;
    
    if (!$config_subdomain_limits['enabled']) {
        return ['success' => true];
    }
    
    $countQuery = $conn->prepare("SELECT COUNT(*) FROM subdomains WHERE api_key = ?");
    $countQuery->bind_param("s", $apiKey);
    $countQuery->execute();
    $countQuery->bind_result($count);
    $countQuery->fetch();
    $countQuery->close();
    
    if ($count >= $config_subdomain_limits['max_per_key']) {
        return [
            'success' => false,
            'result' => 'Maximum number of subdomains reached (' . $config_subdomain_limits['max_per_key'] . ')'
        ];
    }
    
    return ['success' => true];
}

/**
 * Helper function to process IP for logging based on configuration
 * @param string $ip The IP address to potentially anonymize
 * @return string The original or anonymized IP
 */
function process_ip_for_logging($ip) {
    global $config_ip_logging;
    
    if (!$config_ip_logging['enabled']) {
        return "IP logging is disabled.";
    }
    
    if (!$config_ip_logging['anonymize']) {
        return $ip;
    }
    
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        $parts[3] = '0';
        return implode('.', $parts);
    }
    
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $full = explode(':', strtolower($ip));
        return substr($ip, 0, strrpos($ip, ':')) . ':0000';
    }
    
    return $ip;
}

?>