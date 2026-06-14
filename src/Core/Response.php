<?php
/**
 * HTTP Response Abstraction
 */

namespace Exqpay\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $body = '';
    private bool $sent = false;

    public const STATUS_OK = 200;
    public const STATUS_CREATED = 201;
    public const STATUS_ACCEPTED = 202;
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_UNAUTHORIZED = 401;
    public const STATUS_FORBIDDEN = 403;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_CONFLICT = 409;
    public const STATUS_UNPROCESSABLE = 422;
    public const STATUS_INTERNAL_ERROR = 500;
    public const STATUS_SERVICE_UNAVAILABLE = 503;

    /**
     * Set status code
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get status code
     */
    public function getStatus(): int
    {
        return $this->statusCode;
    }

    /**
     * Set header
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Set JSON content type
     */
    public function json(): self
    {
        return $this->header('Content-Type', 'application/json');
    }

    /**
     * Set HTML content type
     */
    public function html(): self
    {
        return $this->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Set body
     */
    public function send(mixed $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Success response
     */
    public function success(array $data = [], string $message = 'Success'): self
    {
        return $this
            ->json()
            ->send([
                'success' => true,
                'message' => $message,
                'data' => $data,
            ]);
    }

    /**
     * Error response
     */
    public function error(string $message, int $code = 400, array $errors = []): self
    {
        return $this
            ->status($code)
            ->json()
            ->send([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ]);
    }

    /**
     * Paginated response
     */
    public function paginated(array $items, int $total, int $page, int $perPage): self
    {
        return $this
            ->json()
            ->send([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'perPage' => $perPage,
                    'pages' => ceil($total / $perPage),
                ],
            ]);
    }

    /**
     * Redirect response
     */
    public function redirect(string $location, int $code = 302): self
    {
        return $this
            ->status($code)
            ->header('Location', $location);
    }

    /**
     * Send response
     */
    public function output(): void
    {
        if ($this->sent) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }

        if (is_array($this->body) || is_object($this->body)) {
            echo json_encode($this->body, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } else {
            echo $this->body;
        }

        $this->sent = true;
    }
}
