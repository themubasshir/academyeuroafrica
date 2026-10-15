# Testing

- Runner: PHPUnit (`php artisan test` / `vendor/bin/phpunit`).
- DB: SQLite in-memory via `RefreshDatabase`.
- Factories: `UserFactory` + role states (`->student() ->trainer() ->academyAdmin() ->superAdmin()`).

## Current coverage (Phase 1)
- `tests/Feature/Auth/LoginTest` — login, wrong password, disabled account blocked at login and per-request, rate limiting.
- `tests/Feature/RoleMiddlewareTest` — guest redirect, per-role area gating, super-admin access.
- `tests/Feature/Joomla/BridgeAuthTest` — HMAC-signed exchange call, claims parsing, failure path.

## To add per phase
- Enrolment, webhook idempotency, cross-student IDOR attempts, quiz timing/scoring, double-booking, credit ledger, refund, file-download permission, migration tests.
