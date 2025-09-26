# Free Fire Tournament Platform

This repository now contains a lightweight PHP backend that implements the core
services for a Free Fire tournament ecosystem. The API supports the Android
player app, administrative dashboard, and staff tools described in the
accompanying documentation.

## Features

- Player registration and authentication
- Tournament catalogue with join flow and entry fee handling
- Wallet ledger with manual deposits, withdrawals, and admin approval
- Staff match management, including result reporting
- Admin workflows for creating tournaments, creating matches, and reviewing
  withdrawal requests

## Project Layout

```
.
├── bin              # CLI utilities for database setup and seeding
├── docs             # Functional requirements, architecture, and API notes
├── public           # HTTP entry point (use with PHP's built-in server)
├── src              # PHP application code
└── storage          # SQLite database location (created automatically)
```

## Getting Started

1. **Install PHP 8.1+** with SQLite support.
2. **Install dependencies** (none external) and run the migrations:

   ```bash
   php bin/migrate.php
   php bin/seed.php   # optional, creates admin/staff/player fixtures
   ```

3. **Start the API server**:

   ```bash
   php -S localhost:8000 -t public
   ```

4. **Interact with the API** using your preferred REST client. Include the
   `Authorization: Bearer <token>` header for protected endpoints after logging
   in.

### Default Accounts

Running `php bin/seed.php` provisions sample credentials:

| Role  | Email               | Password   |
| ----- | ------------------- | ---------- |
| Admin | admin@example.com   | admin123   |
| Staff | staff@example.com   | staff123   |
| User  | player@example.com  | player123  |

## API Highlights

The API entry point is hosted at `/api`. Core routes include:

| Method | Endpoint                              | Description                         |
| ------ | -------------------------------------- | ----------------------------------- |
| POST   | `/api/register`                        | Create a new user account           |
| POST   | `/api/login`                           | Authenticate and receive a token    |
| GET    | `/api/tournaments`                     | List tournaments                    |
| POST   | `/api/tournaments`                     | Create a tournament (admin only)    |
| POST   | `/api/tournaments/{id}/join`           | Join a tournament                   |
| GET    | `/api/wallet`                          | View wallet balance and ledger      |
| POST   | `/api/wallet/deposit`                  | Deposit funds                       |
| POST   | `/api/wallet/withdraw`                 | Request withdrawal                  |
| GET    | `/api/admin/withdrawals`               | View withdrawal requests (admin)    |
| POST   | `/api/admin/withdrawals/{id}/approve`  | Approve withdrawal (admin)          |
| POST   | `/api/admin/withdrawals/{id}/reject`   | Reject withdrawal (admin)           |
| GET    | `/api/staff/matches`                   | View matches (staff)                |
| POST   | `/api/matches/{id}/result`             | Update match result (staff/admin)   |

Review `docs/api.md` for the full endpoint catalogue and adapt the mobile/web
clients accordingly.

## Testing

This starter service does not yet include automated tests. You can exercise the
API manually or integrate your preferred testing framework (e.g. PestPHP or
PHPUnit) as the project matures.
