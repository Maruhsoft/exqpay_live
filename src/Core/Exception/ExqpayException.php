<?php
/**
 * Base Exception for ExqPay
 */

namespace Exqpay\Core\Exception;

class ExqpayException extends \Exception
{
    protected int $httpStatusCode = 500;
    protected array $errors = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        int $httpStatusCode = 500,
        array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->httpStatusCode = $httpStatusCode;
        $this->errors = $errors;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
