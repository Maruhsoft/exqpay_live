<?php
/**
 * Rate Limiting Middleware
 */

namespace Exqpay\Middleware;

use Exqpay\Core\Request;
use Exqpay\Core\Response;
use Exqpay\Core\Middleware\MiddlewareInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests = 100;
    private int $windowSeconds = 60;
    private string $cacheDir;

    public function __construct(int $maxRequests = 100, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->cacheDir = dirname(__DIR__, 2) . '/storage/cache';
    }

    public function handle(Request $request, callable $next): Response
    {
        $identifier = $request->ip() . '::' . $request->path();
        $cacheFile = $this->cacheDir . '/' . md5($identifier) . '.rate';

        $now = time();
        $data = @json_decode(file_get_contents($cacheFile), true) ?? [];

        // Clean old entries
        $data['timestamps'] = array_filter($data['timestamps'] ?? [], function ($time) use ($now) {
            return $time > $now - $this->windowSeconds;
        });

        if (count($data['timestamps']) >= $this->maxRequests) {
            $response = new Response();
            return $response
                ->status(429)
                ->error('Too many requests', 429);
        }

        $data['timestamps'][] = $now;
        @file_put_contents($cacheFile, json_encode($data));

        return $next($request);
    }
}
