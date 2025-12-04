<?php

/**
 * Migration: IMAP Accounts Table
 * 
 * Speichert IMAP-Account-Konfigurationen für E-Mail-Polling
 */

return [
    'up' => "
        CREATE TABLE imap_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            
            -- IMAP Connection Details
            email VARCHAR(255) NOT NULL,
            imap_host VARCHAR(255) NOT NULL,
            imap_port INT NOT NULL DEFAULT 993,
            imap_encryption ENUM('ssl', 'tls', 'none') NOT NULL DEFAULT 'ssl',
            imap_username VARCHAR(255) NOT NULL,
            imap_password TEXT NOT NULL,  -- Verschlüsselt via EncryptionService
            
            -- Status & Metadata
            is_active BOOLEAN DEFAULT TRUE,
            last_sync_at DATETIME NULL,
            last_error TEXT NULL,
            sync_count INT DEFAULT 0,
            
            -- Timestamps
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_user_id (user_id),
            INDEX idx_email (email),
            INDEX idx_is_active (is_active),
            INDEX idx_last_sync (last_sync_at),
            
            -- Foreign Key
            CONSTRAINT fk_imap_accounts_user FOREIGN KEY (user_id) 
                REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    
    'down' => "DROP TABLE IF EXISTS imap_accounts;"
];
