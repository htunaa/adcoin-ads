<?php
/**
 * ADCOIN Ads - Get Slots API
 * Returns all ad slots (public endpoint)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $db = Database::getInstance();
    
    $slots = $db->fetchAll("SELECT * FROM ad_slots ORDER BY id");
    
    jsonResponse([
        'success' => true,
        'slots' => $slots,
        'total' => count($slots)
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to fetch slots'], 500);
}