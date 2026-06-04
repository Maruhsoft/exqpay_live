<?php
/**
 * User Dashboard
 */

require_once dirname(__DIR__) . '/config/app.php';

use Exqpay\Core\Config;
use Exqpay\Services\LedgerService;

Config::load();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user data
$pdo = \Exqpay\Core\Database::connection();
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Get balances
$balances = LedgerService::getBalance($userId);

// Get recent transactions
$transactions = LedgerService::getTransactionHistory($userId, null, 10);

// Get total balance
$totalBalance = 0;
foreach ($balances as $balance) {
    $totalBalance += $balance['total_balance'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ExqPay Live</title>
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
            --success: #4ade80;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--primary);
            color: var(--text);
            line-height: 1.6;
        }

        /* HEADER */
        header {
            background: var(--primary);
            border-bottom: 1px solid var(--border);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent);
            text-decoration: none;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .user-menu {
            position: relative;
        }

        .user-button {
            background: var(--bg-light);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-button:hover {
            border-color: var(--accent);
        }

        .user-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            min-width: 200px;
            margin-top: 0.5rem;
            display: none;
            z-index: 1000;
        }

        .user-dropdown.active {
            display: block;
        }

        .user-dropdown a {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid var(--border);
        }

        .user-dropdown a:last-child {
            border-bottom: none;
        }

        .user-dropdown a:hover {
            background: rgba(212, 175, 55, 0.1);
            color: var(--accent);
        }

        /* CONTAINER */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* GRID */
        .grid {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }

        /* CARD */
        .card {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: var(--accent);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.1);
        }

        .card-title {
            color: var(--secondary);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-value {
            color: var(--accent);
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .card-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--primary);
            font-weight: 600;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .btn-small {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        /* SECTION */
        .section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--text);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 24px;
            background: var(--accent);
            border-radius: 2px;
        }

        /* TABLE */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-light);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        thead {
            background: rgba(212, 175, 55, 0.1);
            border-bottom: 1px solid var(--accent);
        }

        th {
            padding: 1rem;
            text-align: left;
            color: var(--accent);
            font-weight: 600;
            font-size: 0.9rem;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }

        /* BADGE */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(74, 222, 128, 0.2);
            color: var(--success);
        }

        .badge-pending {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--bg-light);
            padding: 2rem;
            border-radius: 0.75rem;
            border: 1px solid var(--border);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            color: var(--accent);
            font-size: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-close:hover {
            color: var(--accent);
        }

        /* FORM */
        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 500;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            color: var(--text);
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        /* BALANCE LIST */
        .balance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .balance-item:last-child {
            border-bottom: none;
        }

        .balance-currency {
            font-weight: 600;
            color: var(--text);
        }

        .balance-amount {
            color: var(--accent);
            font-weight: 600;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .header-container {
                gap: 1rem;
            }

            .logo {
                font-size: 1.25rem;
            }

            .grid-3 {
                grid-template-columns: 1fr;
            }

            .table-responsive {
                font-size: 0.85rem;
            }

            th, td {
                padding: 0.75rem;
            }

            .card-value {
                font-size: 1.5rem;
            }

            .section-title {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }

            .card {
                padding: 1rem;
            }

            .card-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            th, td {
                padding: 0.5rem;
            }

            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .stat {
            background: rgba(212, 175, 55, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.2);
            padding: 1rem;
            border-radius: 0.375rem;
            text-align: center;
        }

        .stat-label {
            color: var(--secondary);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            color: var(--accent);
            font-size: 1.25rem;
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--secondary);
        }

        .empty-state p {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="header-container">
            <a href="/" class="logo">⚡ ExqPay Live</a>
            <div class="header-actions">
                <div class="user-menu">
                    <button class="user-button" id="userButton">
                        👤 <?php echo htmlspecialchars($user['first_name']); ?>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="/profile.php">Profile Settings</a>
                        <a href="/security.php">Security</a>
                        <a href="/documents.php">Documents</a>
                        <a href="/support.php">Support</a>
                        <a href="/logout.php">Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <div class="container">
        <!-- WELCOME -->
        <div class="section">
            <h1 style="margin-bottom: 1rem;">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! 👋</h1>
            <p style="color: var(--secondary); margin-bottom: 1rem;">Here's your account summary and recent activity.</p>
        </div>

        <!-- STATS GRID -->
        <div class="grid grid-3">
            <div class="card">
                <div class="card-title">Total Balance</div>
                <div class="card-value">$<?php echo number_format($totalBalance, 2); ?></div>
                <div class="stat-grid">
                    <div class="stat">
                        <div class="stat-label">Wallets</div>
                        <div class="stat-value"><?php echo count($balances); ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title">KYC Status</div>
                <div style="font-size: 1.5rem; margin: 1rem 0;">
                    <?php if ($user['kyc_status'] === 'approved'): ?>
                        <span class="badge badge-success">✓ Verified</span>
                    <?php elseif ($user['kyc_status'] === 'pending'): ?>
                        <span class="badge badge-pending">⏳ Pending</span>
                    <?php else: ?>
                        <span class="badge badge-danger">✗ Not Started</span>
                    <?php endif; ?>
                </div>
                <button class="btn btn-primary btn-small" onclick="openModal('kycModal')">Complete KYC</button>
            </div>

            <div class="card">
                <div class="card-title">Account Status</div>
                <div style="font-size: 1.5rem; margin: 1rem 0;">
                    <span class="badge badge-success">✓ Active</span>
                </div>
                <p style="color: var(--secondary); font-size: 0.85rem;">Account is secure and active</p>
            </div>
        </div>

        <!-- WALLET BALANCES -->
        <div class="section">
            <div class="section-title">Wallet Balances</div>
            <div class="card">
                <?php if (!empty($balances)): ?>
                    <?php foreach ($balances as $balance): ?>
                        <div class="balance-item">
                            <div>
                                <div class="balance-currency"><?php echo htmlspecialchars($balance['currency']); ?></div>
                                <div style="color: var(--secondary); font-size: 0.85rem;">Total available</div>
                            </div>
                            <div>
                                <div class="balance-amount"><?php echo number_format($balance['available_balance'], 8); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No wallets yet</p>
                        <button class="btn btn-primary" onclick="openModal('depositModal')">Deposit Crypto</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="section">
            <div class="section-title">Quick Actions</div>
            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <button class="btn btn-primary" onclick="openModal('depositModal')" style="padding: 1rem;">
                    📥 Deposit Crypto
                </button>
                <button class="btn btn-primary" onclick="openModal('withdrawModal')" style="padding: 1rem;">
                    📤 Withdraw
                </button>
                <button class="btn btn-primary" onclick="openModal('convertModal')" style="padding: 1rem;">
                    🔄 Convert
                </button>
                <button class="btn btn-primary" onclick="openModal('giftCardModal')" style="padding: 1rem;">
                    🎁 Gift Cards
                </button>
            </div>
        </div>

        <!-- RECENT TRANSACTIONS -->
        <div class="section">
            <div class="section-title">Recent Transactions</div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td><code style="font-size: 0.75rem;"><?php echo substr($tx['transaction_id'], 0, 12); ?>...</code></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $tx['transaction_type'])); ?></td>
                                    <td><?php echo number_format($tx['amount'], 8); ?></td>
                                    <td><?php echo htmlspecialchars($tx['currency']); ?></td>
                                    <td>
                                        <?php if ($tx['status'] === 'confirmed'): ?>
                                            <span class="badge badge-success">✓ Confirmed</span>
                                        <?php elseif ($tx['status'] === 'pending'): ?>
                                            <span class="badge badge-pending">⏳ Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">✗ <?php echo ucfirst($tx['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($tx['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--secondary);">No transactions yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="text-align: right; margin-top: 1rem;">
                <a href="/transactions.php" class="btn btn-secondary">View All</a>
            </div>
        </div>
    </div>

    <!-- MODALS -->
    <div id="depositModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Deposit Crypto</h3>
                <button class="modal-close" onclick="closeModal('depositModal')">×</button>
            </div>
            <div class="form-group">
                <label>Select Currency</label>
                <select>
                    <option>Bitcoin (BTC)</option>
                    <option>Ethereum (ETH)</option>
                    <option>USDT</option>
                    <option>USDC</option>
                </select>
            </div>
            <button class="btn btn-primary" style="width: 100%;">Generate Address</button>
        </div>
    </div>

    <div id="withdrawModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Withdraw to Bank</h3>
                <button class="modal-close" onclick="closeModal('withdrawModal')">×</button>
            </div>
            <div class="form-group">
                <label>Select Currency</label>
                <select>
                    <option>USD</option>
                    <option>EUR</option>
                    <option>GBP</option>
                </select>
            </div>
            <div class="form-group">
                <label>Amount</label>
                <input type="number" placeholder="Enter amount" step="0.01">
            </div>
            <button class="btn btn-primary" style="width: 100%;">Continue</button>
        </div>
    </div>

    <div id="convertModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Convert Crypto</h3>
                <button class="modal-close" onclick="closeModal('convertModal')">×</button>
            </div>
            <div class="form-group">
                <label>From</label>
                <select>
                    <option>Bitcoin (BTC)</option>
                    <option>Ethereum (ETH)</option>
                </select>
            </div>
            <div class="form-group">
                <label>To</label>
                <select>
                    <option>USD Tether (USDT)</option>
                    <option>USD Coin (USDC)</option>
                </select>
            </div>
            <button class="btn btn-primary" style="width: 100%;">Convert</button>
        </div>
    </div>

    <div id="giftCardModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Gift Cards</h3>
                <button class="modal-close" onclick="closeModal('giftCardModal')">×</button>
            </div>
            <p style="color: var(--secondary); margin-bottom: 1rem;">Buy or sell gift cards with crypto</p>
            <button class="btn btn-primary" style="width: 100%; margin-bottom: 0.5rem;">Browse Gift Cards</button>
            <button class="btn btn-secondary" style="width: 100%;">Sell Gift Card</button>
        </div>
    </div>

    <div id="kycModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Complete KYC</h3>
                <button class="modal-close" onclick="closeModal('kycModal')">×</button>
            </div>
            <p style="color: var(--secondary); margin-bottom: 1rem;">Verify your identity to unlock full features</p>
            <button class="btn btn-primary" style="width: 100%;">Start Verification</button>
        </div>
    </div>

    <script>
        // User menu
        document.getElementById('userButton').addEventListener('click', function() {
            document.getElementById('userDropdown').classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu')) {
                document.getElementById('userDropdown').classList.remove('active');
            }
        });

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
