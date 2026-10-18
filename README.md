# EAMN Academy

Commercial online education platform for **EuroAfrica Media** — student-centred
LMS + course commerce + live/prerecorded training + 1-on-1 tutoring, built on
Laravel 13. Integrates with the existing Joomla site at euroafricamedia.eu.

- Host: InterServer shared (no SSH, no Redis/Supervisor/Node).
- Live sessions hosted externally on Zoom/Teams.

## Stack
Laravel 13 · PHP 8.3 · MySQL/InnoDB · Livewire 4 · Alpine · Tailwind 4 · Vite · PHPUnit

## Docs
| File | Covers |
|---|---|
| `ARCHITECTURE.md` | layers, boundaries, subsystem map |
| `DATABASE.md` | schema & relations |
| `SECURITY.md` | auth, privacy, payments, files, audit |
| `JOOMLA_INTEGRATION.md` | bridge protocol, SSO, page mapping |
| `DEPLOYMENT_INTERSERVER.md` | build, upload, cron, rollback |
| `TESTING.md` | test suite & coverage |
| `CHANGELOG.md` | release history |

## Local setup
```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm ci && npm run build
php artisan serve
```

## Testing
```bash
php artisan test
```

## Roles
`super-admin`, `academy-admin`, `trainer`, `student`.

## Joomla bridge
Plugin source in `joomla/eamn-academy-bridge/` → packaged `com_eamnbridge.zip`.
See `JOOMLA_INTEGRATION.md`.

## Payments — PayPal (Phase 10)
PayPal Checkout (REST Orders v2, CAPTURE) is the active gateway.

| Env var | Purpose |
|---|---|
| `PAYMENT_GATEWAY` | `paypal` (default) |
| `PAYPAL_ENABLED` | `false` by default; enable explicitly after validation |
| `PAYPAL_MODE` | `sandbox` (dev/test) or `live` |
| `PAYPAL_SANDBOX_CLIENT_ID` / `PAYPAL_SANDBOX_CLIENT_SECRET` | Sandbox REST app credentials fallback |
| `PAYPAL_SANDBOX_WEBHOOK_ID` | Sandbox webhook verification ID fallback |
| `PAYPAL_LIVE_CLIENT_ID` / `PAYPAL_LIVE_CLIENT_SECRET` | Live REST app credentials fallback |
| `PAYPAL_LIVE_WEBHOOK_ID` | Live webhook verification ID fallback |
| `PAYPAL_CURRENCY` | default `EUR` — order's own currency is authoritative |
| `PAYPAL_BRAND_NAME` | shown on PayPal approval page |

Setup: PayPal Developer → create separate Sandbox and Live REST apps and webhooks
pointing at `https://<host>/webhooks/paypal`. Store credentials in **Admin →
Payment gateway**; Laravel encrypts them using the application's `APP_KEY`.
Empty credential fields do not replace stored values. Keep the gateway disabled
and Sandbox active until the connection and webhook flow have been accepted.

Full setup/test/switch-to-live guide: `DEPLOYMENT_INTERSERVER.md` + `TESTING.md`.

## Learning workflow

Assigned trainers and Academy administrators build draft or published text, PDF,
and video lessons from the existing course management area. Uploaded content is
stored on the private disk and served through authorised endpoints. Required
lesson progress, quiz results, assignment grades, attendance, and configured
approvals feed the existing certificate eligibility service; qualifying students
receive the existing branded PDF certificate synchronously without a queue worker.

Deployment and acceptance details: `FINAL_LEARNING_ACCEPTANCE.md`.
