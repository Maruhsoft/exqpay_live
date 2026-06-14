<?php
/**
 * Enhanced Ledger Service with Financial Transactions
 */

namespace Exqpay\Services;

use Exqpay\Core\BaseService;
use Exqpay\Core\Exception\ExqpayException;

class LedgerService extends BaseService
{
    // Transaction types
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_CONVERSION = 'conversion';
    public const TYPE_GIFT_CARD = 'gift_card';
    public const TYPE_FEE = 'fee';
    public const TYPE_REFUND = 'refund';

    // Status values
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';

    private WalletService $walletService;

    public function __construct()
    {
        parent::__construct();
        $this->walletService = new WalletService();
    }

    /**
     * Record transaction (append-only)
     */
    public function record(
        string $userId,
        string $currency,
        string $amount,
        string $type,
        string $idempotencyKey,
        array $metadata = []
    ): array {
        // Check for idempotency
        $existing = $this->getByIdempotencyKey($idempotencyKey);
        if ($existing) {
            $this->log('Duplicate transaction detected', ['idempotency_key' => $idempotencyKey]);
            return $existing;
        }

        $transactionId = $this->generateId('txn');
        $direction = in_array($type, ['deposit', 'refund']) ? 'debit' : 'credit';

        $this->db->beginTransaction();

        try {
            // Insert into immutable ledger
            $stmt = $this->db->prepare(
                'INSERT INTO ledger_transactions (transaction_id, user_id, currency, amount, direction, transaction_type, idempotency_key, status, metadata, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $transactionId,
                $userId,
                $currency,
                $amount,
                $direction,
                $type,
                $idempotencyKey,
                self::STATUS_PENDING,
                json_encode($metadata),
            ]);

            // Update wallet
            if ($direction === 'debit') {
                $this->walletService->updateBalance($userId, $currency, $amount, 'available');
            } else {
                $pendingAmount = '-' . $amount;
                $this->walletService->updateBalance($userId, $currency, $pendingAmount, 'available');
            }

            $this->db->commit();
            $this->audit('TRANSACTION_RECORDED', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
                'currency' => $currency,
                'amount' => $amount,
                'type' => $type,
            ]);

            return $this->getById($transactionId);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->log('Transaction recording failed', ['error' => $e->getMessage()], 'error');
            throw $e;
        }
    }

    /**
     * Get transaction by ID
     */
    public function getById(string $transactionId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ledger_transactions WHERE transaction_id = ?'
        );
        $stmt->execute([$transactionId]);
        return $stmt->fetch();
    }

    /**
     * Get transaction by idempotency key
     */
    public function getByIdempotencyKey(string $key): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ledger_transactions WHERE idempotency_key = ?'
        );
        $stmt->execute([$key]);
        return $stmt->fetch();
    }

    /**
     * Confirm transaction
     */
    public function confirm(string $transactionId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ledger_transactions SET status = ?, updated_at = NOW() WHERE transaction_id = ?'
        );
        $stmt->execute([self::STATUS_CONFIRMED, $transactionId]);
        $this->audit('TRANSACTION_CONFIRMED', ['transaction_id' => $transactionId]);
    }

    /**
     * Fail transaction
     */
    public function fail(string $transactionId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ledger_transactions SET status = ?, updated_at = NOW() WHERE transaction_id = ?'
        );
        $stmt->execute([self::STATUS_FAILED, $transactionId]);
        $this->audit('TRANSACTION_FAILED', ['transaction_id' => $transactionId]);
    }

    /**
     * Reverse transaction
     */
    public function reverse(string $transactionId, string $reason = ''): void
    {
        $txn = $this->getById($transactionId);

        if (!$txn) {
            throw new ExqpayException('Transaction not found', 0, 404);
        }

        $this->db->beginTransaction();

        try {
            // Mark original as reversed
            $stmt = $this->db->prepare(
                'UPDATE ledger_transactions SET status = ?, updated_at = NOW() WHERE transaction_id = ?'
            );
            $stmt->execute([self::STATUS_REVERSED, $transactionId]);

            // Create reversal transaction
            $reversalId = $this->generateId('rev');
            $reversalAmount = $txn['direction'] === 'debit' ? '-' . $txn['amount'] : $txn['amount'];

            $stmt = $this->db->prepare(
                'INSERT INTO ledger_transactions (transaction_id, user_id, currency, amount, direction, transaction_type, idempotency_key, status, metadata, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $reversalId,
                $txn['user_id'],
                $txn['currency'],
                abs($reversalAmount),
                $txn['direction'] === 'debit' ? 'credit' : 'debit',
                'reversal',
                'rev_' . $transactionId . '_' . time(),
                self::STATUS_CONFIRMED,
                json_encode(['reason' => $reason, 'original_transaction' => $transactionId]),
            ]);

            $this->db->commit();
            $this->audit('TRANSACTION_REVERSED', [
                'transaction_id' => $transactionId,
                'reversal_id' => $reversalId,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get transaction history
     */
    public function getHistory(string $userId, string $currency = null, int $limit = 50): array
    {
        $query = 'SELECT * FROM ledger_transactions WHERE user_id = ?';
        $params = [$userId];

        if ($currency) {
            $query .= ' AND currency = ?';
            $params[] = $currency;
        }

        $query .= ' ORDER BY created_at DESC LIMIT ?';
        $params[] = $limit;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
