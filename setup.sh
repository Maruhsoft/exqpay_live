#!/bin/bash

# ExqPay Live - Setup Instructions
# Production-grade crypto-to-fiat exchange platform

set -e

echo "==================================="
echo "ExqPay Live - Installation Setup"
echo "==================================="
echo ""

# Check PHP version
echo "[1/8] Checking PHP version..."
PHP_VERSION=$(php -v | grep -oP 'PHP \K[0-9]+\.[0-9]+' | head -1)
REQUIRED_VERSION="8.1"

if (( $(echo "$PHP_VERSION < $REQUIRED_VERSION" | bc -l) )); then
    echo "ERROR: PHP 8.1 or higher required. Current: $PHP_VERSION"
    exit 1
fi
echo "✓ PHP $PHP_VERSION installed"
echo ""

# Check required extensions
echo "[2/8] Checking PHP extensions..."
REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "json" "bcmath" "gd" "openssl")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "^$ext"; then
        echo "ERROR: PHP extension '$ext' not found"
        exit 1
    fi
    echo "✓ $ext"
done
echo ""

# Check composer
echo "[3/8] Checking Composer..."
if ! command -v composer &> /dev/null; then
    echo "ERROR: Composer not installed. Install from https://getcomposer.org"
    exit 1
fi
echo "✓ Composer installed"
echo ""

# Create storage directories
echo "[4/8] Creating storage directories..."
mkdir -p storage/logs
mkdir -p storage/uploads
mkdir -p storage/cache
chmod 755 storage/logs storage/uploads storage/cache
echo "✓ Storage directories created"
echo ""

# Copy environment file
echo "[5/8] Setting up environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ .env created from .env.example"
    echo "⚠ IMPORTANT: Edit .env with your database credentials and secrets"
else
    echo "✓ .env file exists"
fi
echo ""

# Install dependencies
echo "[6/8] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader
echo "✓ Dependencies installed"
echo ""

# Database setup prompt
echo "[7/8] Database Setup"
echo "-------------------"
echo "Before proceeding, ensure:"
echo "  1. MySQL/PostgreSQL server is running"
echo "  2. Database credentials are in .env"
echo ""
read -p "Have you configured the database in .env? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "✓ Ready to run migrations"
else
    echo "⚠ Please configure .env and run: php cli/migrate.php"
    exit 0
fi
echo ""

# Run migrations
echo "[8/8] Running database migrations..."
php cli/migrate.php
echo "✓ Database migrations complete"
echo ""

echo "==================================="
echo "✓ Installation Complete!"
echo "==================================="
echo ""
echo "Next steps:"
echo "1. Create admin user: php cli/create-admin.php"
echo "2. Start development server: php -S localhost:8000 -t public/"
echo "3. Access dashboard: http://localhost:8000"
echo ""
echo "Documentation:"
echo "- API: docs/api.md"
echo "- Database: docs/database.md"
echo "- Ledger System: docs/ledger.md"
echo "- Security: docs/security.md"
echo ""
