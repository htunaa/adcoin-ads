<?php
/**
 * ADCOIN Ads - Update Ad API
 * Updates an existing ad slot
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

$walletAddress = trim($_POST['wallet_address'] ?? '');
$linkUrl = trim($_POST['link_url'] ?? '');
$text = trim($_POST['text'] ?? '');

if (!validateWalletAddress($walletAddress)) {
    jsonResponse(['success' => false, 'message' => 'Invalid wallet address'], 400);
}

if (!checkRateLimit('update_ad')) {
    jsonResponse(['success' => false, 'message' => 'Rate limit exceeded'], 429);
}

if (empty($linkUrl) || !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
    jsonResponse(['success' => false, 'message' => 'Valid link URL is required'], 400);
}

if (strlen($text) > 255) {
    jsonResponse(['success' => false, 'message' => 'Text too long (max 255 chars)'], 400);
}

try {
    $db = Database::getInstance();
    $appConfig = $config['app'];
    
    // Verify user still eligible
    $solana = new Solana();
    $result = $solana->checkTokenBalance($walletAddress);
    
    if (!$result['eligible']) {
        jsonResponse(['success' => false, 'message' => 'No longer eligible. Need ' . number_format($config['solana']['min_balance']) . ' tokens'], 403);
    }
    
    // Get user
    $user = $db->fetchOne("SELECT * FROM users WHERE wallet_address = ?", [$walletAddress]);
    
    if (!$user || !$user['slot_id']) {
        jsonResponse(['success' => false, 'message' => 'No slot found. Create one first.'], 404);
    }
    
    // Get current slot
    $slot = $db->fetchOne("SELECT * FROM ad_slots WHERE id = ?", [$user['slot_id']]);
    
    if ($slot['owner_wallet'] !== $walletAddress) {
        jsonResponse(['success' => false, 'message' => 'You do not own this slot'], 403);
    }
    
    $imageUrl = $slot['image_url'];
    
    // Handle file upload (optional - keep existing if not uploaded)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $appConfig['allowed_extensions'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid file type'], 400);
        }
        
        if ($file['size'] > $appConfig['max_upload_size']) {
            jsonResponse(['success' => false, 'message' => 'File too large'], 400);
        }
        
        // Delete old image if exists
        if ($slot['image_url']) {
            $oldFile = str_replace($appConfig['upload_url'], $appConfig['upload_dir'], $slot['image_url']);
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }
        
        $filename = $walletAddress . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $uploadPath = $appConfig['upload_dir'] . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $imageUrl = $appConfig['upload_url'] . $filename;
        }
    }
    
    // Update slot
    $db->execute(
        "UPDATE ad_slots SET image_url = ?, text = ?, link_url = ?, last_verified_at = NOW() WHERE id = ?",
        [$imageUrl, $text, $linkUrl, $user['slot_id']]
    );
    
    jsonResponse([
        'success' => true,
        'message' => 'Ad updated successfully!',
        'slot_id' => $user['slot_id'],
        'image_url' => $imageUrl,
        'text' => $text,
        'link_url' => $linkUrl
    ]);
    
} catch (Exception $e) {
    error_log("Update ad error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Internal server error'], 500);
}