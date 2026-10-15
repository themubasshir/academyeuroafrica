# Security

## Authentication
- Primary: Joomla bridge SSO. Single-use, 60s-TTL authorisation codes exchanged server-to-server over HMAC-signed HTTPS calls. `state` nonce bound to the academy session cookie stops CSRF/account-substitution. Codes hashed at rest, burned on use.
- Local login: throttled (`throttle:5,1`), session regenerated on auth, `password` hashed (bcrypt, `BCRYPT_ROUNDS=12`).
- Session: secure+HttpOnly+SameSite=lax cookies. `EnsureUserIsActive` middleware logs out disabled accounts on every request.

## Authorisation
- Every privileged area behind `auth`+`active`+`role:*` middleware. Per-model access via Policies (wired in from Phase 2).
- `super-admin` gets `Gate::before` allow-all; everything else denied by default.
- IDOR: policies + scoped queries; cross-student access attempts covered by tests.

## Payments (Phase 3)
- Server-side totals from `course_variants`. Browser success URL never grants access — only verified webhook fulfilment does.
- `payment_events.provider_event_id` unique → idempotent processing. Signatures verified per-gateway. No card data stored.

## Files
- Uploads → private disk, randomised names, MIME+size validation. Served only via controller after auth check. Never placed under public web root; PHP execution impossible.

## Transport & headers
- HTTPS only in prod (`SESSION_SECURE_COOKIE`, force HTTPS via host). Add CSP/X-Frame-Options/Referrer-Policy in Phase 8.

## Audit
- `audit_logs` records actor, action, subject, payload, IP for sensitive events (logins, provisioning, payments, refunds, enrolment changes).

## Data minimisation
- Meeting links stored encrypted, shown only to eligible users.
- No payment credentials, passwords, or secrets in code; all via `.env` (never committed).
