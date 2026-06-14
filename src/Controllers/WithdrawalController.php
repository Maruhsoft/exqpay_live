<?php
/**
 * Withdrawal Controller
 */

namespace Exqpay\Controllers;

use Exqpay\Core\BaseController;
use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Services\WithdrawalService;
use Exqpay\Core\Exception\AuthenticationException;
use Exqpay\Utils\Security;

class WithdrawalController extends BaseController
{
    private WithdrawalService $withdrawalService;

    public function __construct(Request $request = null, Response $response = null)
    {
        parent::__construct($request, $response);
        $this->withdrawalService = new WithdrawalService();
    }

    /**
     * Request withdrawal
     */
    public function request(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Unauthorized');
        }

        $rules = [
            'currency' => 'required',
            'amount' => 'required|numeric',
            'bank_account' => 'required',
        ];

        $this->validate($rules);

        try {
            $withdrawal = $this->withdrawalService->request(
                $_SESSION['user_id'],
                $request->input('currency'),
                $request->input('amount'),
                $request->input('bank_account'),
                Security::generateToken()
            );

            return $this->success($withdrawal, 'Withdrawal request submitted', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get withdrawal details
     */
    public function getWithdrawal(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Unauthorized');
        }

        $withdrawalId = $request->param('id');

        try {
            $withdrawal = $this->withdrawalService->getWithdrawal($withdrawalId);

            if (!$withdrawal || $withdrawal['user_id'] !== $_SESSION['user_id']) {
                return $this->error('Withdrawal not found', 404);
            }

            return $this->success($withdrawal, 'Withdrawal retrieved');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Approve withdrawal (Admin)
     */
    public function approve(Request $request): Response
    {
        if ($_SESSION['role'] !== 'admin') {
            throw new AuthenticationException('Insufficient permissions');
        }

        $withdrawalId = $request->param('id');

        try {
            $this->withdrawalService->approve($withdrawalId, $_SESSION['user_id']);
            return $this->success([], 'Withdrawal approved');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Reject withdrawal (Admin)
     */
    public function reject(Request $request): Response
    {
        if ($_SESSION['role'] !== 'admin') {
            throw new AuthenticationException('Insufficient permissions');
        }

        $rules = ['reason' => 'required'];
        $this->validate($rules);

        $withdrawalId = $request->param('id');

        try {
            $this->withdrawalService->reject(
                $withdrawalId,
                $request->input('reason'),
                $_SESSION['user_id']
            );
            return $this->success([], 'Withdrawal rejected');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
