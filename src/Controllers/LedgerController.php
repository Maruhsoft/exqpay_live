<?php
/**
 * Ledger Controller
 */

namespace Exqpay\Controllers;

use Exqpay\Core\BaseController;
use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Services\LedgerService;
use Exqpay\Core\Exception\AuthenticationException;

class LedgerController extends BaseController
{
    private LedgerService $ledgerService;

    public function __construct(Request $request = null, Response $response = null)
    {
        parent::__construct($request, $response);
        $this->ledgerService = new LedgerService();
    }

    /**
     * Get transaction history
     */
    public function getHistory(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Unauthorized');
        }

        $currency = $request->input('currency');
        $limit = (int)$request->input('limit', 50);

        try {
            $history = $this->ledgerService->getHistory(
                $_SESSION['user_id'],
                $currency,
                $limit
            );

            return $this->success($history, 'History retrieved');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get transaction details
     */
    public function getTransaction(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Unauthorized');
        }

        $transactionId = $request->param('id');

        try {
            $transaction = $this->ledgerService->getById($transactionId);

            if (!$transaction || $transaction['user_id'] !== $_SESSION['user_id']) {
                return $this->error('Transaction not found', 404);
            }

            return $this->success($transaction, 'Transaction retrieved');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
