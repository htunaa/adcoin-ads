<?php
/**
 * Simple router for API endpoints
 * Place this in api/index.php to enable clean URLs
 */

// Get request path
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove leading/trailing slashes
$path = trim($path, '/');

// Route API calls
if (strpos($path, 'api/') === 0) {
    $endpoint = str_replace('api/', '', $path);
    
    $routes = [
        'connect_wallet' => 'connect_wallet.php',
        'check_eligibility' => 'check_eligibility.php',
        'create_ad' => 'create_ad.php',
        'update_ad' => 'update_ad.php',
        'delete_ad' => 'delete_ad.php',
        'get_slots' => 'get_slots.php'
    ];
    
    if (isset($routes[$endpoint]) && file_exists(__DIR__ . '/' . $routes[$endpoint])) {
        require __DIR__ . '/' . $routes[$endpoint];
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
    exit;
}

// Not an API call - serve index.php
require __DIR__ . '/../index.php';