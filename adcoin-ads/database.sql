-- ADCOIN Ads Database Schema
-- MySQL 5.7+

-- Create database (run this first)
CREATE DATABASE IF NOT EXISTS adcoin_ads CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE adcoin_ads;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_address VARCHAR(44) UNIQUE NOT NULL,
    slot_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_wallet (wallet_address),
    INDEX idx_slot (slot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ad slots table (100 slots)
CREATE TABLE IF NOT EXISTS ad_slots (
    id INT PRIMARY KEY,
    owner_wallet VARCHAR(44) NULL,
    image_url VARCHAR(500) NULL,
    text VARCHAR(255) NULL,
    link_url VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_verified_at TIMESTAMP NULL,
    INDEX idx_owner (owner_wallet)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initialize 100 empty slots
INSERT INTO ad_slots (id, link_url) 
SELECT n, ''
FROM (
    SELECT 1 + a.N + b.N * 10 AS n
    FROM 
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
    WHERE 1 + a.N + b.N * 10 <= 100
) numbers
ON DUPLICATE KEY UPDATE id = id;

-- Rate limiting table (optional - for spam protection)
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    request_count INT DEFAULT 0,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_endpoint (ip_address, endpoint),
    INDEX idx_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clean up old rate limit entries (run this periodically)
DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR);