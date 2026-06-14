<?php
/**
 * Error Handler for exceptions
 */

namespace Exqpay\Core;

use Exqpay\Core\Exception\ExqpayException;

class ErrorHandler
{
    /**
     * Register error handlers
     */
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handle exceptions
     */
    public static function handleException(\Throwable $exception): void
    {
        $response = new Response();

        if ($exception instanceof ExqpayException) {
            $response
                ->status($exception->getHttpStatusCode())
                ->error($exception->getMessage(), $exception->getHttpStatusCode(), $exception->getErrors());
        } else {
            Logger::error('Uncaught exception', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $response
                ->status(Response::STATUS_INTERNAL_ERROR)
                ->error('Internal server error', Response::STATUS_INTERNAL_ERROR);
        }

        $response->output();
    }

    /**
     * Handle PHP errors
     */
    public static function handleError(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline
    ): bool {
        Logger::error('PHP Error', [
            'error' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'type' => $errno,
        ]);

        return false;
    }

    /**
     * Handle shutdown
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            Logger::critical('Fatal error', [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
        }
    }
}
