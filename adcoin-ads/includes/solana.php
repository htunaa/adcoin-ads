<?php
/**
 * ADCOIN Ads - Solana Integration
 * Handles token balance checking via Solana RPC API
 */

require_once __DIR__ . '/config.php';

class Solana {
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/config.php';
    }
    
    /**
     * Check if a wallet holds enough ADCOIN tokens
     * 
     * @param string $walletAddress Solana wallet address
     * @return array ['success', 'balance', 'eligible', 'message']
     */
    public function checkTokenBalance($walletAddress) {
        $config = $this->config['solana'];
        
        if (!$this->isValidWalletAddress($walletAddress)) {
            return [
                'success' => false,
                'balance' => 0,
                'eligible' => false,
                'message' => 'Invalid wallet address format'
            ];
        }
        
        try {
            $balance = $this->getTokenBalance($walletAddress, $config['token_mint']);
            $eligible = $balance >= $config['min_balance'];
            
            return [
                'success' => true,
                'balance' => $balance,
                'eligible' => $eligible,
                'message' => $eligible 
                    ? 'Eligible - holds ' . number_format($balance) . ' tokens'
                    : 'Not eligible - needs ' . number_format($config['min_balance']) . ' tokens'
            ];
        } catch (Exception $e) {
            error_log("Solana API error: " . $e->getMessage());
            return [
                'success' => false,
                'balance' => 0,
                'eligible' => false,
                'message' => 'Failed to check token balance: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get token balance for a wallet
     * 
     * @param string $walletAddress Wallet address
     * @param string $tokenMint Token mint address
     * @return int Token balance (raw, not decimals)
     */
    private function getTokenBalance($walletAddress, $tokenMint) {
        $config = $this->config['solana'];
        $rpcUrl = $config['rpc_url'];
        
        // Build the request to get token accounts
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getTokenAccountsByOwner',
            'params' => [
                $walletAddress,
                ['mint' => $tokenMint],
                ['encoding' => 'jsonParsed']
            ]
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rpcUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            throw new Exception("RPC request failed with HTTP code: " . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new Exception("RPC error: " . json_encode($data['error']));
        }
        
        // Parse the response to get balance
        if (isset($data['result']['value']) && count($data['result']['value']) > 0) {
            $account = $data['result']['value'][0];
            if (isset($account['account']['data']['parsed']['info']['tokenAmount']['amount'])) {
                return (int) $account['account']['data']['parsed']['info']['tokenAmount']['amount'];
            }
        }
        
        // No token account found = 0 balance
        return 0;
    }
    
    /**
     * Get SOL balance (optional - for debugging)
     */
    public function getSolBalance($walletAddress) {
        $config = $this->config['solana'];
        $rpcUrl = $config['rpc_url'];
        
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getBalance',
            'params' => [$walletAddress]
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rpcUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['result']['value'])) {
            return $data['result']['value'] / 1000000000; // Convert lamports to SOL
        }
        
        return 0;
    }
    
    /**
     * Validate Solana wallet address format
     * Basic validation - checks length and character set
     */
    public function isValidWalletAddress($address) {
        if (empty($address)) {
            return false;
        }
        
        // Solana addresses are base58 encoded, 32-44 characters
        if (strlen($address) < 32 || strlen($address) > 44) {
            return false;
        }
        
        // Base58 characters only
        return preg_match('/^[1-9A-HJ-NP-Za-km-z]+$/', $address) === 1;
    }
}

/**
 * Standalone function for checking token balance
 * Can be called from any PHP file
 */
function checkTokenBalance($wallet_address) {
    $solana = new Solana();
    return $solana->checkTokenBalance($wallet_address);
}