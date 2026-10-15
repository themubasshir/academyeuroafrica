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
| `PAYPAL_MODE` | `sandbox` (dev/test) or `live` |
| `PAYPAL_CLIENT_ID` / `PAYPAL_CLIENT_SECRET` | REST app credentials |
| `PAYPAL_WEBHOOK_ID` | for webhook verification |
| `PAYPAL_CURRENCY` | default `EUR` — order's own currency is authoritative |
| `PAYPAL_BRAND_NAME` | shown on PayPal approval page |

Setup: PayPal Developer → create a **Sandbox REST app** → copy Client ID +
Secret → create a **webhook** pointing at
`https://<host>/webhooks/paypal` for `PAYMENT.CAPTURE.*`/`CHECKOUT.ORDER.*`
events → copy the Webhook ID into `PAYPAL_WEBHOOK_ID`. Never commit secrets;
sandbox creds never reach the live base URL.

Full setup/test/switch-to-live guide: `DEPLOYMENT_INTERSERVER.md` + `TESTING.md`.
