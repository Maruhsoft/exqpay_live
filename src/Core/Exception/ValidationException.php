<?php
/**
 * Validation Exception
 */

namespace Exqpay\Core\Exception;

class ValidationException extends ExqpayException
{
    public function __construct(array $errors = [], string $message = 'Validation failed')
    {
        parent::__construct($message, 0, 422, $errors);
    }
}
