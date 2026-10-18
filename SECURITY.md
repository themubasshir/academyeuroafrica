# Security

## Authentication
- Primary: Joomla bridge SSO. Single-use, 60s-TTL authorisation codes exchanged server-to-server over HMAC-signed HTTPS calls. `state` nonce bound to the academy session cookie stops CSRF/account-substitution. Codes hashed at rest, burned on use.
- Local login: throttled (`throttle:5,1`), session regenerated on auth, `password` hashed (bcrypt, `BCRYPT_ROUNDS=12`).
- Session: secure+HttpOnly+SameSite=lax cookies. `EnsureUserIsActive` middleware logs out disabled accounts on every request.

## Authorisation
- Every privileged area behind `auth`+`active`+`role:*` middleware. Per-model access via Policies (wired in from Phase 2).
- `super-admin` gets `Gate::before` allow-all; everything else denied by default.
- IDOR: policies + scoped queries; cross-student access attempts covered by tests.

## Payments (PayPal, Phase 10)
- Server-side totals from `course_variants`. Browser return/cancel never grants access — only a verified capture or verified webhook does.
- `payment_events(gateway, gateway_event_id)` unique → idempotent fulfilment; `enrolments(user,variant)` unique → no duplicate access.
- Webhook authenticity via PayPal `verify-webhook-signature` (never HMAC-with-secret). Capture integrity checked: status COMPLETED, amount, currency, order reference, capture id.
- `PayPal-Request-Id` idempotency on order create/capture/refund. Refunds require an accepted provider status before persistence; completed full refunds suspend only enrolments linked to that order. Partial refunds preserve access by policy. Client secrets remain server-side and never enter browser payloads or bundles.
- Sandbox and Live credentials are encrypted separately with Laravel's application encryption. Saved values are never hydrated into the administrator form or written to audit payloads. The active environment changes only through an explicit administrator save; invalid or incomplete configuration fails closed.

## Files
- Uploads → private disk, randomised names, MIME+size validation. Served only via controller after auth check. Never placed under public web root; PHP execution impossible.
- Primary lesson PDFs and MP4/WebM videos use the same private disk. Inline PDF and byte-range video endpoints recheck active enrolment or staff assignment on every request and send `nosniff`; opening a PDF never records completion.
- Hosted lesson video embeds accept only YouTube and Vimeo identifiers/hosts. Rich lesson HTML is restricted to a small formatting allowlist with attributes removed.

## Transport & headers
- HTTPS only in prod (`SESSION_SECURE_COOKIE`, force HTTPS via host). Add CSP/X-Frame-Options/Referrer-Policy in Phase 8.

## Deployment runner
- The migration runner accepts only a fixed command allowlist, requires an IP allowlist, a one-time token, and a future `expires_at` value, and uses a distributed execution lock. Successful execution deletes the token and writes a command completion marker. Remove the route and token after deployment synchronisation; never expose the token or source archive through public storage.

## Audit
- `audit_logs` records actor, action, subject, payload, IP for sensitive events (logins, provisioning, payments, refunds, enrolment changes).

## Data minimisation
- Meeting links stored encrypted, shown only to eligible users.
- No payment credentials, passwords, or secrets in code; all via `.env` (never committed).
