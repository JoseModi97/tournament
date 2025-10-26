# System Architecture

The current implementation delivers a lightweight, self-contained backend for
the Free Fire Tournament Platform. It is designed to run locally or on a single
server while providing clear extension points for a production-ready deployment.

## High-Level Components

1. **PHP API Service**
   - Built with vanilla PHP 8 and SQLite, avoiding heavy dependencies while the
     product is still evolving.
   - Exposes REST endpoints for authentication, tournaments, wallet management,
     and role-specific workflows.
   - Implements a tiny routing layer (`App\\Core\\App`) that maps HTTP methods
     to closures for straightforward request handling.

2. **SQLite Database**
   - Stores users, tokens, tournaments, matches, registrations, wallet
     transactions, and withdrawal requests.
   - Managed via migration scripts (`bin/migrate.php`) so schema changes can be
     version-controlled.

3. **CLI Tooling**
   - `bin/migrate.php` prepares the database.
   - `bin/seed.php` provisions sample admin, staff, and player accounts for
     quick testing.

4. **Landing Page**
   - `public/landing.php` serves a responsive marketing page with APK download
     and support contact placeholders.

## Request Lifecycle

1. PHP's built-in server routes traffic to `public/index.php`.
2. The bootstrap script loads the autoloader and instantiates service classes.
3. Incoming requests are matched to handlers, which:
   - Parse JSON payloads via `App\\Core\\Request`.
   - Validate authentication using `App\\Auth\\AuthService` for protected
     routes.
   - Delegate to domain services (e.g., `TournamentService`, `WalletService`).
4. Responses are serialized to JSON using `App\\Core\\Response` helpers.

## Data Model Overview

- **users** – Account details, hashed passwords, role, wallet balance.
- **tokens** – Session tokens with expiration timestamps.
- **tournaments** – Core tournament metadata and scheduling.
- **matches** – Match records tied to tournaments with optional staff owners.
- **registrations** – Player enrolments enforcing uniqueness per tournament.
- **wallet_transactions** – Ledger of deposits, withdrawals, and entry fees.
- **withdrawal_requests** – Admin-reviewed payout requests.

## Security Considerations

- Passwords stored using bcrypt via `password_hash`.
- Bearer tokens stored in the database with expiry enforcement on each request.
- Role-based guards restrict admin/staff/player functionality.

## Extensibility Roadmap

- Swap SQLite for MySQL/PostgreSQL by updating the PDO DSN in
  `App\\Core\\Database`.
- Replace the custom router with a micro-framework such as Slim or Laravel
  Lumen for middleware and validation support.
- Integrate payment gateways for automated deposits/withdrawals.
- Add JWT support, rate limiting, and background job processing as load
  increases.

## Deployment Notes

- Suitable for a single VPS or container using `php -S` behind Nginx/Apache.
- Persistent storage must include the `storage/` directory for the SQLite file.
- Use environment variables (e.g., `.env`) in future iterations to manage
  secrets and configuration outside of source control.
