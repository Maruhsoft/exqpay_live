<?php
/**
 * Bootstrap Application
 */

namespace Exqpay;

use Exqpay\Core\Config;
use Exqpay\Core\Database;
use Exqpay\Core\Logger;
use Exqpay\Core\ErrorHandler;
use Exqpay\Core\Router;
use Exqpay\Middleware\SecurityHeadersMiddleware;
use Exqpay\Middleware\CorsMiddleware;
use Exqpay\Controllers\AuthController;
use Exqpay\Controllers\WalletController;
use Exqpay\Controllers\DepositController;
use Exqpay\Controllers\WithdrawalController;
use Exqpay\Controllers\ConversionController;
use Exqpay\Controllers\LedgerController;

class App
{
    private static Router $router;

    /**
     * Initialize application
     */
    public static function bootstrap(): void
    {
        // Load configuration
        Config::load();

        // Initialize logger
        Logger::init(dirname(__DIR__) . '/storage/logs');

        // Setup error handling
        ErrorHandler::register();

        // Initialize database connection
        Database::connection();

        // Setup session
        self::setupSession();
    }

    /**
     * Setup session
     */
    private static function setupSession(): void
    {
        $config = Config::get('session', []);

        session_set_cookie_params([
            'lifetime' => $config['timeout'] ?? 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $config['secure'] ?? true,
            'httponly' => $config['httponly'] ?? true,
            'samesite' => $config['samesite'] ?? 'Strict',
        ]);

        session_start();
    }

    /**
     * Get router
     */
    public static function getRouter(): Router
    {
        if (!isset(self::$router)) {
            self::$router = self::createRouter();
        }
        return self::$router;
    }

    /**
     * Create and register routes
     */
    private static function createRouter(): Router
    {
        $router = new Router();

        // Global middleware
        $router->middleware(new SecurityHeadersMiddleware());
        $router->middleware(new CorsMiddleware(['*']));

        // Auth routes
        $router->post('/api/auth/register', 'Exqpay\\Controllers\\AuthController@register');
        $router->post('/api/auth/login', 'Exqpay\\Controllers\\AuthController@login');
        $router->post('/api/auth/verify', 'Exqpay\\Controllers\\AuthController@verify');

        // Wallet routes
        $router->get('/api/wallet/balance', 'Exqpay\\Controllers\\WalletController@getBalance');
        $router->post('/api/wallet/create', 'Exqpay\\Controllers\\WalletController@getOrCreate');

        // Deposit routes
        $router->post('/api/deposits/address', 'Exqpay\\Controllers\\DepositController@createAddress');
        $router->get('/api/deposits', 'Exqpay\\Controllers\\DepositController@getUserDeposits');
        $router->get('/api/deposits/{id}', 'Exqpay\\Controllers\\DepositController@getDeposit');

        // Withdrawal routes
        $router->post('/api/withdrawals/request', 'Exqpay\\Controllers\\WithdrawalController@request');
        $router->get('/api/withdrawals/{id}', 'Exqpay\\Controllers\\WithdrawalController@getWithdrawal');
        $router->post('/api/withdrawals/{id}/approve', 'Exqpay\\Controllers\\WithdrawalController@approve');
        $router->post('/api/withdrawals/{id}/reject', 'Exqpay\\Controllers\\WithdrawalController@reject');

        // Conversion routes
        $router->get('/api/conversions/rate', 'Exqpay\\Controllers\\ConversionController@getRate');
        $router->post('/api/conversions/convert', 'Exqpay\\Controllers\\ConversionController@convert');

        // Ledger routes
        $router->get('/api/ledger/history', 'Exqpay\\Controllers\\LedgerController@getHistory');
        $router->get('/api/ledger/{id}', 'Exqpay\\Controllers\\LedgerController@getTransaction');

        return $router;
    }
}
