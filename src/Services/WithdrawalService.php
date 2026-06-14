<?php
/**
 * Withdrawal Service
 */

namespace Exqpay\Services;

use Exqpay\Core\BaseService;
use Exqpay\Core\Exception\ExqpayException;

class WithdrawalService extends BaseService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';

    private LedgerService $ledgerService;
    private WalletService $walletService;

    public function __construct()
    {
        parent::__construct();
        $this->ledgerService = new LedgerService();
        $this->walletService = new WalletService();
    }

    /**
     * Request withdrawal
     */
    public function request(
        string $userId,
        string $currency,
        string $amount,
        string $bankAccount,
        string $idempotencyKey
    ): array {
        // Validate
        $wallet = $this->walletService->getBalance($userId, $currency);
        if (!$wallet || bccomp($wallet['available_balance'], $amount, 18) < 0) {
            throw new ExqpayException('Insufficient balance', 0, 400);
        }

        // Check velocity limits
        $this->validateVelocityLimits($userId, $currency, $amount);

        $withdrawalId = $this->generateId('wd');

        $this->db->beginTransaction();

        try {
            // Lock funds
            $this->walletService->lock($userId, $currency, $amount);

            // Create withdrawal request
            $stmt = $this->db->prepare(
                'INSERT INTO withdrawals (withdrawal_id, user_id, currency, amount, bank_account, status, idempotency_key, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $withdrawalId,
                $userId,
                $currency,
                $amount,
                encrypt($bankAccount),
                self::STATUS_PENDING,
                $idempotencyKey,
            ]);

            $this->db->commit();
            $this->audit('WITHDRAWAL_REQUESTED', [
                'withdrawal_id' => $withdrawalId,
                'user_id' => $userId,
                'currency' => $currency,
                'amount' => $amount,
            ]);

            return $this->getWithdrawal($withdrawalId);
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get withdrawal
     */
    public function getWithdrawal(string $withdrawalId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM withdrawals WHERE withdrawal_id = ?'
        );
        $stmt->execute([$withdrawalId]);
        return $stmt->fetch();
    }

    /**
     * Approve withdrawal
     */
    public function approve(string $withdrawalId, string $approvedBy): void
    {
        $stmt = $this->db->prepare(
            'UPDATE withdrawals SET status = ?, approved_by = ?, approved_at = NOW() WHERE withdrawal_id = ?'
        );
        $stmt->execute([self::STATUS_APPROVED, $approvedBy, $withdrawalId]);
        $this->audit('WITHDRAWAL_APPROVED', ['withdrawal_id' => $withdrawalId, 'approved_by' => $approvedBy]);
    }

    /**
     * Reject withdrawal
     */
    public function reject(string $withdrawalId, string $reason, string $rejectedBy): void
    {
        $withdrawal = $this->getWithdrawal($withdrawalId);

        if (!$withdrawal) {
            throw new ExqpayException('Withdrawal not found', 0, 404);
        }

        $this->db->beginTransaction();

        try {
            // Unlock funds
            $this->walletService->unlock($withdrawal['user_id'], $withdrawal['currency'], $withdrawal['amount']);

            // Update status
            $stmt = $this->db->prepare(
                'UPDATE withdrawals SET status = ?, rejected_reason = ?, rejected_by = ?, rejected_at = NOW() WHERE withdrawal_id = ?'
            );
            $stmt->execute([self::STATUS_REJECTED, $reason, $rejectedBy, $withdrawalId]);

            $this->db->commit();
            $this->audit('WITHDRAWAL_REJECTED', [
                'withdrawal_id' => $withdrawalId,
                'reason' => $reason,
                'rejected_by' => $rejectedBy,
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Complete withdrawal
     */
    public function complete(string $withdrawalId, string $txHash): void
    {
        $withdrawal = $this->getWithdrawal($withdrawalId);

        if (!$withdrawal) {
            throw new ExqpayException('Withdrawal not found', 0, 404);
        }

        $this->db->beginTransaction();

        try {
            // Update status
            $stmt = $this->db->prepare(
                'UPDATE withdrawals SET status = ?, tx_hash = ?, completed_at = NOW() WHERE withdrawal_id = ?'
            );
            $stmt->execute([self::STATUS_COMPLETED, $txHash, $withdrawalId]);

            // Record in ledger
            $this->ledgerService->record(
                $withdrawal['user_id'],
                $withdrawal['currency'],
                $withdrawal['amount'],
                LedgerService::TYPE_WITHDRAWAL,
                'wd_' . $withdrawalId . '_completed',
                ['withdrawal_id' => $withdrawalId, 'tx_hash' => $txHash]
            );

            $this->db->commit();
            $this->audit('WITHDRAWAL_COMPLETED', ['withdrawal_id' => $withdrawalId]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Validate velocity limits
     */
    private function validateVelocityLimits(string $userId, string $currency, string $amount): void
    {
        $config = config('withdrawal');
        $minAmount = $config['min_amount'] ?? 100;
        $maxAmount = $config['max_amount'] ?? 50000;
        $dailyLimit = $config['daily_limit'] ?? 100000;

        if (bccomp($amount, $minAmount, 2) < 0) {
            throw new ExqpayException("Minimum withdrawal is {$minAmount}", 0, 400);
        }

        if (bccomp($amount, $maxAmount, 2) > 0) {
            throw new ExqpayException("Maximum withdrawal is {$maxAmount}", 0, 400);
        }

        // Check daily limit
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) as total FROM withdrawals
             WHERE user_id = ? AND currency = ? AND status IN (?, ?, ?)
             AND DATE(created_at) = CURDATE()'
        );
        $stmt->execute([$userId, $currency, self::STATUS_APPROVED, self::STATUS_PROCESSING, self::STATUS_COMPLETED]);
        $result = $stmt->fetch();
        $dailyTotal = $result['total'] ?? 0;

        if (bcadd($dailyTotal, $amount, 2) > $dailyLimit) {
            throw new ExqpayException("Daily withdrawal limit of {$dailyLimit} exceeded", 0, 400);
        }
    }
}
