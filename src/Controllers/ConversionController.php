<?php
/**
 * Conversion Controller
 */

namespace Exqpay\Controllers;

use Exqpay\Core\BaseController;
use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Services\ConversionService;
use Exqpay\Core\Exception\AuthenticationException;
use Exqpay\Utils\Security;

class ConversionController extends BaseController
{
    private ConversionService $conversionService;

    public function __construct(Request $request = null, Response $response = null)
    {
        parent::__construct($request, $response);
        $this->conversionService = new ConversionService();
    }

    /**
     * Get exchange rate
     */
    public function getRate(Request $request): Response
    {
        $rules = [
            'from' => 'required',
            'to' => 'required',
        ];

        $this->validate($rules);

        try {
            $rate = $this->conversionService->getRate(
                $request->input('from'),
                $request->input('to')
            );

            return $this->success(['rate' => $rate], 'Rate retrieved');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Convert currency
     */
    public function convert(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            throw new AuthenticationException('Unauthorized');
        }

        $rules = [
            'from_currency' => 'required',
            'to_currency' => 'required',
            'amount' => 'required|numeric',
        ];

        $this->validate($rules);

        try {
            $conversion = $this->conversionService->convert(
                $_SESSION['user_id'],
                $request->input('from_currency'),
                $request->input('to_currency'),
                $request->input('amount'),
                Security::generateToken()
            );

            return $this->success($conversion, 'Conversion completed', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
