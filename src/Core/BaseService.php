<?php
/**
 * Base Service
 */

namespace Exqpay\Core;

use Exqpay\Core\Logger;

class BaseService
{
    protected \PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Generate UUID
     */
    protected function generateId(string $prefix = ''): string
    {
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        return $prefix ? $prefix . '_' . str_replace('-', '', $uuid) : $uuid;
    }

    /**
     * Log action
     */
    protected function log(string $action, array $data = [], string $level = 'info'): void
    {
        Logger::log($action, $data, $level);
    }

    /**
     * Log audit
     */
    protected function audit(string $action, array $data): void
    {
        Logger::audit($action, $data);
    }
}
