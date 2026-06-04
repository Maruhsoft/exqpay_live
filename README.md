Build a production-grade web application using pure Core PHP (no frameworks, no MVC, no Laravel, no external backend frameworks).

The system is a crypto-to-fiat exchange platform with gift card trading and manual fiat withdrawals via bank transfer.

Architecture must be clean, modular, service-based PHP, using simple directory separation (not MVC patterns).


---

FRONTEND / UI REQUIREMENTS

Design a minimalist, high-end financial interface with the following style:

Colour scheme:

Black (primary background)

Gold (primary accent, buttons, highlights, key actions)

Gray (secondary UI elements, borders, muted text)


UI principles:

Extremely clean and uncluttered

Highly intuitive navigation flow

Premium financial-product feel

Subtle transitions and hover feedback

Mobile-first responsive design

Fast-loading static UI (no heavy frontend frameworks required unless vanilla JS is used)




---

USER ROLES (RBAC SYSTEM)

Implement strict role-based access control:

1. Customer

Register and authenticate

Complete KYC verification

View dashboard (balances, transactions)

Generate deposit wallet address + QR code

Deposit cryptocurrency

View deposit confirmations

Convert crypto → fiat balance internally

Request fiat withdrawal (manual bank transfer)

Buy/sell gift cards

View transaction history



2. Support

View user accounts (read-only sensitive data)

Handle support tickets

Assist with deposit/withdrawal issues

Flag suspicious activity

Cannot approve or execute financial transactions



3. Admin

Full system control

Approve/reject fiat withdrawals

Manage users and KYC approvals

Manage exchange rates and fees

Manage gift card inventory and pricing

Freeze/unfreeze accounts

View audit logs

Handle fraud investigations





---

CORE FUNCTIONAL REQUIREMENTS

1. Wallet & Deposit System

Generate unique deposit addresses per user or transaction

Generate QR codes for deposit addresses

Track blockchain deposits via API/webhooks or polling

Credit funds only after required confirmations

Ensure idempotent deposit processing (no duplicate crediting)



---

2. Internal Ledger (CRITICAL SYSTEM COMPONENT)

Implement a double-entry ledger system in raw PHP

No direct balance updates anywhere

Maintain:

Available balance

Pending balance

Locked/frozen balance


Append-only transaction log (never overwrite history)

All balance updates must go through ledger service functions only



---

3. Crypto → Fiat Conversion Engine

Convert crypto balances to fiat internally

Exchange rates must be:

Configurable

Timestamped

Locked per transaction


All conversions must generate audit records



---

4. Fiat Withdrawal System (Manual Bank Transfer)

Users submit withdrawal requests with bank details

Admin manually processes bank transfers externally

Withdrawal states:

Pending → Approved → Processing → Completed / Rejected


Prevent double execution of withdrawals (critical race condition protection)



---

5. Gift Card Module

Users can buy/sell gift cards using crypto or fiat balances

Admin manages:

Inventory

Pricing

Fulfilment (manual or API-based)


Track gift card lifecycle:

Available → Purchased → Delivered → Redeemed (if applicable)




---

SECURITY & FINANCIAL CORRECTNESS (CRITICAL)

System must enforce:

Strict financial correctness guarantees

Protection against:

Double spending

Race conditions

Replay attacks

Duplicate deposit crediting


Use database transactions (PDO + MySQL recommended)

Idempotency keys for all financial operations

Webhook signature verification for blockchain callbacks

Server-side validation for all monetary operations

No client-trusted financial inputs



---

FRAUD PREVENTION

Transaction velocity limits per user

Suspicious activity detection rules

Manual review triggers for high-risk events

Account freezing capability

Device/session tracking for sensitive actions

Blacklist/whitelist wallet addresses and users



---

COMPLIANCE REQUIREMENTS

KYC required before fiat withdrawals

AML-style monitoring rules for flagged transactions

Full audit logging (immutable logs)

Bank account must be verified and bound to user identity

Admin actions must be fully traceable

Data retention for financial records



---

SYSTEM ARCHITECTURE (NO FRAMEWORK)

Use a modular PHP structure like:

/config

/database

/services (wallet, ledger, withdrawals, gift cards)

/controllers (simple request handlers, not MVC pattern)

/auth

/middleware

/utils

/public


Rules:

No frameworks

No ORM

Use PDO with prepared statements only

No MVC architecture patterns

No heavy abstractions



---

BACKGROUND PROCESSING

Use PHP CLI scripts or cron jobs for:

Deposit confirmations

Blockchain polling

Ledger reconciliation

Withdrawal queue processing




---

NON-FUNCTIONAL REQUIREMENTS

High performance under concurrent users

Safe concurrent balance handling

Deterministic financial operations

Fully auditable transaction flow

System must remain stable under partial failure conditions
