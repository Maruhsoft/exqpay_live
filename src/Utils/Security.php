<?php
/**
 * Enhanced Security Utilities
 */

namespace Exqpay\Utils;

class Security
{
    private const ALGORITHM = 'AES-256-GCM';
    private const KEY_LENGTH = 32;
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    /**
     * Get encryption key
     */
    private static function getEncryptionKey(): string
    {
        $key = getenv('ENCRYPTION_KEY');
        if (!$key || strlen($key) < self::KEY_LENGTH) {
            throw new \RuntimeException('Invalid encryption key');
        }
        return substr(hash('sha256', $key), 0, self::KEY_LENGTH);
    }

    /**
     * Encrypt data
     */
    public static function encrypt(string $data): string
    {
        $key = self::getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(self::IV_LENGTH);
        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            self::ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt data
     */
    public static function decrypt(string $encrypted): string
    {
        $key = self::getEncryptionKey();
        $data = base64_decode($encrypted, true);

        if ($data === false) {
            throw new \RuntimeException('Decryption failed: invalid base64');
        }

        $iv = substr($data, 0, self::IV_LENGTH);
        $tag = substr($data, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($data, self::IV_LENGTH + self::TAG_LENGTH);

        $decrypted = openssl_decrypt(
            $ciphertext,
            self::ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed: invalid data');
        }

        return $decrypted;
    }

    /**
     * Generate HMAC signature
     */
    public static function hmac(string $data, string $key = ''): string
    {
        if (empty($key)) {
            $key = getenv('HMAC_SECRET');
        }
        return hash_hmac('sha256', $data, $key);
    }

    /**
     * Verify HMAC signature
     */
    public static function verifyHmac(string $data, string $signature, string $key = ''): bool
    {
        if (empty($key)) {
            $key = getenv('HMAC_SECRET');
        }
        $expectedSignature = hash_hmac('sha256', $data, $key);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash data
     */
    public static function hash(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Hash password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 19456,
            'time_cost' => 4,
            'parallelism' => 1,
        ]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Validate email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Sanitize input
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate JWT token
     */
    public static function generateJwt(array $payload, int $expiresIn = 3600): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload['exp'] = time() + $expiresIn;
        $payload['iat'] = time();

        $headerEncoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $payloadEncoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            getenv('JWT_SECRET'),
            true
        );

        $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Verify and decode JWT token
     */
    public static function verifyJwt(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            getenv('JWT_SECRET'),
            true
        );

        $expectedSignatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
            return null;
        }

        $payload = json_decode(
            base64_decode(strtr($payloadEncoded, '-_', '+/'), true),
            true
        );

        if (!$payload) {
            return null;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }
}
