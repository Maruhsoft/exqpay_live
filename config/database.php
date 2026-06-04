<?php
/**
 * Database Configuration
 */

return [
    'default' => getenv('DB_DRIVER') ?: 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => getenv('DB_HOST'),
            'port'      => getenv('DB_PORT') ?: 3306,
            'database'  => getenv('DB_NAME'),
            'username'  => getenv('DB_USER'),
            'password'  => getenv('DB_PASSWORD'),
            'charset'   => getenv('DB_CHARSET') ?: 'utf8mb4',
            'collation' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',
            'prefix'    => getenv('DB_PREFIX') ?: '',
            'options'   => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES  => false,
                PDO::MYSQL_ATTR_SSL_CA      => null,
            ],
        ],
        'pgsql' => [
            'driver'    => 'pgsql',
            'host'      => getenv('DB_HOST'),
            'port'      => getenv('DB_PORT') ?: 5432,
            'database'  => getenv('DB_NAME'),
            'username'  => getenv('DB_USER'),
            'password'  => getenv('DB_PASSWORD'),
            'charset'   => 'utf8',
            'prefix'    => getenv('DB_PREFIX') ?: '',
            'options'   => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES  => false,
            ],
        ],
    ],
    
    // Connection pool settings for high concurrency
    'pool' => [
        'min_connections'     => 5,
        'max_connections'     => 50,
        'connection_lifetime' => 3600,
        'idle_timeout'        => 900,
    ],
];
