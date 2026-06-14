<?php
/**
 * Middleware Pipeline
 */

namespace Exqpay\Core\Middleware;

use Exqpay\Core\Request;
use Exqpay\Core\Response;

class Pipeline
{
    private array $middlewares = [];
    private Request $request;
    private $controller;
    private string $method;

    public function __construct(Request $request, $controller, string $method)
    {
        $this->request = $request;
        $this->controller = $controller;
        $this->method = $method;
    }

    /**
     * Add middleware
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Execute pipeline
     */
    public function execute(): Response
    {
        $pipeline = array_reverse($this->middlewares);

        $next = function (Request $request) {
            $response = call_user_func([$this->controller, $this->method], $request);
            if (!($response instanceof Response)) {
                $response = new Response();
                $response->send($response);
            }
            return $response;
        };

        foreach ($pipeline as $middleware) {
            $next = $this->createNext($middleware, $next);
        }

        return $next($this->request);
    }

    /**
     * Create next middleware callback
     */
    private function createNext(MiddlewareInterface $middleware, callable $next): callable
    {
        return function (Request $request) use ($middleware, $next) {
            return $middleware->handle($request, $next);
        };
    }
}
