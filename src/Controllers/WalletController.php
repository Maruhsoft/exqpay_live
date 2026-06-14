<?php
/**
 * Wallet Controller
 */

namespace Exqpay\Controllers;

use Exqpay\Core\BaseController;
use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Services\WalletService;
use Exqpay\Core\Exception\AuthenticationException;

class WalletController extends BaseController
{
    private WalletService $walletService;

    public function __construct(Request $request = null, Response $response = null)
    {
        parent::__construct($request, $response);
        $this->walletService = new WalletService();
    }

    /**
     * Get wallet balance
     */
    public function getBalance(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Unauthorized');
        }

        $userId = $_SESSION['user_id'];
        $currency = $request->input('currency');

        try {
            if ($currency) {
                $balance = $this->walletService->getBalance($userId, $currency);
            } else {
                $balance = $this->walletService->getBalance($userId);
            }

            return $this->success($balance, 'Balance retrieved');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get or create wallet
     */
    public function getOrCreate(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Unauthorized');
        }

        $rules = ['currency' => 'required'];
        $this->validate($rules);

        try {
            $wallet = $this->walletService->getOrCreate(
                $_SESSION['user_id'],
                $request->input('currency')
            );

            return $this->success($wallet, 'Wallet ready');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
