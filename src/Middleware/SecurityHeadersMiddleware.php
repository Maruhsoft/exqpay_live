<?php
/**
 * Security Headers Middleware
 */

namespace Exqpay\Middleware;

use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Core\Middleware\MiddlewareInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->header('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection
        $response->header('X-XSS-Protection', '1; mode=block');

        // Strict Transport Security
        $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Content Security Policy
        $response->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'");

        // Referrer Policy
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
