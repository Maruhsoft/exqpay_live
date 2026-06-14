<?php
/**
 * HTTP Request Abstraction
 */

namespace Exqpay\Core;

class Request
{
    private array $query = [];
    private array $post = [];
    private array $headers = [];
    private array $server = [];
    private array $files = [];
    private ?string $body = null;
    private array $routeParams = [];

    public function __construct(
        array $query = [],
        array $post = [],
        array $headers = [],
        array $server = [],
        array $files = [],
        ?string $body = null
    ) {
        $this->query = $query;
        $this->post = $post;
        $this->headers = $headers;
        $this->server = $server;
        $this->files = $files;
        $this->body = $body;
    }

    /**
     * Create from globals
     */
    public static function createFromGlobals(): self
    {
        return new self(
            $_GET ?? [],
            $_POST ?? [],
            self::getAllHeaders(),
            $_SERVER ?? [],
            $_FILES ?? [],
            file_get_contents('php://input')
        );
    }

    /**
     * Get all HTTP headers
     */
    private static function getAllHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($name, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get query parameter
     */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get POST parameter
     */
    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get input parameter (query or post)
     */
    public function input(string $key, $default = null)
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get all input
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Get header
     */
    public function header(string $key, $default = null): ?string
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get all headers
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get request body
     */
    public function body(): ?string
    {
        return $this->body;
    }

    /**
     * Get JSON body
     */
    public function json(): ?array
    {
        if (!$this->body) {
            return null;
        }
        return json_decode($this->body, true);
    }

    /**
     * Get request method
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get request path
     */
    public function path(): string
    {
        $path = $this->server['REQUEST_URI'] ?? '/';
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        return $path ?: '/';
    }

    /**
     * Get file
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get all files
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * Get client IP
     */
    public function ip(): string
    {
        return $this->server['HTTP_CF_CONNECTING_IP']
            ?? $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    /**
     * Get user agent
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return ($this->header('X-Requested-With') === 'XMLHttpRequest');
    }

    /**
     * Check if request is JSON
     */
    public function isJson(): bool
    {
        return strpos($this->header('Content-Type') ?? '', 'application/json') !== false;
    }

    /**
     * Set route parameters
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Get route parameter
     */
    public function param(string $key, $default = null)
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Get all route parameters
     */
    public function params(): array
    {
        return $this->routeParams;
    }
}
