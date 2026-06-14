<?php
/**
 * Complete Database Migrations
 */

namespace Exqpay\Database;

use Exqpay\Core\Database;

class Migrations
{
    /**
     * Run all migrations
     */
    public static function runAll(): void
    {
        self::createUsersTable();
        self::createWalletsTable();
        self::createLedgerTable();
        self::createDepositsTable();
        self::createWithdrawalsTable();
        self::createConversionsTable();
        self::createSessionsTable();
        self::createAuditTable();
    }

    /**
     * Create users table
     */
    private static function createUsersTable(): void
    {
        $pdo = Database::connection();
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Create wallets table
     */
    private static function createWalletsTable(): void
    {
        $pdo = Database::connection();
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Create ledger transactions table (immutable)
     */
    private static function createLedgerTable(): void
    {
        $pdo = Database::connection();
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
                status ENUM('pending', 'confirmed', 'failed', 'reversed') DEFAULT 'pending',
                metadata JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_currency (currency),
                INDEX idx_created_at (created_at),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Create deposits table
     */
    private static function createDepositsTable(): void
    {
        $pdo = Database::connection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS deposits (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                deposit_id VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(50) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                amount DECIMAL(38, 18) NOT NULL,
                tx_hash VARCHAR(255),
                confirmations INT DEFAULT 0,
                status ENUM('pending', 'confirmed', 'failed', 'credited') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_deposit_id (deposit_id),
                INDEX idx_tx_hash (tx_hash),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Create deposit addresses table
     */
    private static function createDepositAddressesTable(): void
    {
        $pdo = Database::connection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS deposit_addresses (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                address_id VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(50) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                address VARCHAR(255) NOT NULL,
                qr_code_data LONGTEXT,
                status ENUM('pending', 'active', 'used') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_address_id (address_id),
                INDEX idx_address (address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Create withdrawals table
     */
    private static function createWithdrawalsTable(): void
    {
        $pdo = Database::connection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS withdrawals (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                withdrawal_id VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(50) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                amount DECIMAL(38, 18) NOT NULL,
                bank_account LONGTEXT,
                status ENUM('pending', 'approved', 'processing', 'completed', 'failed', 'rejected') DEFAULT 'pending',
                idempotency_key VARCHAR(255) NOT NULL UNIQUE,
                approved_by VARCHAR(50),
                approved_at TIMESTAMP NULL,
                rejected_by VARCHAR(50),
                rejected_at TIMESTAMP NULL,
                rejected_reason TEXT,
                tx_hash VARCHAR(255),
                completed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_withdrawal_id (withdrawal_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Create conversions table
     */
    private static function createConversionsTable(): void
    {
        $pdo = Database::connection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS conversions (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                conversion_id VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(50) NOT NULL,
                from_currency VARCHAR(10) NOT NULL,
                to_currency VARCHAR(10) NOT NULL,
                from_amount DECIMAL(38, 18) NOT NULL,
                to_amount DECIMAL(38, 18) NOT NULL,
                rate DECIMAL(38, 18) NOT NULL,
                status ENUM('pending', 'confirmed', 'failed') DEFAULT 'confirmed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_conversion_id (conversion_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Create sessions table
     */
    private static function createSessionsTable(): void
    {
        $pdo = Database::connection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_sessions (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                session_id VARCHAR(255) NOT NULL UNIQUE,
                user_id VARCHAR(50) NOT NULL,
                token VARCHAR(255),
                ip_address VARCHAR(45),
                user_agent TEXT,
                expires_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_session_id (session_id),
                INDEX idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Create audit log table (immutable)
     */
    private static function createAuditTable(): void
    {
        $pdo = Database::connection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                audit_id VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(50),
                action VARCHAR(255) NOT NULL,
                entity_type VARCHAR(100),
                entity_id VARCHAR(100),
                changes JSON,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
