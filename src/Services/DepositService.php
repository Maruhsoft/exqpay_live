<?php
/**
 * Deposit Service
 */

namespace Exqpay\Services;

use Exqpay\Core\BaseService;
use Exqpay\Core\Exception\ExqpayException;

class DepositService extends BaseService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CREDITED = 'credited';

    private LedgerService $ledgerService;

    public function __construct()
    {
        parent::__construct();
        $this->ledgerService = new LedgerService();
    }

    /**
     * Create deposit address
     */
    public function createAddress(string $userId, string $currency): array
    {
        $addressId = $this->generateId('addr');
        $address = $this->generateDepositAddress($currency);
        $qrCode = $this->generateQrCode($address);

        $stmt = $this->db->prepare(
            'INSERT INTO deposit_addresses (address_id, user_id, currency, address, qr_code_data, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $addressId,
            $userId,
            $currency,
            $address,
            $qrCode,
            self::STATUS_PENDING,
        ]);

        return $this->getAddress($addressId);
    }

    /**
     * Get deposit address
     */
    public function getAddress(string $addressId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM deposit_addresses WHERE address_id = ?'
        );
        $stmt->execute([$addressId]);
        return $stmt->fetch();
    }

    /**
     * Get user deposits
     */
    public function getUserDeposits(string $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Record deposit
     */
    public function recordDeposit(
        string $userId,
        string $currency,
        string $amount,
        string $txHash,
        int $confirmations = 0
    ): array {
        $depositId = $this->generateId('dep');
        $idempotencyKey = hash('sha256', $txHash . $currency . $userId);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO deposits (deposit_id, user_id, currency, amount, tx_hash, confirmations, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $depositId,
                $userId,
                $currency,
                $amount,
                $txHash,
                $confirmations,
                self::STATUS_PENDING,
            ]);

            // Record in ledger
            $this->ledgerService->record(
                $userId,
                $currency,
                $amount,
                LedgerService::TYPE_DEPOSIT,
                $idempotencyKey,
                [
                    'deposit_id' => $depositId,
                    'tx_hash' => $txHash,
                    'confirmations' => $confirmations,
                ]
            );

            $this->db->commit();
            $this->audit('DEPOSIT_RECORDED', [
                'deposit_id' => $depositId,
                'user_id' => $userId,
                'currency' => $currency,
                'amount' => $amount,
                'tx_hash' => $txHash,
            ]);

            return $this->getDeposit($depositId);
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get deposit
     */
    public function getDeposit(string $depositId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM deposits WHERE deposit_id = ?'
        );
        $stmt->execute([$depositId]);
        return $stmt->fetch();
    }

    /**
     * Confirm deposit
     */
    public function confirmDeposit(string $depositId): void
    {
        $deposit = $this->getDeposit($depositId);

        if (!$deposit) {
            throw new ExqpayException('Deposit not found', 0, 404);
        }

        if ($deposit['status'] === self::STATUS_CREDITED) {
            return; // Already credited
        }

        $stmt = $this->db->prepare(
            'UPDATE deposits SET status = ?, confirmations = ?, updated_at = NOW() WHERE deposit_id = ?'
        );
        $stmt->execute([self::STATUS_CONFIRMED, 999, $depositId]);

        $this->ledgerService->confirm($deposit['deposit_id']);
        $this->audit('DEPOSIT_CONFIRMED', ['deposit_id' => $depositId]);
    }

    /**
     * Credit deposit
     */
    public function creditDeposit(string $depositId): void
    {
        $deposit = $this->getDeposit($depositId);

        if (!$deposit) {
            throw new ExqpayException('Deposit not found', 0, 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE deposits SET status = ?, updated_at = NOW() WHERE deposit_id = ?'
        );
        $stmt->execute([self::STATUS_CREDITED, $depositId]);
        $this->audit('DEPOSIT_CREDITED', ['deposit_id' => $depositId]);
    }

    /**
     * Generate deposit address (mock - integrate with blockchain APIs)
     */
    private function generateDepositAddress(string $currency): string
    {
        // This should integrate with actual blockchain address generation
        // For now, return a mock address
        return $currency . '_' . bin2hex(random_bytes(20));
    }

    /**
     * Generate QR code
     */
    private function generateQrCode(string $address): string
    {
        // This should generate actual QR code
        // For now, return placeholder
        return base64_encode($address);
    }
}
