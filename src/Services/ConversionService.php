<?php
/**
 * Conversion Service for Crypto-to-Fiat
 */

namespace Exqpay\Services;

use Exqpay\Core\BaseService;
use Exqpay\Core\Exception\ExqpayException;

class ConversionService extends BaseService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';

    private LedgerService $ledgerService;

    public function __construct()
    {
        parent::__construct();
        $this->ledgerService = new LedgerService();
    }

    /**
     * Get current exchange rate
     */
    public function getRate(string $fromCurrency, string $toCurrency): string
    {
        // Check cache first
        $cacheFile = dirname(__DIR__, 2) . '/storage/cache/rates_' . $fromCurrency . '_' . $toCurrency . '.json';
        if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 300) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            return $cached['rate'] ?? $this->fetchRate($fromCurrency, $toCurrency);
        }

        return $this->fetchRate($fromCurrency, $toCurrency);
    }

    /**
     * Fetch rate from external API
     */
    private function fetchRate(string $fromCurrency, string $toCurrency): string
    {
        $apiUrl = config('app.exchange_rate_api', 'https://api.coingecko.com/api/v3');
        $url = $apiUrl . '/simple/price?ids=' . strtolower($fromCurrency) . '&vs_currencies=' . strtolower($toCurrency);

        try {
            $response = @file_get_contents($url);
            $data = json_decode($response, true);

            if (!$data || !isset($data[strtolower($fromCurrency)][strtolower($toCurrency)])) {
                return '0';
            }

            $rate = (string)$data[strtolower($fromCurrency)][strtolower($toCurrency)];

            // Cache the rate
            $cacheFile = dirname(__DIR__, 2) . '/storage/cache/rates_' . $fromCurrency . '_' . $toCurrency . '.json';
            @file_put_contents($cacheFile, json_encode(['rate' => $rate, 'timestamp' => time()]));

            return $rate;
        } catch (\Exception $e) {
            $this->log('Failed to fetch exchange rate', ['error' => $e->getMessage()], 'error');
            return '0';
        }
    }

    /**
     * Convert amount
     */
    public function convert(
        string $userId,
        string $fromCurrency,
        string $toCurrency,
        string $fromAmount,
        string $idempotencyKey
    ): array {
        $rate = $this->getRate($fromCurrency, $toCurrency);

        if ($rate === '0') {
            throw new ExqpayException('Unable to fetch exchange rate', 0, 500);
        }

        $toAmount = bcmul($fromAmount, $rate, 18);
        $conversionId = $this->generateId('conv');

        $this->db->beginTransaction();

        try {
            // Record debit (crypto out)
            $this->ledgerService->record(
                $userId,
                $fromCurrency,
                $fromAmount,
                LedgerService::TYPE_CONVERSION,
                $idempotencyKey . '_from',
                ['conversion_id' => $conversionId, 'to_currency' => $toCurrency]
            );

            // Record credit (fiat in)
            $this->ledgerService->record(
                $userId,
                $toCurrency,
                $toAmount,
                LedgerService::TYPE_CONVERSION,
                $idempotencyKey . '_to',
                ['conversion_id' => $conversionId, 'from_currency' => $fromCurrency]
            );

            // Store conversion record
            $stmt = $this->db->prepare(
                'INSERT INTO conversions (conversion_id, user_id, from_currency, to_currency, from_amount, to_amount, rate, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $conversionId,
                $userId,
                $fromCurrency,
                $toCurrency,
                $fromAmount,
                $toAmount,
                $rate,
                self::STATUS_CONFIRMED,
            ]);

            $this->db->commit();
            $this->audit('CONVERSION_COMPLETED', [
                'conversion_id' => $conversionId,
                'user_id' => $userId,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'from_amount' => $fromAmount,
                'to_amount' => $toAmount,
                'rate' => $rate,
            ]);

            return $this->getConversion($conversionId);
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get conversion
     */
    public function getConversion(string $conversionId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM conversions WHERE conversion_id = ?'
        );
        $stmt->execute([$conversionId]);
        return $stmt->fetch();
    }
}
