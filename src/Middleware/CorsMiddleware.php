<?php
/**
 * CORS Middleware
 */

namespace Exqpay\Middleware;

use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Core\Middleware\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins = [];

    public function __construct(array $allowedOrigins = [])
    {
        $this->allowedOrigins = $allowedOrigins ?: ['*'];
    }

    public function handle(Request $request, callable $next): Response
    {
        $origin = $request->header('Origin') ?? '*';

        if ($this->isOriginAllowed($origin)) {
            $response = $next($request);
            $response
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                ->header('Access-Control-Allow-Credentials', 'true');

            return $response;
        }

        return $next($request);
    }

    /**
     * Check if origin is allowed
     */
    private function isOriginAllowed(string $origin): bool
    {
        if (in_array('*', $this->allowedOrigins)) {
            return true;
        }

        return in_array($origin, $this->allowedOrigins);
    }
}
