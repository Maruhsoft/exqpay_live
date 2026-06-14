<?php
/**
 * Dependency Injection Container
 */

namespace Exqpay\Core;

class Container
{
    private static array $bindings = [];
    private static array $singletons = [];

    /**
     * Bind interface/class to implementation
     */
    public static function bind(string $abstract, $concrete): void
    {
        self::$bindings[$abstract] = $concrete;
    }

    /**
     * Bind singleton
     */
    public static function singleton(string $abstract, $concrete): void
    {
        self::bind($abstract, $concrete);
        self::$singletons[$abstract] = true;
    }

    /**
     * Resolve binding
     */
    public static function make(string $abstract)
    {
        if (isset(self::$singletons[$abstract]) && is_object(self::$bindings[$abstract])) {
            return self::$bindings[$abstract];
        }

        $concrete = self::$bindings[$abstract] ?? $abstract;

        if (is_callable($concrete)) {
            $instance = $concrete();
        } elseif (is_string($concrete)) {
            $instance = class_exists($concrete) ? new $concrete() : null;
        } else {
            $instance = $concrete;
        }

        if (isset(self::$singletons[$abstract])) {
            self::$bindings[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Check if binding exists
     */
    public static function has(string $abstract): bool
    {
        return isset(self::$bindings[$abstract]);
    }
}
