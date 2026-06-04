<?php
/**
 * Logging Service
 */

namespace Exqpay\Core;

class Logger
{
    private const LOG_LEVELS = ['debug', 'info', 'warning', 'error', 'critical'];
    private static string $logDir = '';

    /**
     * Initialize logger
     */
    public static function init(string $logDir): void
    {
        self::$logDir = $logDir;
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log message
     */
    public static function log(string $action, array $data = [], string $level = 'info'): void
    {
        if (!in_array($level, self::LOG_LEVELS, true)) {
            $level = 'info';
        }

        $timestamp = date('Y-m-d H:i:s');
        $logFile = self::$logDir . '/' . date('Y-m-d') . '.log';

        $logEntry = json_encode([
            'timestamp' => $timestamp,
            'level'     => strtoupper($level),
            'action'    => $action,
            'data'      => $data,
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
        ]) . PHP_EOL;

        error_log($logEntry, 3, $logFile);
    }

    /**
     * Log financial transaction (immutable audit log)
     */
    public static function audit(string $action, array $data): void
    {
        $auditFile = self::$logDir . '/audit.log';

        $logEntry = json_encode([
            'timestamp'    => date('Y-m-d H:i:s'),
            'unix_time'    => time(),
            'action'       => $action,
            'data'         => $data,
            'ip'           => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            'hash'         => hash('sha256', json_encode($data) . time()),
        ]) . PHP_EOL;

        error_log($logEntry, 3, $auditFile);
    }

    /**
     * Log debug message
     */
    public static function debug(string $action, array $data = []): void
    {
        self::log($action, $data, 'debug');
    }

    /**
     * Log info message
     */
    public static function info(string $action, array $data = []): void
    {
        self::log($action, $data, 'info');
    }

    /**
     * Log warning
     */
    public static function warning(string $action, array $data = []): void
    {
        self::log($action, $data, 'warning');
    }

    /**
     * Log error
     */
    public static function error(string $action, array $data = []): void
    {
        self::log($action, $data, 'error');
    }

    /**
     * Log critical error
     */
    public static function critical(string $action, array $data = []): void
    {
        self::log($action, $data, 'critical');
    }
}
