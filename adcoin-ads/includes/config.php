<?php
/**
 * ADCOIN Ads - Configuration
 * 
 * Edit these settings for your deployment
 */

return [
    // Database configuration
    'db' => [
        'host' => 'localhost',
        'name' => 'adcoin_ads',
        'user' => 'your_db_username',
        'pass' => 'your_db_password',
        'charset' => 'utf8mb4'
    ],

    // Solana RPC API Configuration
    // Use Helius, QuickNode, or public RPC
    'solana' => [
        // RPC endpoint URL
        'rpc_url' => 'https://api.mainnet-beta.solana.com',
        
        // ADCOIN Token Mint Address (Solana)
        // Replace with actual ADCOIN token address
        'token_mint' => 'YourADCOINTokenMintAddressHere',
        
        // Minimum token balance required (10,000,000 = 1% of 1B supply)
        'min_balance' => 10000000,
        
        // Optional: API key for premium RPC services
        'api_key' => ''
    ],

    // Application settings
    'app' => [
        'name' => 'ADCOIN Ads',
        'url' => 'https://your-domain.com',
        'upload_dir' => __DIR__ . '/../uploads/',
        'upload_url' => '/uploads/',
        'max_upload_size' => 2097152, // 2MB in bytes
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'total_slots' => 100
    ],

    // Rate limiting
    'rate_limit' => [
        'max_requests' => 10,
        'window_seconds' => 60
    ],

    // Security
    'security' => [
        'cors_enabled' => true,
        'cors_origins' => ['*'] // Adjust for production
    ]
];