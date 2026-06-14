<?php
/**
 * Middleware Interface
 */

namespace Exqpay\Core\Middleware;

use Exqpay\Core\Request;
use Exqpay\Core\Response;

interface MiddlewareInterface
{
    /**
     * Handle middleware
     */
    public function handle(Request $request, callable $next): Response;
}
