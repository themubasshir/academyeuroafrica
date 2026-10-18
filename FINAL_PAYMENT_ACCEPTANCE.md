# EAMN Academy — Final Payment Acceptance

Prepared 2026-10-18. No production deployment, Joomla modification, PayPal Live activation, or real payment was performed.

## Completed features

- Administrator-only **Settings → Payment Gateway** screen.
- Separate encrypted Sandbox and Live Client ID, Client Secret, and Webhook ID storage.
- Empty credential fields retain the encrypted stored value; saved credentials are never returned to Livewire or audit payloads.
- Explicit PayPal enable/disable and active-environment controls. Saving or testing credentials does not switch environments.
- Safe OAuth connection test with generic failure reporting.
- Configuration-change and connection-test audit records containing field names and state only.
- Stable course checkout URLs backed by one administrator-assigned course package.
- Server-side price, currency, expiry, renewal, capture, and course mapping.
- Verified PayPal webhook signatures using the Webhook ID for the active environment.
- Idempotent capture, webhook, refund, and enrolment handling.
- Cancelled, denied, pending, refunded, and disputed transaction reconciliation.
- Full refunds and disputes end both current entitlement expiry fields; partial refunds preserve access.
- Admin order register shows gateway transaction and refund status.

## Environment configuration

Production must retain one stable `APP_KEY`; changing it makes database-encrypted credentials unreadable. Back up `.env` outside the web root and never include it in an archive.

Ship with:

```dotenv
PAYMENT_GATEWAY=paypal
PAYPAL_ENABLED=false
PAYPAL_MODE=sandbox
PAYPAL_CURRENCY=EUR
PAYPAL_BRAND_NAME="EAMN Academy"
```

Credentials can then be entered through **Admin → Payment gateway**. Environment-variable fallback is also supported with `PAYPAL_SANDBOX_*` and `PAYPAL_LIVE_*`; legacy `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, and `PAYPAL_WEBHOOK_ID` remain supported only for the environment selected by `PAYPAL_MODE`.

Create separate Sandbox and Live PayPal webhooks pointing to:

```text
https://academy.euroafricamedia.eu/webhooks/paypal
```

Subscribe at minimum to `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.PENDING`, `PAYMENT.CAPTURE.DENIED`, `PAYMENT.CAPTURE.REFUNDED`, `CHECKOUT.ORDER.VOIDED`, `CHECKOUT.PAYMENT-APPROVAL.REVERSED`, `CUSTOMER.DISPUTE.CREATED`, `CUSTOMER.DISPUTE.UPDATED`, and `CUSTOMER.DISPUTE.RESOLVED`. Save each environment's matching Webhook ID in its own section.

## cPanel deployment

1. Export the complete production database through phpMyAdmin and download the backup.
2. Keep the current app and public archives as the rollback release.
3. Upload and extract the new private app archive outside the web root.
4. Upload and extract the new public archive into the Academy document root.
5. Preserve the production `.env`, `APP_KEY`, storage contents, and writable permissions.
6. Run `php artisan migrate --force` through the existing IP/token-gated migration runner. If that runner is unavailable and production is still on the pre-2026-10-18 schema, import `deployment/phpmyadmin/2026_10_18_final_payment_phase.sql` once.
7. Clear and rebuild Laravel caches using the approved cPanel maintenance route or CLI access.
8. Leave PayPal disabled and Sandbox selected. Enter Sandbox credentials, save them, and run the connection test.
9. Assign and copy each published course checkout link. Test one controlled Sandbox purchase, webhook, enrolment, cancellation, and refund.
10. Review Admin Orders and Audit Logs. Confirm no credentials appear in page source, responses, logs, or audit payloads.
11. Live activation requires a separate approval after real Live credentials, Live Webhook ID, HTTPS delivery, backup, and Sandbox acceptance are confirmed.

## Rollback

Preferred rollback: enable maintenance mode, restore the pre-release database export, restore both prior code archives, preserve the prior `.env`, clear caches, and verify login plus course access.

The reviewed SQL rollback file is `deployment/phpmyadmin/2026_10_18_final_payment_phase_rollback.sql`. It is destructive and removes encrypted gateway settings and access-policy data, so use it only before any production payment or entitlement change. A database restore is safer after production use.

## Acceptance checklist

- [x] Encrypted Sandbox configuration
- [x] Encrypted Live configuration isolated from Sandbox
- [x] Invalid-credential connection failure is safe
- [x] Missing active Webhook ID fails closed
- [x] Server-authoritative Sandbox checkout and capture covered by automated tests
- [x] Cancelled and denied payments do not enrol
- [x] Duplicate notification is idempotent
- [x] Full and partial refund behavior covered
- [x] Chargeback/dispute state and entitlement suspension covered
- [x] Expired entitlement and renewal behavior covered
- [x] Joomla-button direct checkout and authentication return covered
- [x] Admin transaction status visible
- [x] Required migrations and reviewed phpMyAdmin SQL included
- [x] Rollback steps included
- [ ] Real Sandbox credentials and externally reachable webhook tested on the production host
- [ ] cPanel archive upload and production migration executed
- [ ] PayPal Live credentials entered and connection tested
- [ ] PayPal Live activation approved

## Production status

**Prepared, not deployed. PayPal Live is not activated.** The remaining blockers are real merchant credentials, host-level Sandbox acceptance, verified HTTPS webhook delivery from PayPal, a fresh production database backup, and explicit approval for Live activation.
