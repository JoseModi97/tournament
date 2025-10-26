# API Specification

This document describes the current HTTP API for the Free Fire Tournament
Platform backend included in this repository. All endpoints exchange JSON
payloads and are prefixed with `/api`.

## Authentication

| Method | Endpoint        | Description                   |
| ------ | --------------- | ----------------------------- |
| POST   | `/api/register` | Register a new account        |
| POST   | `/api/login`    | Obtain an access token        |
| GET    | `/api/me`       | Retrieve authenticated profile|

### Login Response

```json
{
  "token": "<access_token>",
  "expires_at": "2024-05-01T10:30:00+00:00",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

Include the token in the `Authorization: Bearer <token>` header to access
protected routes.

## Tournaments

| Method | Endpoint                     | Role   | Description                                   |
| ------ | ---------------------------- | ------ | --------------------------------------------- |
| GET    | `/api/tournaments`           | Any    | List tournaments ordered by start time        |
| GET    | `/api/tournaments/{id}`      | Any    | Retrieve tournament details, matches, players |
| POST   | `/api/tournaments`           | Admin  | Create a tournament                            |
| POST   | `/api/tournaments/{id}/join` | Auth   | Join a tournament (deducts entry fee)          |

### Tournament Payload

```json
{
  "name": "Solo Showdown",
  "description": "128-player qualifier",
  "entry_fee": 30,
  "prize_pool": 1000,
  "start_time": "2024-05-08T18:00:00+05:30"
}
```

## Matches

| Method | Endpoint                      | Role        | Description                                  |
| ------ | ----------------------------- | ----------- | -------------------------------------------- |
| POST   | `/api/matches`                | Admin       | Create a match and optionally assign staff   |
| GET    | `/api/staff/matches`          | Staff       | List matches assigned to the staff member    |
| POST   | `/api/matches/{id}/result`    | Staff/Admin | Update match status and submit results       |

### Result Update Payload

```json
{
  "status": "completed",
  "result_text": "Squad 22 takes the Booyah!"
}
```

## Wallet

| Method | Endpoint                 | Role   | Description                                  |
| ------ | ------------------------ | ------ | -------------------------------------------- |
| GET    | `/api/wallet`            | Auth   | View balance and last 50 transactions        |
| POST   | `/api/wallet/deposit`    | Auth   | Record a manual deposit                      |
| POST   | `/api/wallet/withdraw`   | Auth   | Request a withdrawal (moves to pending)      |

### Deposit Payload

```json
{
  "amount": 200
}
```

## Withdrawal Administration

| Method | Endpoint                               | Role  | Description                              |
| ------ | -------------------------------------- | ----- | ---------------------------------------- |
| GET    | `/api/admin/withdrawals`               | Admin | List pending and processed withdrawals   |
| POST   | `/api/admin/withdrawals/{id}/approve`  | Admin | Approve the withdrawal request           |
| POST   | `/api/admin/withdrawals/{id}/reject`   | Admin | Reject the withdrawal and refund balance |

## Error Format

Errors are returned with an HTTP status code and body:

```json
{
  "error": "Invalid credentials"
}
```

## Rate Limiting & Pagination

The starter implementation does not yet include rate limiting or pagination.
These can be added using middleware and query parameters as the platform scales.
