<?php
/**
 * Authorization Exception
 */

namespace Exqpay\Core\Exception;

class AuthorizationException extends ExqpayException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct($message, 0, 403);
    }
}
