<?php
/**
 * User Login Page
 */

require_once dirname(__DIR__) . '/config/app.php';

use Exqpay\Core\Config;
use Exqpay\Services\AuthService;
use Exqpay\Utils\Security;

Config::load();

$errors = [];
$registered = $_GET['registered'] ?? false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = Security::sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = $_POST['remember_me'] ?? false;

    if (!$email) $errors[] = 'Email is required';
    if (!$password) $errors[] = 'Password is required';

    if (empty($errors)) {
        try {
            $user = AuthService::authenticate($email, $password);
            $token = AuthService::generateToken($user['user_id']);

            // Set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['token'] = $token;

            // Set cookie for remember me
            if ($rememberMe) {
                setcookie('exqpay_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
            }

            header('Location: /dashboard.php');
            exit;
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - ExqPay Live</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #000000;
            --accent: #d4af37;
            --secondary: #808080;
            --text: #ffffff;
            --bg: #1a1a1a;
            --bg-light: #2d2d2d;
            --border: #404040;
            --danger: #ef4444;
            --success: #4ade80;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--primary);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .container {
            width: 100%;
            max-width: 400px;
        }

        .auth-card {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 2rem;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            font-size: 1.5rem;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: var(--secondary);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 500;
            font-size: 0.95rem;
        }

        input, textarea, select {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            color: var(--text);
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: 400;
            font-size: 0.9rem;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 1.5rem;
        }

        .forgot-password a {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: var(--accent);
            color: var(--primary);
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.2);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        .alert-success {
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert ul {
            list-style: none;
            padding-left: 0;
        }

        .alert li {
            margin-bottom: 0.5rem;
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--secondary);
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: var(--accent);
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            color: var(--secondary);
            font-size: 0.85rem;
        }

        .two-factor {
            background: rgba(212, 175, 55, 0.1);
            border-left: 3px solid var(--accent);
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: var(--accent);
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 1.5rem;
            }

            .auth-header h1 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Sign In</h1>
                <p>Welcome back to ExqPay Live</p>
            </div>

            <?php if ($registered): ?>
                <div class="alert alert-success">
                    Account created successfully! Please sign in with your credentials.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="two-factor">
                <strong>🔐 Secure Login:</strong> Your account is protected with bank-grade security.
            </div>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    <label for="remember_me">Remember me for 30 days</label>
                </div>

                <div class="forgot-password">
                    <a href="/forgot-password.php">Forgot your password?</a>
                </div>

                <button type="submit" class="btn">Sign In</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="/register.php">Create one</a>
            </div>
        </div>
    </div>

    <script>
        // Focus management
        document.getElementById('email').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('password').focus();
            }
        });

        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });

        // Auto-fill email from registration if available
        if (localStorage.getItem('lastEmail')) {
            document.getElementById('email').value = localStorage.getItem('lastEmail');
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            localStorage.setItem('lastEmail', document.getElementById('email').value);
        });
    </script>
</body>
</html>
