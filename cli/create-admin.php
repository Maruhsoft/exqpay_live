<?php
/**
 * CLI - Create Admin User
 */

require_once dirname(__DIR__) . '/config/app.php';

use Exqpay\Core\Config;
use Exqpay\Services\AuthService;

Config::load();

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line');
}

echo "\n=== Create Admin User ===\n\n";

// Read input
echo "Email: ";
$email = trim(fgets(STDIN));

echo "First Name: ";
$firstName = trim(fgets(STDIN));

echo "Last Name: ";
$lastName = trim(fgets(STDIN));

echo "Password: ";
$password = trim(fgets(STDIN));

try {
    $user = AuthService::register($email, $password, $firstName, $lastName);
    
    // Update user to admin
    $pdo = \Exqpay\Core\Database::connection();
    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE user_id = ?');
    $stmt->execute(['admin', $user['user_id']]);
    
    echo "\n✓ Admin user created successfully\n";
    echo "User ID: " . $user['user_id'] . "\n";
    echo "Email: " . $user['email'] . "\n\n";
    exit(0);
} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
