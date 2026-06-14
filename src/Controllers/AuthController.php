<?php
/**
 * Authentication Controller
 */

namespace Exqpay\Controllers;

use Exqpay\Core\BaseController;
use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Services\AuthService;
use Exqpay\Utils\Security;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct(Request $request = null, Response $response = null)
    {
        parent::__construct($request, $response);
        $this->authService = new AuthService();
    }

    /**
     * Register endpoint
     */
    public function register(Request $request): Response
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|minlength:8',
            'first_name' => 'required',
            'last_name' => 'required',
        ];

        $this->validate($rules);

        try {
            $user = $this->authService->register(
                $request->input('email'),
                $request->input('password'),
                $request->input('first_name'),
                $request->input('last_name')
            );

            return $this->success($user, 'User registered successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Login endpoint
     */
    public function login(Request $request): Response
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        $this->validate($rules);

        try {
            $user = $this->authService->authenticate(
                $request->input('email'),
                $request->input('password')
            );

            $token = $this->authService->generateToken($user['user_id']);

            return $this->success([
                'user' => $user,
                'token' => $token,
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    /**
     * Verify token endpoint
     */
    public function verify(Request $request): Response
    {
        $token = $request->header('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return $this->error('No token provided', 401);
        }

        $token = substr($token, 7);
        $payload = Security::verifyJwt($token);

        if (!$payload) {
            return $this->error('Invalid token', 401);
        }

        return $this->success($payload, 'Token valid');
    }
}
