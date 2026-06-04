<?php
/**
 * Application Configuration
 */

return [
    'name'     => getenv('APP_NAME') ?: 'ExqPay Live',
    'env'      => getenv('APP_ENV') ?: 'production',
    'debug'    => getenv('APP_DEBUG') === 'true',
    'url'      => getenv('APP_URL') ?: 'http://localhost:8000',
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
    
    'security' => [
        'jwt_secret'    => getenv('JWT_SECRET'),
        'encryption_key' => getenv('ENCRYPTION_KEY'),
        'hmac_secret'   => getenv('HMAC_SECRET'),
    ],
    
    'crypto' => [
        'bitcoin' => [
            'network'              => getenv('BTC_NETWORK') ?: 'mainnet',
            'confirmations_required' => (int)getenv('BTC_CONFIRMATION_REQUIRED') ?: 3,
            'min_deposit'          => '0.001',
        ],
        'ethereum' => [
            'network'              => getenv('ETH_NETWORK') ?: 'mainnet',
            'confirmations_required' => (int)getenv('ETH_CONFIRMATION_REQUIRED') ?: 12,
            'min_deposit'          => '0.01',
        ],
    ],
    
    'withdrawal' => [
        'min_amount'    => (float)getenv('WITHDRAWAL_MIN_AMOUNT') ?: 100,
        'max_amount'    => (float)getenv('WITHDRAWAL_MAX_AMOUNT') ?: 50000,
        'daily_limit'   => (float)getenv('WITHDRAWAL_DAILY_LIMIT') ?: 100000,
        'fee_percent'   => (float)getenv('WITHDRAWAL_FEE_PERCENT') ?: 2.5,
    ],
    
    'kyc' => [
        'required'                 => getenv('KYC_REQUIRED') === 'true',
        'document_verification_required' => getenv('KYC_DOCUMENT_VERIFICATION_REQUIRED') === 'true',
    ],
    
    'session' => [
        'timeout'   => (int)getenv('SESSION_TIMEOUT') ?: 3600,
        'secure'    => getenv('SESSION_SECURE') === 'true',
        'httponly'  => getenv('SESSION_HTTPONLY') === 'true',
        'samesite'  => getenv('SESSION_SAMESITE') ?: 'Strict',
    ],
    
    'features' => [
        'gift_cards'    => getenv('FEATURE_GIFT_CARDS') === 'true',
        'p2p_trading'   => getenv('FEATURE_P2P_TRADING') === 'true',
        'api_webhook'   => getenv('FEATURE_API_WEBHOOK') === 'true',
    ],
];
