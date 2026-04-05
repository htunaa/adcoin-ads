# ADCOIN Ads - Technical Specification

## Project Overview
- **Project Name**: ADCOIN Ads
- **Type**: Full-stack crypto-native advertising platform
- **Core Functionality**: Token-gated banner ad slots for Solana ADCOIN holders
- **Target Users**: ADCOIN token holders who want advertising visibility

## Technical Stack
- **Backend**: PHP (no framework, clean structured code)
- **Database**: MySQL
- **Frontend**: HTML + TailwindCSS + Vanilla JS
- **Blockchain**: Solana (via RPC API)
- **Deployment**: Shared hosting (Hostinger compatible)

## Configuration
- **Token Minimum Balance**: 10,000,000 tokens (1% of 1B total supply)
- **Total Slots**: 100
- **Slot Limit**: 1 per wallet
- **Token Address**: Configurable in config.php

## Database Schema

### users
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_address VARCHAR(44) UNIQUE NOT NULL,
    slot_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### ad_slots
```sql
CREATE TABLE ad_slots (
    id INT PRIMARY KEY,
    owner_wallet VARCHAR(44) NULL,
    image_url VARCHAR(500) NULL,
    text VARCHAR(255) NULL,
    link_url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_verified_at TIMESTAMP NULL
);
```

## API Endpoints

### connect_wallet.php
- **Method**: POST
- **Input**: wallet_address
- **Output**: JSON with eligibility status and user data

### check_eligibility.php
- **Method**: POST
- **Input**: wallet_address
- **Output**: JSON with token balance and eligibility boolean

### create_ad.php
- **Method**: POST
- **Input**: wallet_address, link_url, image (file), text
- **Output**: JSON with success/failure and slot assignment
- **Validation**: Rate limiting, file validation

### update_ad.php
- **Method**: POST
- **Input**: wallet_address, link_url, image (file), text
- **Output**: JSON with success/failure
- **Validation**: Owner check

### delete_ad.php
- **Method**: POST
- **Input**: wallet_address
- **Output**: JSON with success/failure

### get_slots.php
- **Method**: GET
- **Output**: JSON array of all 100 slots (with owner info)

## Core Logic Flow

1. **Wallet Connection**:
   - User clicks "Connect Wallet" → Phantom popup
   - On success, JS sends wallet address to backend
   - Backend checks token balance via Solana RPC
   - If balance >= 10M tokens → Eligible
   - If no slot assigned → Assign random available slot
   - If slot exists → Allow edit/delete

2. **Slot Management**:
   - User can upload image OR text (or both)
   - link_url is required
   - File upload: /uploads/ directory, max 2MB, jpg/png/gif/webp

3. **Slot Verification (Cron)**:
   - Run every 5-10 minutes
   - For each occupied slot, check token balance
   - If balance < 10M → Clear slot, remove user association

## UI Layout

### Header
- Logo: "ADCOIN" with coin icon
- Connect Wallet button (right side)

### Wallet Status Panel (below header, when connected)
- Wallet address (truncated)
- Token balance (formatted)
- Eligibility badge (green/red)
- Ad management button (if eligible)

### Banner Grid
- 100 slots in responsive grid
- Slot size: varies by screen (mobile 1 col, tablet 2-3 cols, desktop 4-5 cols)
- Each slot shows image OR text
- Clickable → opens link_url in new tab

### Slot Shuffle
- On page load, shuffle slot display order (JS)
- Actual slot IDs remain consistent

### Ad Management Modal
- Form with: Image upload, Text input, URL input
- Submit/Cancel buttons
- Delete button (if slot exists)

## File Structure
```
/adcoin-ads/
├── index.php              # Main page
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
│   └── solana.php
├── cron/
│   └── verify_slots.php
├── uploads/               # Uploaded images (chmod 755)
├── css/
│   └── style.css         # Custom styles if needed
├── js/
│   └── app.js            # Frontend JS
└── SPEC.md
```

## Security
- Input sanitization on all endpoints
- File upload validation (type, size)
- Rate limiting: max 10 requests per minute per IP
- CORS headers for API
- Wallet address validation (base58 format)

## Style Guidelines
- Dark mode default
- Primary color: #FF6B00 (orange - degen feel)
- Secondary: #1a1a2e (dark background)
- Accent: #00FF88 (green for success states)
- Font: system-ui, monospace for addresses
- Subtle glow effects on buttons
- Grid background pattern