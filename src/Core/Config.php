<?php
/**
 * Configuration Manager
 */

namespace Exqpay\Core;

class Config
{
    private static array $configs = [];
    private static bool $loaded = false;

    /**
     * Load configuration files
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $configDir = dirname(__DIR__, 2) . '/config';

        // Load all PHP config files
        foreach (glob($configDir . '/*.php') as $file) {
            $key = basename($file, '.php');
            self::$configs[$key] = require $file;
        }

        self::$loaded = true;
    }

    /**
     * Get configuration value using dot notation
     * 
     * @param string $key e.g., 'database.connections.mysql.host'
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $value = self::$configs;

        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set configuration value
     */
    public static function set(string $key, $value): void
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $config = &self::$configs;

        foreach ($keys as $segment) {
            if (!isset($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }

        $config = $value;
    }

    /**
     * Get all configurations
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }
        return self::$configs;
    }
}
