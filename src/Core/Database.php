<?php
/**
 * Database Connection Manager
 */

namespace Exqpay\Core;

class Database
{
    private static ?\PDO $connection = null;

    /**
     * Get database connection (singleton)
     */
    public static function connection(): \PDO
    {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
        }
        return self::$connection;
    }

    /**
     * Create new database connection
     */
    private static function createConnection(): \PDO
    {
        $config = Config::get('database');
        $driver = $config['default'];
        $connConfig = $config['connections'][$driver];

        if ($driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $connConfig['host'],
                $connConfig['port'],
                $connConfig['database'],
                $connConfig['charset']
            );
        } elseif ($driver === 'pgsql') {
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $connConfig['host'],
                $connConfig['port'],
                $connConfig['database']
            );
        } else {
            throw new \RuntimeException('Unsupported database driver: ' . $driver);
        }

        $pdo = new \PDO(
            $dsn,
            $connConfig['username'],
            $connConfig['password'],
            $connConfig['options']
        );

        // Set proper error mode
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        return $pdo;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): void
    {
        self::connection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): void
    {
        self::connection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): void
    {
        self::connection()->rollBack();
    }

    /**
     * Execute query within transaction
     */
    public static function transaction(callable $callback): mixed
    {
        try {
            self::beginTransaction();
            $result = $callback(self::connection());
            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollback();
            throw $e;
        }
    }
}
