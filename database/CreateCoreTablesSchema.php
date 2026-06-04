<?php
/**
 * Database Migration - Create Core Tables
 */

namespace Exqpay\Database;

use Exqpay\Core\Database;

class CreateCoreTablesSchema
{
    public static function up(): void
    {
        $pdo = Database::connection();

        // Users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                user_id VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                phone VARCHAR(20),
                country VARCHAR(100),
                role ENUM('customer', 'support', 'admin') DEFAULT 'customer',
                kyc_status ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
                account_status ENUM('active', 'frozen', 'suspended') DEFAULT 'active',
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_email (email),
                INDEX idx_kyc_status (kyc_status),
                INDEX idx_account_status (account_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // User wallets
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_wallets (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                user_id VARCHAR(50) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                available_balance DECIMAL(38, 18) DEFAULT 0,
                pending_balance DECIMAL(38, 18) DEFAULT 0,
                locked_balance DECIMAL(38, 18) DEFAULT 0,
                total_balance DECIMAL(38, 18) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_currency (user_id, currency),
                INDEX idx_user_id (user_id),
                INDEX idx_currency (currency)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Ledger transactions (immutable)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ledger_transactions (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                transaction_id VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(50) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                amount DECIMAL(38, 18) NOT NULL,
                direction ENUM('debit', 'credit') NOT NULL,
                transaction_type VARCHAR(50) NOT NULL,
                idempotency_key VARCHAR(255) NOT NULL UNIQUE,
                status ENUM('pending', 'confirmed', 'reversed', 'failed') DEFAULT 'pending',
                metadata JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_currency (currency),
                INDEX idx_created_at (created_at),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Deposit addresses
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS deposit_addresses (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                address_id VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(50) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                address VARCHAR(255) NOT NULL,
                qr_code_data LONGTEXT,
                status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
                confirmations_required INT DEFAULT 0,
                confirmations_received INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_address (address),
                INDEX idx_currency (currency),
                UNIQUE KEY unique_user_currency_active (user_id, currency, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Withdrawal requests
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS withdrawal_requests (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                withdrawal_id VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(50) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                amount DECIMAL(38, 18) NOT NULL,
                fiat_currency VARCHAR(10) NOT NULL,
                fiat_amount DECIMAL(19, 2) NOT NULL,
                exchange_rate DECIMAL(19, 8) NOT NULL,
                fee DECIMAL(19, 2) NOT NULL,
                bank_account_id INT UNSIGNED NOT NULL,
                status ENUM('pending', 'approved', 'processing', 'completed', 'rejected', 'cancelled') DEFAULT 'pending',
                rejection_reason TEXT,
                idempotency_key VARCHAR(255) NOT NULL UNIQUE,
                metadata JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_withdrawal_id (withdrawal_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Bank accounts (user verified bank details)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS bank_accounts (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                bank_account_id VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(50) NOT NULL,
                bank_name VARCHAR(255) NOT NULL,
                account_holder_name VARCHAR(255) NOT NULL,
                account_number VARCHAR(50) NOT NULL,
                routing_number VARCHAR(50),
                iban VARCHAR(50),
                swift_code VARCHAR(20),
                country VARCHAR(100) NOT NULL,
                status ENUM('pending', 'verified', 'rejected', 'inactive') DEFAULT 'pending',
                is_primary BOOLEAN DEFAULT FALSE,
                verified_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Gift cards
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS gift_cards (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                gift_card_id VARCHAR(50) NOT NULL UNIQUE,
                code VARCHAR(100) NOT NULL UNIQUE,
                provider VARCHAR(100) NOT NULL,
                amount_usd DECIMAL(19, 2) NOT NULL,
                status ENUM('available', 'sold', 'delivered', 'redeemed', 'expired', 'cancelled') DEFAULT 'available',
                current_owner_id VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                sold_at TIMESTAMP NULL,
                delivered_at TIMESTAMP NULL,
                redeemed_at TIMESTAMP NULL,
                INDEX idx_gift_card_id (gift_card_id),
                INDEX idx_status (status),
                INDEX idx_owner_id (current_owner_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Gift card transactions
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS gift_card_transactions (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                transaction_id VARCHAR(50) NOT NULL UNIQUE,
                gift_card_id VARCHAR(50) NOT NULL,
                user_id VARCHAR(50) NOT NULL,
                transaction_type ENUM('purchase', 'sale', 'delivery', 'redemption') NOT NULL,
                amount DECIMAL(19, 2) NOT NULL,
                idempotency_key VARCHAR(255) NOT NULL UNIQUE,
                metadata JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_gift_card_id (gift_card_id),
                INDEX idx_transaction_type (transaction_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Exchange rates (for historical audit)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS exchange_rates (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                from_currency VARCHAR(10) NOT NULL,
                to_currency VARCHAR(10) NOT NULL,
                rate DECIMAL(19, 8) NOT NULL,
                source VARCHAR(100),
                locked_for_transaction VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_currencies (from_currency, to_currency),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        echo "✓ Core tables created successfully\n";
    }

    public static function down(): void
    {
        $pdo = Database::connection();
        $pdo->exec("DROP TABLE IF EXISTS gift_card_transactions");
        $pdo->exec("DROP TABLE IF EXISTS gift_cards");
        $pdo->exec("DROP TABLE IF EXISTS bank_accounts");
        $pdo->exec("DROP TABLE IF EXISTS withdrawal_requests");
        $pdo->exec("DROP TABLE IF EXISTS deposit_addresses");
        $pdo->exec("DROP TABLE IF EXISTS ledger_transactions");
        $pdo->exec("DROP TABLE IF EXISTS user_wallets");
        $pdo->exec("DROP TABLE IF EXISTS users");
        $pdo->exec("DROP TABLE IF EXISTS exchange_rates");
        echo "✓ Core tables dropped\n";
    }
}
