<?php
/**
 * ADCOIN Ads - Cron Job: Verify Slots
 * 
 * Runs periodically to verify that slot owners still hold enough tokens
 * Removes slots from users who no longer meet the eligibility requirement
 * 
 * Recommended: Run every 5-10 minutes via cron
 * 
 * Usage: curl "https://your-domain.com/cron/verify_slots.php"
 * Or add to crontab: */10 * * * * curl -s "https://your-domain.com/cron/verify_slots.php"
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/solana.php';

$logFile = __DIR__ . '/verify_log.txt';

function logMsg($msg) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMsg = "[$timestamp] $msg\n";
    file_put_contents($logFile, $logMsg, FILE_APPEND);
    echo $logMsg;
}

logMsg("=== Starting slot verification ===");

try {
    $db = Database::getInstance();
    $solana = new Solana();
    $config = require __DIR__ . '/../includes/config.php';
    $appConfig = $config['app'];
    
    // Get all occupied slots
    $occupiedSlots = $db->fetchAll(
        "SELECT s.*, u.id as user_id FROM ad_slots s 
         JOIN users u ON u.slot_id = s.id 
         WHERE s.owner_wallet IS NOT NULL"
    );
    
    logMsg("Found " . count($occupiedSlots) . " occupied slots to verify");
    
    $removed = 0;
    $verified = 0;
    
    foreach ($occupiedSlots as $slot) {
        $wallet = $slot['owner_wallet'];
        
        logMsg("Checking wallet: " . substr($wallet, 0, 8) . "...");
        
        // Check token balance
        $result = $solana->checkTokenBalance($wallet);
        
        if (!$result['success']) {
            logMsg("  ERROR: Failed to check balance for " . substr($wallet, 0, 8));
            continue;
        }
        
        logMsg("  Balance: " . number_format($result['balance']) . " tokens, Eligible: " . ($result['eligible'] ? 'YES' : 'NO'));
        
        if ($result['eligible']) {
            // Still eligible - update verification timestamp
            $db->execute(
                "UPDATE ad_slots SET last_verified_at = NOW() WHERE id = ?",
                [$slot['id']]
            );
            $verified++;
        } else {
            // Not eligible anymore - remove slot
            logMsg("  REMOVING slot - wallet no longer eligible");
            
            // Delete image file if exists
            if ($slot['image_url']) {
                $imageFile = str_replace($appConfig['upload_url'], $appConfig['upload_dir'], $slot['image_url']);
                if (file_exists($imageFile)) {
                    @unlink($imageFile);
                    logMsg("  Deleted image: " . basename($imageFile));
                }
            }
            
            // Clear slot in database
            $db->execute(
                "UPDATE ad_slots SET owner_wallet = NULL, image_url = NULL, text = NULL, link_url = '', last_verified_at = NULL WHERE id = ?",
                [$slot['id']]
            );
            
            // Clear user slot reference
            $db->execute(
                "UPDATE users SET slot_id = NULL WHERE wallet_address = ?",
                [$wallet]
            );
            
            $removed++;
        }
    }
    
    logMsg("=== Verification complete: $verified verified, $removed removed ===");
    
} catch (Exception $e) {
    logMsg("ERROR: " . $e->getMessage());
    http_response_code(500);
}