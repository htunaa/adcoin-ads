<?php
/**
 * ADCOIN Ads - Connect Wallet API
 * Handles wallet connection and eligibility check
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/solana.php';
require_once __DIR__ . '/../includes/helpers.php';

setCorsHeaders();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$walletAddress = trim($input['wallet_address'] ?? '');

// Validate wallet address
if (!validateWalletAddress($walletAddress)) {
    jsonResponse(['success' => false, 'message' => 'Invalid wallet address'], 400);
}

// Check rate limit
if (!checkRateLimit('connect_wallet')) {
    jsonResponse(['success' => false, 'message' => 'Rate limit exceeded. Try again later.'], 429);
}

try {
    $db = Database::getInstance();
    
    // Check token balance via Solana API
    $solana = new Solana();
    $tokenResult = $solana->checkTokenBalance($walletAddress);
    
    if (!$tokenResult['success']) {
        jsonResponse([
            'success' => false,
            'message' => $tokenResult['message'],
            'eligible' => false
        ], 500);
    }
    
    $eligible = $tokenResult['eligible'];
    $balance = $tokenResult['balance'];
    
    // Get or create user record
    $user = $db->fetchOne("SELECT * FROM users WHERE wallet_address = ?", [$walletAddress]);
    
    if (!$user) {
        // Create new user if eligible
        if ($eligible) {
            $db->execute(
                "INSERT INTO users (wallet_address, slot_id) VALUES (?, NULL)",
                [$walletAddress]
            );
            $user = $db->fetchOne("SELECT * FROM users WHERE wallet_address = ?", [$walletAddress]);
        }
    } else {
        // If user exists but no longer eligible, clear their slot
        if (!$eligible && $user['slot_id']) {
            $db->execute("UPDATE ad_slots SET owner_wallet = NULL, link_url = '', image_url = NULL, text = NULL, last_verified_at = NULL WHERE id = ?", [$user['slot_id']]);
            $db->execute("UPDATE users SET slot_id = NULL WHERE wallet_address = ?", [$walletAddress]);
            $user['slot_id'] = null;
        }
    }
    
    // Get slot info if user has one
    $slot = null;
    if ($user && $user['slot_id']) {
        $slot = $db->fetchOne("SELECT * FROM ad_slots WHERE id = ?", [$user['slot_id']]);
    }
    
    jsonResponse([
        'success' => true,
        'eligible' => $eligible,
        'balance' => $balance,
        'wallet' => $walletAddress,
        'has_slot' => $user && $user['slot_id'] !== null,
        'slot_id' => $user['slot_id'] ?? null,
        'slot' => $slot,
        'min_required' => $config['solana']['min_balance']
    ]);
    
} catch (Exception $e) {
    error_log("Connect wallet error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Internal server error'], 500);
}