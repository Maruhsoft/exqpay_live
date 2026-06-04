<?php
/**
 * CLI Migration Runner
 * Run database migrations
 */

require_once dirname(__DIR__) . '/config/app.php';

use Exqpay\Core\Config;
use Exqpay\Core\Database;
use Exqpay\Database\CreateCoreTablesSchema;

// Load configuration
Config::load();

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line');
}

echo "\n=== ExqPay Live - Database Migrations ===\n\n";

try {
    // Get action
    $action = $argv[1] ?? 'migrate';

    if ($action === 'migrate') {
        echo "[1/1] Running migrations...\n";
        CreateCoreTablesSchema::up();
        echo "\n✓ All migrations completed successfully\n";
    } elseif ($action === 'rollback') {
        echo "[1/1] Rolling back migrations...\n";
        CreateCoreTablesSchema::down();
        echo "\n✓ All migrations rolled back\n";
    } else {
        echo "Usage: php cli/migrate.php [migrate|rollback]\n";
        exit(1);
    }

    echo "\n";
    exit(0);
} catch (\Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
