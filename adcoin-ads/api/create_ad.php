<?php
/**
 * ADCOIN Ads - Create Ad API
 * Creates a new ad slot for eligible users
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

if (!checkRateLimit('create_ad')) {
    jsonResponse(['success' => false, 'message' => 'Rate limit exceeded'], 429);
}

// Validate link URL
if (empty($linkUrl) || !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
    jsonResponse(['success' => false, 'message' => 'Valid link URL is required'], 400);
}

if (strlen($text) > 255) {
    jsonResponse(['success' => false, 'message' => 'Text too long (max 255 chars)'], 400);
}

try {
    $db = Database::getInstance();
    $appConfig = $config['app'];
    
    // Verify user is eligible
    $solana = new Solana();
    $result = $solana->checkTokenBalance($walletAddress);
    
    if (!$result['eligible']) {
        jsonResponse(['success' => false, 'message' => 'Not eligible - need ' . number_format($config['solana']['min_balance']) . ' tokens'], 403);
    }
    
    // Get user
    $user = $db->fetchOne("SELECT * FROM users WHERE wallet_address = ?", [$walletAddress]);
    
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found. Please reconnect wallet.'], 404);
    }
    
    // Check if user already has a slot
    if ($user['slot_id']) {
        jsonResponse(['success' => false, 'message' => 'You already have a slot. Use update instead.'], 400);
    }
    
    // Find available slot (random)
    $availableSlots = $db->fetchAll(
        "SELECT id FROM ad_slots WHERE owner_wallet IS NULL ORDER BY RAND() LIMIT 1"
    );
    
    if (empty($availableSlots)) {
        jsonResponse(['success' => false, 'message' => 'No slots available'], 400);
    }
    
    $slotId = $availableSlots[0]['id'];
    $imageUrl = null;
    
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $appConfig['allowed_extensions'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $appConfig['allowed_extensions'])], 400);
        }
        
        if ($file['size'] > $appConfig['max_upload_size']) {
            jsonResponse(['success' => false, 'message' => 'File too large. Max: ' . ($appConfig['max_upload_size'] / 1024 / 1024) . 'MB'], 400);
        }
        
        // Generate unique filename
        $filename = $walletAddress . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $uploadPath = $appConfig['upload_dir'] . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $imageUrl = $appConfig['upload_url'] . $filename;
        }
    }
    
    // Update slot
    $db->execute(
        "UPDATE ad_slots SET owner_wallet = ?, image_url = ?, text = ?, link_url = ?, created_at = NOW(), last_verified_at = NOW() WHERE id = ?",
        [$walletAddress, $imageUrl, $text, $linkUrl, $slotId]
    );
    
    // Update user
    $db->execute("UPDATE users SET slot_id = ? WHERE wallet_address = ?", [$slotId, $walletAddress]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Ad created successfully!',
        'slot_id' => $slotId,
        'image_url' => $imageUrl,
        'text' => $text,
        'link_url' => $linkUrl
    ]);
    
} catch (Exception $e) {
    error_log("Create ad error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Internal server error'], 500);
}