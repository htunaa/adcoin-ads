# ADCOIN Ads - Deployment Guide

## Prerequisites

- **Hosting**: Shared hosting with PHP 8.0+ and MySQL 5.7+ (Hostinger, SiteGround, etc.)
- **Solana RPC**: Helius, QuickNode, or public RPC endpoint
- **Domain**: Point your domain to the hosting

---

## Step 1: Upload Files

1. Upload all files to your hosting (typically `public_html` or `www` folder)
2. Directory structure:
   ```
   /public_html/
   ├── index.php
   ├── api/
   │   ├── connect_wallet.php
   │   ├── check_eligibility.php
   │   ├── create_ad.php
   │   ├── update_ad.php
   │   ├── delete_ad.php
   │   └── get_slots.php
   ├── includes/
   │   ├── config.php
   │   ├── db.php
   │   ├── solana.php
   │   └── helpers.php
   ├── cron/
   │   └── verify_slots.php
   ├── uploads/        (chmod 755)
   ├── js/
   │   └── app.js
   └── SPEC.md
   ```

---

## Step 2: Configure Database

1. **Create Database** in hosting control panel (phpMyAdmin)
2. Import `database.sql`:
   ```bash
   mysql -u username -p database_name < database.sql
   ```
   Or paste contents in phpMyAdmin SQL tab

3. **Edit config.php** with your credentials:
   ```php
   'db' => [
       'host' => 'localhost',
       'name' => 'your_database_name',
       'user' => 'your_database_user',
       'pass' => 'your_database_password',
       'charset' => 'utf8mb4'
   ]
   ```

---

## Step 3: Configure Solana RPC

1. Get an RPC endpoint:
   - **Helius**: https://helius.xyz (free tier available)
   - **QuickNode**: https://quicknode.com
   - **Public**: https://api.mainnet-beta.solana.com (rate limited)

2. Update `includes/config.php`:
   ```php
   'solana' => [
       'rpc_url' => 'https://your-rpc-endpoint.com',
       'token_mint' => 'YourADCOINTokenMintAddress',
       'min_balance' => 10000000,
       'api_key' => '' // if needed
   ]
   ```

---

## Step 4: Set Upload Permissions

1. SSH or FTP to your server
2. Set uploads directory:
   ```bash
   chmod 755 uploads
   ```
   Or in FileZilla: Right-click → Permissions → 755

---

## Step 5: Configure Cron Job

### Option A: Hostinger Cron (Recommended)

1. Login to Hostinger → Advanced → Cron Jobs
2. Add new cron job:
   - **Command**: `curl -s https://your-domain.com/cron/verify_slots.php`
   - **Interval**: Every 10 minutes

### Option B: Standard Cron

```bash
*/10 * * * * curl -s https://your-domain.com/cron/verify_slots.php
```

### Verify Cron Works

```bash
curl https://your-domain.com/cron/verify_slots.php
```

Should output verification logs.

---

## Step 6: Test the Platform

1. Visit `https://your-domain.com`
2. Click "Connect Wallet" - Phantom popup should appear
3. After connecting, you'll see:
   - Your wallet address
   - Token balance
   - Eligibility status

### If Eligible:
- Click "Create Ad" to claim a slot
- Upload image (optional)
- Enter text (optional)
- Enter link URL (required)
- Submit

### If Not Eligible:
- You'll need 10,000,000 ADCOIN tokens

---

## Configuration Options

### Change Minimum Balance

In `includes/config.php`:
```php
'min_balance' => 10000000, // 10 million tokens
```

### Change Slot Count

In `includes/config.php`:
```php
'app' => [
    'total_slots' => 100
]
```

And update database:
```sql
DELETE FROM ad_slots;
INSERT INTO ad_slots (id, link_url) 
SELECT n, '' FROM (SELECT 1 + a.N + b.N * 10 AS n FROM ...) numbers;
```

### File Upload Settings

```php
'app' => [
    'max_upload_size' => 2097152,    // 2MB in bytes
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
]
```

---

## Troubleshooting

### "Database connection failed"
- Check credentials in `config.php`
- Ensure database exists
- Check MySQL user has permissions

### "Failed to check token balance"
- Verify RPC URL in `config.php`
- Check your API key if using Helius/QuickNode
- Try different RPC endpoint

### Images not uploading
- Check `uploads/` directory permissions (755)
- Verify `upload_dir` path in `config.php`

### Cron not working
- Verify cron URL works in browser
- Check cron logs in hosting panel

---

## Security Notes

1. **Keep config.php private** - it's in .gitignore if you use git
2. **Use HTTPS** - required for Phantom wallet
3. **Rate limiting** - included by default (10 req/min)
4. **Input sanitization** - all inputs validated in PHP

---

## Project Structure

```
adcoin-ads/
├── index.php           # Main page
├── api/                # API endpoints
│   ├── connect_wallet.php
│   ├── check_eligibility.php
│   ├── create_ad.php
│   ├── update_ad.php
│   ├── delete_ad.php
│   └── get_slots.php
├── includes/           # Core PHP files
│   ├── config.php     # Configuration
│   ├── db.php         # Database
│   ├── solana.php     # Solana RPC
│   └── helpers.php    # Utilities
├── cron/              # Cron jobs
│   └── verify_slots.php
├── uploads/           # Uploaded images
├── js/                # Frontend JS
│   └── app.js
├── database.sql       # Database schema
└── SPEC.md            # Full specification
```

---

## Support

For issues:
1. Check PHP error logs
2. Verify all config settings
3. Test API endpoints directly
4. Check cron job output