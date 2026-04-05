<?php
/**
 * ADCOIN Ads - Check Eligibility API
 * Returns token balance and eligibility status
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

if (!checkRateLimit('check_eligibility')) {
    jsonResponse(['success' => false, 'message' => 'Rate limit exceeded'], 429);
}

try {
    $solana = new Solana();
    $result = $solana->checkTokenBalance($walletAddress);
    
    jsonResponse([
        'success' => $result['success'],
        'eligible' => $result['eligible'],
        'balance' => $result['balance'],
        'min_required' => $config['solana']['min_balance'],
        'message' => $result['message']
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to check eligibility'], 500);
}