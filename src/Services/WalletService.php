<?php
/**
 * Wallet Service
 */

namespace Exqpay\Services;

use Exqpay\Core\BaseService;
use Exqpay\Core\Exception\ExqpayException;

class WalletService extends BaseService
{
    /**
     * Get or create wallet
     */
    public function getOrCreate(string $userId, string $currency): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM user_wallets WHERE user_id = ? AND currency = ?'
        );
        $stmt->execute([$userId, $currency]);
        $wallet = $stmt->fetch();

        if (!$wallet) {
            return $this->create($userId, $currency);
        }

        return $wallet;
    }

    /**
     * Create new wallet
     */
    private function create(string $userId, string $currency): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_wallets (user_id, currency, available_balance, pending_balance, locked_balance, total_balance)
             VALUES (?, ?, 0, 0, 0, 0)'
        );
        $stmt->execute([$userId, $currency]);

        return $this->getOrCreate($userId, $currency);
    }

    /**
     * Get balance
     */
    public function getBalance(string $userId, string $currency = null): array
    {
        if ($currency) {
            $stmt = $this->db->prepare(
                'SELECT * FROM user_wallets WHERE user_id = ? AND currency = ?'
            );
            $stmt->execute([$userId, $currency]);
            return $stmt->fetch() ?: [];
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM user_wallets WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Update balance
     */
    public function updateBalance(
        string $userId,
        string $currency,
        string $amount,
        string $type = 'available'
    ): void {
        $wallet = $this->getOrCreate($userId, $currency);

        $field = match ($type) {
            'pending' => 'pending_balance',
            'locked' => 'locked_balance',
            default => 'available_balance',
        };

        $newAmount = bcadd($wallet[$field], $amount, 18);
        $totalBalance = bcadd(
            bcadd($wallet['available_balance'], $wallet['pending_balance'], 18),
            $wallet['locked_balance'],
            18
        );

        $stmt = $this->db->prepare(
            "UPDATE user_wallets SET {$field} = ?, total_balance = ?, updated_at = NOW()
             WHERE user_id = ? AND currency = ?"
        );
        $stmt->execute([$newAmount, $totalBalance, $userId, $currency]);
    }

    /**
     * Lock funds
     */
    public function lock(string $userId, string $currency, string $amount): void
    {
        $wallet = $this->getOrCreate($userId, $currency);

        if (bccomp($wallet['available_balance'], $amount, 18) < 0) {
            throw new ExqpayException('Insufficient balance', 0, 400);
        }

        $this->db->beginTransaction();

        try {
            // Move from available to locked
            $this->updateBalance($userId, $currency, '-' . $amount, 'available');
            $this->updateBalance($userId, $currency, $amount, 'locked');

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Unlock funds
     */
    public function unlock(string $userId, string $currency, string $amount): void
    {
        $wallet = $this->getOrCreate($userId, $currency);

        if (bccomp($wallet['locked_balance'], $amount, 18) < 0) {
            throw new ExqpayException('Cannot unlock more than locked balance', 0, 400);
        }

        $this->db->beginTransaction();

        try {
            // Move from locked to available
            $this->updateBalance($userId, $currency, '-' . $amount, 'locked');
            $this->updateBalance($userId, $currency, $amount, 'available');

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
