<?php
/**
 * Role-Based Access Control Middleware
 */

namespace Exqpay\Middleware;

use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Core\Middleware\MiddlewareInterface;
use Exqpay\Core\Exception\AuthorizationException;

class RbacMiddleware implements MiddlewareInterface
{
    private array $roles = [];

    public function __construct(array $roles = [])
    {
        $this->roles = $roles;
    }

    public function handle(Request $request, callable $next): Response
    {
        if (empty($this->roles)) {
            return $next($request);
        }

        $userRole = $_SESSION['role'] ?? null;

        if (!in_array($userRole, $this->roles)) {
            throw new AuthorizationException('Insufficient permissions');
        }

        return $next($request);
    }
}
