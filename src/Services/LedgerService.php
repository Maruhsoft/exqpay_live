<?php
/**
 * Double-Entry Ledger System
 * Core financial accounting ensuring financial correctness
 */

namespace Exqpay\Services;

use Exqpay\Core\Database;
use Exqpay\Core\Logger;

class LedgerService
{
    // Transaction types
    public const TRANSACTION_TYPE_DEPOSIT = 'deposit';
    public const TRANSACTION_TYPE_WITHDRAWAL = 'withdrawal';
    public const TRANSACTION_TYPE_CONVERSION = 'conversion';
    public const TRANSACTION_TYPE_GIFT_CARD_PURCHASE = 'gift_card_purchase';
    public const TRANSACTION_TYPE_FEE = 'fee';
    public const TRANSACTION_TYPE_REFUND = 'refund';

    // Status values
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_FAILED = 'failed';

    /**
     * Record a debit transaction (money in)
     */
    public static function recordDebit(
        string $userId,
        string $currency,
        string $amount,
        string $transactionType,
        string $idempotencyKey,
        array $metadata = []
    ): array {
        return self::recordTransaction($userId, $currency, $amount, 'debit', $transactionType, $idempotencyKey, $metadata);
    }

    /**
     * Record a credit transaction (money out)
     */
    public static function recordCredit(
        string $userId,
        string $currency,
        string $amount,
        string $transactionType,
        string $idempotencyKey,
        array $metadata = []
    ): array {
        return self::recordTransaction($userId, $currency, $amount, 'credit', $transactionType, $idempotencyKey, $metadata);
    }

    /**
     * Core transaction recording with idempotency
     */
    private static function recordTransaction(
        string $userId,
        string $currency,
        string $amount,
        string $direction,
        string $transactionType,
        string $idempotencyKey,
        array $metadata = []
    ): array {
        $pdo = Database::connection();

        try {
            Database::beginTransaction();

            // Check for duplicate (idempotency)
            $existing = $pdo->prepare('SELECT id, transaction_id FROM ledger_transactions WHERE idempotency_key = ?');
            $existing->execute([$idempotencyKey]);
            $existingRecord = $existing->fetch();

            if ($existingRecord) {
                Database::commit();
                Logger::warning('Duplicate transaction', ['idempotency_key' => $idempotencyKey]);
                return self::getTransaction($existingRecord['transaction_id']);
            }

            $transactionId = self::generateTransactionId();
            self::ensureWallet($userId, $currency);

            // Insert immutable ledger entry
            $stmt = $pdo->prepare(
                'INSERT INTO ledger_transactions (transaction_id, user_id, currency, amount, direction, transaction_type, idempotency_key, status, metadata, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                $transactionId, $userId, $currency, $amount, $direction,
                $transactionType, $idempotencyKey, self::STATUS_PENDING, json_encode($metadata),
            ]);

            // Update balance
            if ($direction === 'debit') {
                self::updateBalance($userId, $currency, $amount, 'add');
            } else {
                self::updateBalance($userId, $currency, $amount, 'subtract');
            }

            Database::commit();
            Logger::audit('ledger_transaction_recorded', ['transaction_id' => $transactionId, 'user_id' => $userId]);

            return self::getTransaction($transactionId);
        } catch (\Exception $e) {
            Database::rollback();
            Logger::error('Ledger transaction failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get current balance for user
     */
    public static function getBalance(string $userId, string $currency = null): array
    {
        $pdo = Database::connection();

        if ($currency) {
            $stmt = $pdo->prepare('SELECT currency, available_balance, pending_balance, locked_balance, total_balance FROM user_wallets WHERE user_id = ? AND currency = ?');
            $stmt->execute([$userId, strtoupper($currency)]);
            $wallet = $stmt->fetch();
            return $wallet ?: ['currency' => strtoupper($currency), 'available_balance' => '0', 'pending_balance' => '0', 'locked_balance' => '0', 'total_balance' => '0'];
        }

        $stmt = $pdo->prepare('SELECT currency, available_balance, pending_balance, locked_balance, total_balance FROM user_wallets WHERE user_id = ? ORDER BY currency');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Update wallet balance
     */
    private static function updateBalance(string $userId, string $currency, string $amount, string $operation = 'add'): void
    {
        $pdo = Database::connection();
        $currency = strtoupper($currency);

        if ($operation === 'add') {
            $stmt = $pdo->prepare('UPDATE user_wallets SET available_balance = available_balance + ?, total_balance = total_balance + ?, updated_at = NOW() WHERE user_id = ? AND currency = ?');
        } else {
            $stmt = $pdo->prepare('UPDATE user_wallets SET available_balance = available_balance - ?, total_balance = total_balance - ?, updated_at = NOW() WHERE user_id = ? AND currency = ?');
        }

        $stmt->execute([$amount, $amount, $userId, $currency]);
    }

    /**
     * Get transaction details
     */
    public static function getTransaction(string $transactionId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM ledger_transactions WHERE transaction_id = ?');
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            throw new \RuntimeException('Transaction not found: ' . $transactionId);
        }

        if ($transaction['metadata']) {
            $transaction['metadata'] = json_decode($transaction['metadata'], true);
        }

        return $transaction;
    }

    /**
     * Get transaction history
     */
    public static function getTransactionHistory(string $userId, string $currency = null, int $limit = 50, int $offset = 0): array
    {
        $pdo = Database::connection();
        $query = 'SELECT * FROM ledger_transactions WHERE user_id = ?';
        $params = [$userId];

        if ($currency) {
            $query .= ' AND currency = ?';
            $params[] = strtoupper($currency);
        }

        $query .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        foreach ($transactions as &$tx) {
            if ($tx['metadata']) {
                $tx['metadata'] = json_decode($tx['metadata'], true);
            }
        }

        return $transactions;
    }

    /**
     * Ensure wallet exists
     */
    private static function ensureWallet(string $userId, string $currency): void
    {
        $pdo = Database::connection();
        $currency = strtoupper($currency);
        $stmt = $pdo->prepare('SELECT id FROM user_wallets WHERE user_id = ? AND currency = ?');
        $stmt->execute([$userId, $currency]);

        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO user_wallets (user_id, currency, available_balance, pending_balance, locked_balance, total_balance, created_at, updated_at) VALUES (?, ?, "0", "0", "0", "0", NOW(), NOW())');
            $stmt->execute([$userId, $currency]);
        }
    }

    /**
     * Generate unique transaction ID
     */
    private static function generateTransactionId(): string
    {
        return 'TXN_' . date('Ymd') . '_' . strtoupper(bin2hex(random_bytes(8)));
    }
}
