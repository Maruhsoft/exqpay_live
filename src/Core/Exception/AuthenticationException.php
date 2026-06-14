<?php
/**
 * Authentication Exception
 */

namespace Exqpay\Core\Exception;

class AuthenticationException extends ExqpayException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 0, 401);
    }
}
