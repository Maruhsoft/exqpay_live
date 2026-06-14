<?php
/**
 * JWT Authentication Middleware
 */

namespace Exqpay\Middleware;

use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Core\Middleware\MiddlewareInterface;
use Exqpay\Core\Exception\AuthenticationException;
use Exqpay\Utils\Security;

class JwtAuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            throw new AuthenticationException('No token provided');
        }

        $payload = Security::verifyJwt($token);

        if (!$payload) {
            throw new AuthenticationException('Invalid or expired token');
        }

        // Store authenticated user in request
        $_SESSION['user_id'] = $payload['user_id'] ?? null;
        $_SESSION['email'] = $payload['email'] ?? null;
        $_SESSION['role'] = $payload['role'] ?? null;

        return $next($request);
    }

    /**
     * Extract token from request
     */
    private function extractToken(Request $request): ?string
    {
        $auth = $request->header('Authorization');

        if ($auth && str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }

        return null;
    }
}
