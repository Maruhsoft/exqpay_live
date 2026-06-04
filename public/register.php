<?php
/**
 * User Registration Page
 */

require_once dirname(__DIR__) . '/config/app.php';

use Exqpay\Core\Config;
use Exqpay\Services\AuthService;
use Exqpay\Utils\Security;

Config::load();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = Security::sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = Security::sanitize($_POST['first_name'] ?? '');
    $lastName = Security::sanitize($_POST['last_name'] ?? '');
    $terms = $_POST['terms'] ?? false;

    // Validation
    if (!$email) $errors[] = 'Email is required';
    if (!$firstName) $errors[] = 'First name is required';
    if (!$lastName) $errors[] = 'Last name is required';
    if (!$password) $errors[] = 'Password is required';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';
    if (!$terms) $errors[] = 'You must agree to the terms';

    if (empty($errors)) {
        try {
            AuthService::register($email, $password, $firstName, $lastName);
            $success = true;
            header('Location: /login.php?registered=1');
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
    <title>Sign Up - ExqPay Live</title>
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        input[type="checkbox"] {
            width: auto;
            margin-top: 0.25rem;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: 400;
            font-size: 0.85rem;
            color: var(--secondary);
        }

        .checkbox-group a {
            color: var(--accent);
            text-decoration: none;
        }

        .checkbox-group a:hover {
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
            margin-top: 1rem;
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
            border: 1px solid #4ade80;
            color: #4ade80;
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

        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background: var(--bg);
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            background: var(--danger);
            transition: width 0.3s ease, background 0.3s ease;
        }

        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            color: var(--secondary);
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Join ExqPay Live today</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-strength-text" id="strengthText">Minimum 8 characters</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="terms" name="terms">
                    <label for="terms">I agree to the <a href="/terms">Terms of Service</a> and <a href="/privacy">Privacy Policy</a></label>
                </div>

                <button type="submit" class="btn">Create Account</button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="/login.php">Sign In</a>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/\d/.test(password)) strength += 15;
            if (/[^a-zA-Z\d]/.test(password)) strength += 10;
            return Math.min(strength, 100);
        }

        function updatePasswordStrength() {
            const strength = calculatePasswordStrength(passwordInput.value);
            strengthBar.style.width = strength + '%';

            if (strength < 40) {
                strengthBar.style.background = '#ef4444';
                strengthText.textContent = 'Weak password';
            } else if (strength < 70) {
                strengthBar.style.background = '#f59e0b';
                strengthText.textContent = 'Moderate password';
            } else {
                strengthBar.style.background = '#4ade80';
                strengthText.textContent = 'Strong password';
            }
        }

        passwordInput.addEventListener('input', updatePasswordStrength);

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters');
                return;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }

            if (!document.getElementById('terms').checked) {
                e.preventDefault();
                alert('You must agree to the terms');
                return;
            }
        });
    </script>
</body>
</html>
