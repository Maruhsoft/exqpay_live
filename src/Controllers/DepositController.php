<?php
/**
 * Deposit Controller
 */

namespace Exqpay\Controllers;

use Exqpay\Core\BaseController;
use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Services\DepositService;
use Exqpay\Core\Exception\AuthenticationException;

class DepositController extends BaseController
{
    private DepositService $depositService;

    public function __construct(Request $request = null, Response $response = null)
    {
        parent::__construct($request, $response);
        $this->depositService = new DepositService();
    }

    /**
     * Create deposit address
     */
    public function createAddress(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Unauthorized');
        }

        $rules = ['currency' => 'required'];
        $this->validate($rules);

        try {
            $address = $this->depositService->createAddress(
                $_SESSION['user_id'],
                $request->input('currency')
            );

            return $this->success($address, 'Deposit address created', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get user deposits
     */
    public function getUserDeposits(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Unauthorized');
        }

        $limit = (int)$request->input('limit', 50);

        try {
            $deposits = $this->depositService->getUserDeposits($_SESSION['user_id'], $limit);
            return $this->success($deposits, 'Deposits retrieved');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get deposit details
     */
    public function getDeposit(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Unauthorized');
        }

        $depositId = $request->param('id');

        try {
            $deposit = $this->depositService->getDeposit($depositId);

            if (!$deposit || $deposit['user_id'] !== $_SESSION['user_id']) {
                return $this->error('Deposit not found', 404);
            }

            return $this->success($deposit, 'Deposit retrieved');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
