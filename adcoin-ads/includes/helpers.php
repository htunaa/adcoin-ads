<?php
/**
 * CORS and API helper functions
 */

function setCorsHeaders() {
    $config = require __DIR__ . '/config.php';
    $security = $config['security'];
    
    if ($security['cors_enabled']) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Accept");
        header("Access-Control-Max-Age: 86400");
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getClientIp() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    
    return $ip;
}

function validateWalletAddress($address) {
    if (empty($address)) {
        return false;
    }
    if (strlen($address) < 32 || strlen($address) > 44) {
        return false;
    }
    return preg_match('/^[1-9A-HJ-NP-Za-km-z]+$/', $address) === 1;
}

function checkRateLimit($endpoint) {
    $config = require __DIR__ . '/config.php';
    $rateLimit = $config['rate_limit'];
    $ip = getClientIp();
    
    $db = require __DIR__ . '/db.php';
    
    // Clean old entries first
    $db->execute(
        "DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)",
        [$rateLimit['window_seconds']]
    );
    
    // Check current requests
    $stmt = $db->query(
        "SELECT request_count FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)",
        [$ip, $endpoint, $rateLimit['window_seconds']]
    );
    $row = $stmt->fetch();
    
    if ($row && $row['request_count'] >= $rateLimit['max_requests']) {
        return false;
    }
    
    // Increment or insert
    if ($row) {
        $db->execute(
            "UPDATE rate_limits SET request_count = request_count + 1 WHERE ip_address = ? AND endpoint = ?",
            [$ip, $endpoint]
        );
    } else {
        $db->execute(
            "INSERT INTO rate_limits (ip_address, endpoint, request_count) VALUES (?, ?, 1)",
            [$ip, $endpoint]
        );
    }
    
    return true;
}