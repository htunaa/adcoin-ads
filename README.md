# 🪙 ADCOIN Ads - Token-Gated Advertising Platform

A crypto-native advertising platform built on Solana blockchain. Banner slots are token-gated — no payments required, just hold ADCOIN tokens to rent your spot!

![License](https://img.shields.io/badge/License-MIT-green.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-purple.svg)
![Solana](https://img.shields.io/badge/Blockchain-Solana-blue.svg)

---

## ✨ Features

### 🎯 Main Advertising Site
- **100 Token-Gated Banner Slots** - Own a spot by holding 10M ADCOIN tokens
- **Phantom Wallet Integration** - Connect with your Solana wallet
- **Automatic Verification** - Cron job verifies token holdings every 10 minutes
- **Real-time Stats** - Live slot occupancy tracking

### 🎰 Reward System (Spin & Win)
- **Daily Lucky Draw** - 12 free spins every day
- **Win Real SOL** - From 0.00001 to 0.001 SOL per spin

| Number Range | Reward |
|-------------|--------|
| 9000-9899 | 0.00001 SOL |
| 9900-9989 | 0.00005 SOL |
| 9990-9999 | 0.0001 SOL |
| 10000 | 🏆 JACKPOT 0.001 SOL |

- **ADCOIN Holder Boost** - +10% bonus for 10M+ token holders
- **Near Miss Alerts** - "So close!" messages when hitting 9900+
- **Referral System** - Earn 10% commission from friends' winnings

### 🔒 Security
- IP-based rate limiting (max 3 wallets per IP)
- Device fingerprint tracking
- Cloudflare Turnstile CAPTCHA support

---

## 🚀 Demo

- **Main Site**: [https://adcoin.one](https://adcoin.one)
- **Rewards**: [https://adcoin.one/reward](https://adcoin.one/reward)

---

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- Solana RPC endpoint (mainnet or devnet)

---

## 🛠️ Installation

### 1. Upload Files
Upload all files to your web server's `public_html` directory.

### 2. Create Database
```bash
mysql -u username -p database_name < reward_database.sql

Or import via phpMyAdmin:

Import database.sql for main site tables
Import reward_database.sql for reward system tables
3. Configure

Copy includes/config.example.php to includes/config.php and fill in your values:

<?php
return [
    'db' => [
        'host' => 'localhost',
        'user' => 'your_db_user',
        'pass' => 'your_db_password',
        'name' => 'your_database_name'
    ],
    'solana' => [
        'rpc_url' => 'https://api.mainnet-beta.solana.com',
        'token_mint' => 'YOUR_ADCOIN_TOKEN_MINT_ADDRESS',
        'min_balance' => 10000000
    ],
    'captcha' => [
        'site_key' => 'YOUR_TURNSTILE_KEY',
        'site_secret' => 'YOUR_TURNSTILE_SECRET'
    ]
];
Note: Get your Cloudflare Turnstile keys from Cloudflare Dashboard

4. Set Up Cron Jobs (Recommended)

# Daily reset at midnight - resets spins to 12
0 0 * * * curl -s https://adcoin.one/cron/daily_reset.php

# Verify slots every 10 minutes - checks token holdings
*/10 * * * * curl -s https://adcoin.one/cron/verify_slots.php
🔗 URL Structure

Route	Description
/	Main ad site
/reward	Spin & Win game
/api/*	API endpoints
📁 File Structure

adcoin-ads/
├── index.php              # Main site entry point
├── reward.php             # Rewards game page
├── .htaccess              # URL rewriting rules
├── api/                   # API endpoints
│   ├── connect_wallet.php
│   ├── reward_login.php
│   ├── reward_play.php
│   └── ...
├── includes/              # Core configuration
│   ├── config.php         # Your secrets (NOT in git)
│   ├── config.example.php # Template
│   ├── solana.php         # Solana RPC integration
│   └── db.php
├── cron/                  # Scheduled tasks
│   ├── daily_reset.php    # Reset daily spins
│   └── verify_slots.php   # Verify token holdings
├── js/                    # Frontend JavaScript
├── css/                   # Stylesheets
└── uploads/               # User uploads
🔐 Security Notes

Never commit includes/config.php - It contains your database credentials and API keys
Use .gitignore - Already configured to exclude sensitive files
Enable Cloudflare - For DDoS protection and WAF
Use environment variables - In production, consider using env vars instead of config files
🧰 Tech Stack

Category	Technology
Backend	PHP 8 (vanilla, no framework)
Database	MySQL with PDO
Frontend	HTML, TailwindCSS
Blockchain	Solana (Phantom Wallet)
Hosting	Compatible with shared hosting (Hostinger, etc.)
🤝 Contributing

Fork the repository
Create your feature branch (git checkout -b feature/amazing)
Commit your changes (git commit -m 'Add amazing feature')
Push to the branch (git push origin feature/amazing)
Open a Pull Request
📄 License

This project is licensed under the MIT License.

🙏 Acknowledgments

Solana - Blockchain infrastructure
Phantom Wallet - Solana wallet integration
Cloudflare Turnstile - CAPTCHA solution
TailwindCSS - UI styling
Built with 💜 for the Solana community

