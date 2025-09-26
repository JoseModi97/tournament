# Free Fire Tournament Platform User Manual

## Introduction

The Free Fire Tournament Platform is a PHP 8 web service that powers the player,
admin, and staff experiences described in the project requirements. This manual
explains how to prepare your environment, configure the backend, run migrations,
and operate the HTTP API for local development or staging deployments.

## Prerequisites

- **PHP 8.1 or newer** with the SQLite3 extension enabled.
- **Composer (optional)** if you plan to add third-party libraries.
- A terminal environment capable of running PHP CLI scripts.

All dependencies in the current codebase are part of PHP's standard library, so
no package installation is required after PHP itself is installed.

## Project Structure Overview

```
├── bin/                # CLI utilities for database setup and seeding
├── docs/               # Documentation, including this manual
├── public/             # HTTP entry point for the API and landing page
├── src/                # Application source code (autoloaded PSR-4 style)
└── storage/            # SQLite database files (created automatically)
```

## Initial Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd tournament
   ```

2. **Verify PHP version**
   ```bash
   php -v
   ```
   Ensure the output shows PHP 8.1 or newer. Install or upgrade PHP if needed.

3. **Create the storage directory (if missing)**
   The `storage` directory is committed to the repository. If you perform a
   clean checkout without it, create the directory manually:
   ```bash
   mkdir -p storage
   ```

## Database Migration and Seeding

The platform uses an SQLite database located at `storage/database.sqlite`.

> **Having trouble?** If `php bin/migrate.php` reports `could not find driver`,
> enable the PDO SQLite extension. On Windows, uncomment `extension=pdo_sqlite`
> and `extension=sqlite3` in your `php.ini`. On Debian/Ubuntu, run
> `sudo apt install php-sqlite3` and restart PHP.

1. **Run migrations** to create the schema:
   ```bash
   php bin/migrate.php
   ```

2. **Seed demo data** (optional but recommended for testing):
   ```bash
   php bin/seed.php
   ```
   This script provisions sample admin, staff, and player accounts and populates
   example tournaments and matches. Refer to the script output for credentials.

### Demo Data Overview

| Role  | Email                | Password   | Notes |
|-------|----------------------|------------|-------|
| Admin | `admin@example.com`  | `admin123` | Can manage tournaments, wallets, and withdrawals. |
| Staff | `staff@example.com`  | `staff123` | Assigned to update match results. |
| User  | `player@example.com` | `player123`| Pre-enrolled in tournaments with wallet history. |

Additional seeded records include:

- **Tournaments**: “Free Fire Solo Showdown” (upcoming) and “Free Fire Champions Cup” (completed) with associated matches and registrations.
- **Wallet Transactions**: Deposit, entry fee, approved withdrawal, and pending withdrawal request entries to exercise wallet summaries.
- **Withdrawal Requests**: One approved and one pending request to demonstrate the admin review workflow.

## Launching the HTTP Server

Use PHP's built-in development server to expose the API and landing page:
```bash
php -S localhost:8000 -t public
```
The server will listen on port 8000 by default. Visit `http://localhost:8000`
in a browser to view the landing page. API routes are available under `/api`.

For production deployments, configure a proper web server (e.g., Nginx or
Apache) to direct traffic to `public/index.php` and ensure PHP-FPM is running
with appropriate security settings.

## Authentication Workflow

1. Register a new account via `POST /api/register` **or** log in with a seeded
   account using `POST /api/login`.
2. The login response returns a bearer token. Include it in subsequent requests:
   ```
   Authorization: Bearer <token>
   ```
3. Role-based routes (admin/staff) require accounts with the appropriate role as
   defined in the database.

## Core API Usage

- **List tournaments**: `GET /api/tournaments`
- **Join a tournament**: `POST /api/tournaments/{id}/join`
- **Check wallet balance**: `GET /api/wallet`
- **Request withdrawal**: `POST /api/wallet/withdraw`
- **Admin approve withdrawal**: `POST /api/admin/withdrawals/{id}/approve`
- **Staff record result**: `POST /api/matches/{id}/result`

Refer to `docs/api.md` for the complete catalog with request/response payloads.

## Managing Environment Variables

Configuration values (e.g., database path) are defined in `src/Config.php`. For
more complex setups, extend this file to load environment variables via `getenv`
or a configuration library and update the service container accordingly.

## Logs and Troubleshooting

- The PHP built-in server logs requests and errors to stdout. Monitor the
  console running `php -S` for real-time diagnostics.
- Database issues usually stem from missing migrations. Re-run `php bin/migrate.php` if you encounter table-not-found errors.
- To reset the environment, delete `storage/database.sqlite` and rerun the
  migration and seed scripts.

## Next Steps and Customization

- Integrate a production-ready authentication mechanism (JWT, OAuth) as needed.
- Replace SQLite with MySQL/PostgreSQL for larger deployments by updating
  `src/Core/Database.php` and the configuration constants.
- Connect the mobile and web front-ends to the documented API endpoints.
- Add automated tests (PHPUnit/Pest) and CI workflows for quality assurance.

---

For additional architectural context, review `docs/architecture.md` and the API
contract in `docs/api.md`.
