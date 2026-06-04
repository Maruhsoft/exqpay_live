<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExqPay Live - Crypto Exchange</title>
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
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--primary);
            color: var(--text);
            line-height: 1.6;
        }

        /* ===== HEADER ===== */
        header {
            background: var(--primary);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
        }

        .header-container {
            max-width: 1200px;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        nav a {
            color: var(--text);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        nav a:hover {
            color: var(--accent);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.95rem;
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
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.2);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--accent);
            color: var(--accent);
        }

        .btn-secondary:hover {
            background: var(--accent);
            color: var(--primary);
        }

        /* ===== HERO ===== */
        .hero {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 1rem;
            text-align: center;
        }

        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.5rem);
            margin-bottom: 1rem;
            color: var(--text);
        }

        .hero .highlight {
            color: var(--accent);
        }

        .hero p {
            font-size: 1.1rem;
            color: var(--secondary);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ===== CONTAINER ===== */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* ===== GRID ===== */
        .grid {
            display: grid;
            gap: 2rem;
            margin: 2rem 0;
        }

        .grid-2 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        /* ===== CARDS ===== */
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
            transform: translateY(-4px);
        }

        .card h3 {
            color: var(--accent);
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .card p {
            color: var(--secondary);
            margin-bottom: 1rem;
        }

        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 500;
        }

        input, textarea, select {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            color: var(--text);
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        /* ===== DASHBOARD ===== */
        .dashboard {
            padding: 2rem 0;
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .balance-display {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-left: 4px solid var(--accent);
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }

        .balance-label {
            color: var(--secondary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .balance-amount {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent);
        }

        /* ===== TRANSACTIONS TABLE ===== */
        .table-responsive {
            overflow-x: auto;
            margin: 2rem 0;
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
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
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

        /* ===== MODAL ===== */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s ease;
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
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: var(--accent);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* ===== ALERT ===== */
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .alert-success {
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid var(--warning);
            color: var(--warning);
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--bg-light);
            border-top: 1px solid var(--border);
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h4 {
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .footer-section a {
            display: block;
            color: var(--secondary);
            text-decoration: none;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: var(--accent);
        }

        .footer-bottom {
            border-top: 1px solid var(--border);
            padding-top: 1.5rem;
            text-align: center;
            color: var(--secondary);
            font-size: 0.9rem;
        }

        /* ===== RESPONSIVE ===== */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--accent);
            font-size: 1.5rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                background: var(--bg-light);
                border-bottom: 1px solid var(--border);
                padding: 1rem;
                gap: 0.5rem;
            }

            nav.active {
                display: flex;
            }

            .hero {
                padding: 2rem 1rem;
            }

            .hero h1 {
                font-size: 1.75rem;
            }

            .hero-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }

            .table-responsive {
                font-size: 0.85rem;
            }

            th, td {
                padding: 0.75rem 0.5rem;
            }

            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .header-container {
                gap: 1rem;
            }

            .logo {
                font-size: 1.25rem;
            }

            .hero h1 {
                font-size: 1.5rem;
            }

            .balance-amount {
                font-size: 1.5rem;
            }

            .card {
                padding: 1rem;
            }
        }

        /* ===== LOADING ===== */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--border);
            border-top: 3px solid var(--accent);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ===== UTILITY ===== */
        .hidden {
            display: none;
        }

        .text-center {
            text-align: center;
        }

        .mt-1 { margin-top: 0.5rem; }
        .mt-2 { margin-top: 1rem; }
        .mt-4 { margin-top: 2rem; }

        .mb-1 { margin-bottom: 0.5rem; }
        .mb-2 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 2rem; }

        .gap-1 { gap: 0.5rem; }
        .gap-2 { gap: 1rem; }

        .flex {
            display: flex;
        }

        .justify-between {
            justify-content: space-between;
        }

        .items-center {
            align-items: center;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="header-container">
            <a href="/" class="logo">
                <span>⚡</span> ExqPay Live
            </a>
            <button class="menu-toggle" id="menuToggle">☰</button>
            <nav id="nav">
                <a href="/#features">Features</a>
                <a href="/#how-it-works">How It Works</a>
                <a href="/dashboard.php" class="link">Dashboard</a>
                <a href="/login.php" class="btn btn-secondary">Login</a>
                <a href="/register.php" class="btn btn-primary">Sign Up</a>
            </nav>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero">
        <h1>Crypto Exchange Made <span class="highlight">Simple</span></h1>
        <p>Convert crypto to fiat instantly. Trade gift cards. Withdraw to your bank account with confidence.</p>
        <div class="hero-actions">
            <a href="/register.php" class="btn btn-primary">Get Started Free</a>
            <a href="#features" class="btn btn-secondary">Learn More</a>
        </div>
    </section>

    <!-- FEATURES -->
    <section id="features" class="container">
        <h2 class="text-center mb-4" style="font-size: 2rem; color: var(--accent);">Why Choose ExqPay?</h2>
        <div class="grid grid-3">
            <div class="card">
                <h3>🔐 Secure</h3>
                <p>Bank-grade encryption. All transactions verified on blockchain. Your assets are protected.</p>
            </div>
            <div class="card">
                <h3>⚡ Fast</h3>
                <p>Instant deposits. Real-time conversions. Quick withdrawals to your bank account.</p>
            </div>
            <div class="card">
                <h3>💰 Transparent</h3>
                <p>No hidden fees. Competitive rates. Full transaction history at your fingertips.</p>
            </div>
            <div class="card">
                <h3>📱 Mobile First</h3>
                <p>Beautiful interface that works on any device. Manage your portfolio on the go.</p>
            </div>
            <div class="card">
                <h3>🎁 Gift Cards</h3>
                <p>Buy and sell gift cards with crypto. Expand your portfolio with alternative assets.</p>
            </div>
            <div class="card">
                <h3>🌍 Global</h3>
                <p>Available worldwide. Multi-currency support. Connect with a global community.</p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>Product</h4>
                <a href="#">Features</a>
                <a href="#">Pricing</a>
                <a href="#">Security</a>
                <a href="#">API</a>
            </div>
            <div class="footer-section">
                <h4>Company</h4>
                <a href="#">About</a>
                <a href="#">Blog</a>
                <a href="#">Careers</a>
                <a href="#">Contact</a>
            </div>
            <div class="footer-section">
                <h4>Legal</h4>
                <a href="#">Terms</a>
                <a href="#">Privacy</a>
                <a href="#">Compliance</a>
                <a href="#">Cookies</a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="#">Help Center</a>
                <a href="#">Status</a>
                <a href="#">Contact Us</a>
                <a href="#">Feedback</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 ExqPay Live. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const nav = document.getElementById('nav');

        menuToggle.addEventListener('click', () => {
            nav.classList.toggle('active');
        });

        // Close menu on link click
        nav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                nav.classList.remove('active');
            });
        });

        // Close menu on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('header')) {
                nav.classList.remove('active');
            }
        });
    </script>
</body>
</html>
