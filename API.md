# API

The app is Livewire-rendered; the only true HTTP APIs are the inbound
payment-webhook and Joomla-bridge surfaces.

## Payment webhook
`POST /webhooks/{gateway}` — `{gateway}` = `stripe` (prod) or `test` (local only).

- No auth/CSRF; verified per-gateway signature.
- Stripe: `Stripe-Signature` header, HMAC-SHA256 over `t.payload`, 5-min window.
- Test: `X-Test-Signature`, HMAC-SHA256 over raw body (local adapter only).
- Response: `200 {"received":true}` · `400 {"error":"invalid signature"}`
- Idempotent: duplicate `gateway_event_id` is a no-op.

## Joomla bridge consume
`GET /auth/bridge/consume?code={64hex}&state={64hex}`

- `state` must equal the session's `bridge_state` (CSRF/account-substitution guard).
- Exchanges `code` server-to-server via `BridgeClient` (HMAC-signed) → logs in mapped user.
- Throttled `10/min`.

## Bridge exchange (Joomla side — implemented by plugin)
`POST <joomla>/index.php?option=com_eamnbridge&task=exchange`

Headers: `X-EAMN-Client`, `X-EAMN-Timestamp`, `X-EAMN-Signature` (HMAC over `ts\nbody`).
Body: `{"code":"…"}` → `200 {sub,email,name,groups}` or `401`.

## Health
`GET /up` — Laravel health endpoint.
