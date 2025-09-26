# Product Requirements

This document highlights the core capabilities currently delivered by the backend
implementation alongside the future features envisioned for the Free Fire
Tournament Platform.

## Personas

- **Player** – Registers, manages wallet funds, and joins tournaments.
- **Admin** – Configures tournaments, supervises finances, and reviews
  withdrawals.
- **Staff** – Oversees assigned matches and records final results.

## Delivered Functionality

| Area              | Capabilities                                                                 |
| ----------------- | ----------------------------------------------------------------------------- |
| Account           | Email registration, password login, bearer-token authentication.              |
| Tournaments       | CRUD-lite support (create + list + detail) and participant enrolment.         |
| Matches           | Admin match creation, staff assignment, and result submission.                |
| Wallet            | Manual deposits, entry fee deductions, withdrawal requests, admin approvals.  |
| Roles & Security  | Role-based guards for admin, staff, and player endpoints.                     |
| Landing Website   | Static marketing page with APK download placeholder and support contact.      |

## Roadmap Features

### Player Experience

1. **Profile Enhancements** – Avatar uploads, in-game identifiers, and device
   verification.
2. **Payment Integrations** – Payment gateway deposits, automatic payout
   processing, and KYC validation.
3. **Notifications** – Push notifications for match reminders, results, and
   wallet changes.
4. **Support Desk** – Ticketing workflows and chat-style conversations with
   moderators.

### Admin Console

1. **Tournament Lifecycle** – Draft, publish, archive stages with cloning and
   template support.
2. **Staff Management** – Invitations, fine-grained permissions, and scheduling
   tools.
3. **Analytics** – Dashboards for player growth, revenue, and match health.
4. **Content Management** – Editable website sections, news posts, and FAQs.

### Staff Tools

1. **Lobby Coordination** – Room credential distribution and presence tracking.
2. **Evidence Handling** – Upload screenshots/videos for dispute management.
3. **Escalations** – Structured incident reports routed to admins.

### Public Website

1. **Changelog & Versioning** – Automatically publish APK release notes.
2. **SEO & Campaigns** – Landing page variations, newsletter signups, and UTM
   tracking.
3. **Community Content** – Blog posts, highlight reels, and winner spotlights.

## Non-Functional Goals

- **Performance** – Maintain sub-500 ms response times for the existing API on
  modest infrastructure.
- **Security** – Expand token management to JWT or OAuth2, enforce HTTPS, and
  integrate audit logging for financial operations.
- **Scalability** – Prepare migration path to MySQL/PostgreSQL and add caching
  as concurrency grows.
- **Observability** – Introduce structured logging and health-check endpoints to
  monitor service uptime.

These requirements will evolve as mobile and web clients are built on top of the
new backend foundation.
