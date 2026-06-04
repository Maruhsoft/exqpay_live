<?php
/**
 * Authentication Service
 */

namespace Exqpay\Services;

use Exqpay\Core\Database;
use Exqpay\Utils\Security;
use Exqpay\Core\Logger;

class AuthService
{
    /**
     * Register new user
     */
    public static function register(
        string $email,
        string $password,
        string $firstName,
        string $lastName
    ): array {
        $pdo = Database::connection();

        if (!Security::validateEmail($email)) {
            throw new \InvalidArgumentException('Invalid email address');
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([strtolower($email)]);
        if ($stmt->fetch()) {
            throw new \RuntimeException('Email already registered');
        }

        $userId = self::generateUserId();
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 19456,
            'time_cost' => 4,
            'parallelism' => 1,
        ]);

        try {
            Database::beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO users (user_id, email, password_hash, first_name, last_name, role, kyc_status, account_status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                $userId, strtolower($email), $passwordHash, $firstName, $lastName, 'customer', 'pending', 'active',
            ]);

            Database::commit();
            Logger::info('User registered', ['user_id' => $userId]);

            return [
                'user_id' => $userId,
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ];
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Authenticate user
     */
    public static function authenticate(string $email, string $password): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT user_id, email, password_hash, role, account_status FROM users WHERE email = ? AND account_status = ?'
        );
        $stmt->execute([strtolower($email), 'active']);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Logger::warning('Authentication failed', ['email' => $email]);
            throw new \RuntimeException('Invalid credentials');
        }

        $updateStmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE user_id = ?');
        $updateStmt->execute([$user['user_id']]);

        Logger::info('User authenticated', ['user_id' => $user['user_id']]);

        return [
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    /**
     * Generate JWT token for user
     */
    public static function generateToken(string $userId): string
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT user_id, email, role FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        return Security::generateJwt([
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ]);
    }

    /**
     * Verify JWT token
     */
    public static function verifyToken(string $token): ?array
    {
        return Security::verifyJwt($token);
    }

    /**
     * Change password
     */
    public static function changePassword(string $userId, string $oldPassword, string $newPassword): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if (!password_verify($oldPassword, $user['password_hash'])) {
            throw new \RuntimeException('Incorrect current password');
        }

        $newHash = password_hash($newPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 19456,
            'time_cost' => 4,
            'parallelism' => 1,
        ]);

        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        $stmt->execute([$newHash, $userId]);

        Logger::info('Password changed', ['user_id' => $userId]);
        return true;
    }

    /**
     * Generate user ID
     */
    private static function generateUserId(): string
    {
        return 'USR_' . strtoupper(bin2hex(random_bytes(8)));
    }
}
