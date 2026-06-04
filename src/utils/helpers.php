<?php
/**
 * Global Helper Functions
 */

use Exqpay\Core\Logger;
use Exqpay\Core\Config;
use Exqpay\Core\Database;
use Exqpay\Utils\Security;

if (!function_exists('config')) {
    /**
     * Get configuration value
     */
    function config(string $key, $default = null) {
        return Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable
     */
    function env(string $key, $default = null) {
        return getenv($key) ?: $default;
    }
}

if (!function_exists('db')) {
    /**
     * Get database connection
     */
    function db(): \PDO {
        return Database::connection();
    }
}

if (!function_exists('log_action')) {
    /**
     * Log action
     */
    function log_action(string $action, array $data = [], string $level = 'info') {
        Logger::log($action, $data, $level);
    }
}

if (!function_exists('encrypt')) {
    /**
     * Encrypt data
     */
    function encrypt(string $data): string {
        return Security::encrypt($data);
    }
}

if (!function_exists('decrypt')) {
    /**
     * Decrypt data
     */
    function decrypt(string $encrypted): string {
        return Security::decrypt($encrypted);
    }
}

if (!function_exists('hash_password')) {
    /**
     * Hash password
     */
    function hash_password(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 19456, 'time_cost' => 4, 'parallelism' => 1]);
    }
}

if (!function_exists('verify_password')) {
    /**
     * Verify password
     */
    function verify_password(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}

if (!function_exists('generate_uuid')) {
    /**
     * Generate UUID v4
     */
    function generate_uuid(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

if (!function_exists('generate_idempotency_key')) {
    /**
     * Generate idempotency key for financial operations
     */
    function generate_idempotency_key(): string {
        return hash('sha256', generate_uuid() . microtime(true) . random_bytes(16));
    }
}

if (!function_exists('current_timestamp')) {
    /**
     * Get current timestamp
     */
    function current_timestamp(): string {
        return date('Y-m-d H:i:s', time());
    }
}

if (!function_exists('current_unix_timestamp')) {
    /**
     * Get current unix timestamp
     */
    function current_unix_timestamp(): int {
        return time();
    }
}

if (!function_exists('format_amount')) {
    /**
     * Format monetary amount with proper precision
     */
    function format_amount($amount, int $decimals = 8): string {
        if (extension_loaded('bcmath')) {
            return bcscale($decimals) ? number_format($amount, $decimals, '.', '') : (string)$amount;
        }
        return number_format((float)$amount, $decimals, '.', '');
    }
}

if (!function_exists('add_amounts')) {
    /**
     * Add two amounts with precision using BCMath
     */
    function add_amounts($a, $b): string {
        if (!extension_loaded('bcmath')) {
            return (string)((float)$a + (float)$b);
        }
        return bcadd((string)$a, (string)$b, 18);
    }
}

if (!function_exists('subtract_amounts')) {
    /**
     * Subtract two amounts with precision using BCMath
     */
    function subtract_amounts($a, $b): string {
        if (!extension_loaded('bcmath')) {
            return (string)((float)$a - (float)$b);
        }
        return bcsub((string)$a, (string)$b, 18);
    }
}

if (!function_exists('multiply_amounts')) {
    /**
     * Multiply two amounts with precision using BCMath
     */
    function multiply_amounts($a, $b): string {
        if (!extension_loaded('bcmath')) {
            return (string)((float)$a * (float)$b);
        }
        return bcmul((string)$a, (string)$b, 18);
    }
}

if (!function_exists('compare_amounts')) {
    /**
     * Compare two amounts
     * Returns: -1 if $a < $b, 0 if equal, 1 if $a > $b
     */
    function compare_amounts($a, $b): int {
        if (!extension_loaded('bcmath')) {
            $a = (float)$a;
            $b = (float)$b;
            return $a < $b ? -1 : ($a > $b ? 1 : 0);
        }
        return bccomp((string)$a, (string)$b, 18);
    }
}

if (!function_exists('json_encode_safe')) {
    /**
     * Safe JSON encoding
     */
    function json_encode_safe($data, int $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string {
        $json = json_encode($data, $options | JSON_THROW_ON_ERROR);
        if ($json === false) {
            throw new RuntimeException('JSON encoding failed');
        }
        return $json;
    }
}

if (!function_exists('json_decode_safe')) {
    /**
     * Safe JSON decoding
     */
    function json_decode_safe(string $json, bool $assoc = true) {
        $data = json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
        if ($data === null && $json !== 'null') {
            throw new RuntimeException('JSON decoding failed');
        }
        return $data;
    }
}

if (!function_exists('response_json')) {
    /**
     * JSON response helper
     */
    function response_json(int $code, string $message = '', $data = null, array $meta = []): string {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => $code >= 200 && $code < 300,
            'code'    => $code,
            'message' => $message,
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        return json_encode_safe($response);
    }
}
