<?php
/**
 * ADCOIN Ads - Delete Ad API
 * Deletes user's ad slot
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/solana.php';
require_once __DIR__ . '/../includes/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$walletAddress = trim($input['wallet_address'] ?? '');

if (!validateWalletAddress($walletAddress)) {
    jsonResponse(['success' => false, 'message' => 'Invalid wallet address'], 400);
}

if (!checkRateLimit('delete_ad')) {
    jsonResponse(['success' => false, 'message' => 'Rate limit exceeded'], 429);
}

try {
    $db = Database::getInstance();
    $appConfig = $config['app'];
    
    // Get user
    $user = $db->fetchOne("SELECT * FROM users WHERE wallet_address = ?", [$walletAddress]);
    
    if (!$user || !$user['slot_id']) {
        jsonResponse(['success' => false, 'message' => 'No slot found'], 404);
    }
    
    // Get slot
    $slot = $db->fetchOne("SELECT * FROM ad_slots WHERE id = ?", [$user['slot_id']]);
    
    if ($slot['owner_wallet'] !== $walletAddress) {
        jsonResponse(['success' => false, 'message' => 'You do not own this slot'], 403);
    }
    
    // Delete image if exists
    if ($slot['image_url']) {
        $imageFile = str_replace($appConfig['upload_url'], $appConfig['upload_dir'], $slot['image_url']);
        if (file_exists($imageFile)) {
            @unlink($imageFile);
        }
    }
    
    // Clear slot
    $db->execute(
        "UPDATE ad_slots SET owner_wallet = NULL, image_url = NULL, text = NULL, link_url = '', last_verified_at = NULL WHERE id = ?",
        [$user['slot_id']]
    );
    
    // Clear user slot reference
    $db->execute("UPDATE users SET slot_id = NULL WHERE wallet_address = ?", [$walletAddress]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Ad deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Delete ad error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Internal server error'], 500);
}