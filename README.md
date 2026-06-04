# ExqPay Live - Crypto-to-Fiat Exchange Platform

## Overview

A production-grade, modular PHP cryptocurrency-to-fiat exchange platform with gift card trading capabilities. Built with pure PHP (no frameworks, no MVC patterns), implementing strict financial correctness guarantees, double-entry ledger accounting, and comprehensive security controls.

## Architecture

```
exqpay_live/
├── config/              # Configuration files
├── database/            # Database setup and migrations
├── src/
│   ├── auth/           # Authentication & RBAC
│   ├── services/       # Core business logic (ledger, wallet, etc.)
│   ├── controllers/    # HTTP request handlers
│   ├── middleware/     # Request/response middleware
│   ├── utils/          # Utilities and helpers
│   └── models/         # Database models (no ORM - raw queries)
├── public/             # Web-accessible files
├── cli/                # Background CLI scripts
└── storage/            # Logs, uploads, cache
```

## Key Features

### 1. Double-Entry Ledger System
- Append-only transaction logging
- Strict financial correctness
- Available, pending, and locked balance tracking
- Cryptographic audit trail

### 2. Wallet & Deposit Management
- Unique deposit address generation per transaction
- QR code generation
- Blockchain polling/webhook support
- Idempotent deposit processing
- Configurable confirmation requirements

### 3. Crypto-to-Fiat Conversion
- Real-time exchange rate configuration
- Timestamped conversion records
- Locked rates per transaction
- Audit trail for all conversions

### 4. Fiat Withdrawal System
- Bank transfer integration
- Multi-state withdrawal workflow
- Race condition protection
- Full audit logging

### 5. Gift Card Module
- Buy/sell gift cards
- Inventory management
- Lifecycle tracking
- Fulfillment workflow

### 6. Role-Based Access Control
- **Customer**: Self-service crypto deposits, conversions, withdrawals, gift cards
- **Support**: Account viewing, ticket handling, activity flagging
- **Admin**: Full system control, approvals, user/inventory management

### 7. Security & Compliance
- KYC verification required
- AML monitoring rules
- Device/session tracking
- Velocity limits per user
- Immutable audit logs
- Bank account verification

## Installation

### Requirements
- PHP 8.1+
- MySQL 5.7+ or PostgreSQL
- PDO extension
- BCMath extension
- GD extension (for QR codes)

### Setup

```bash
# Clone repository
git clone https://github.com/Maruhsoft/exqpay_live.git
cd exqpay_live

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure database
# Edit .env with your database credentials

# Run migrations
php cli/migrate.php

# Create admin user
php cli/create-admin.php

# Start development server
php -S localhost:8000 -t public/
```

## Documentation

- [API Documentation](docs/api.md)
- [Database Schema](docs/database.md)
- [Ledger System](docs/ledger.md)
- [Security Guidelines](docs/security.md)
- [Deployment Guide](docs/deployment.md)

## Development

All financial operations must:
1. Go through the Ledger Service
2. Use database transactions
3. Include idempotency keys
4. Generate audit records
5. Validate on server-side

## Testing

```bash
php cli/test-ledger.php
php cli/test-deposits.php
php cli/test-withdrawals.php
```

## License

Proprietary - ExqPay Live
