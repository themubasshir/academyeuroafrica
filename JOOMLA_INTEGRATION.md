# Joomla Integration — EAMN Academy Bridge

Joomla 4 cannot act as an OIDC provider without an extra extension, so a separate, audited bridge protocol is used.

## Flow (login)
1. Joomla-side link hits the **EAMN Academy Bridge** plugin, which mints a single-use authorisation code (128-bit, SHA-256 stored, 60s TTL) bound to the Joomla user + a nonce, and redirects the browser to `https://academy.euroafricamedia.eu/auth/bridge/consume?code=…&state=…`.
2. Academy verifies `state` against the session cookie, then calls Joomla **server-to-server**: `POST /index.php?option=com_eamnbridge&task=exchange` with HMAC-SHA256 signature (`X-EAMN-Signature`, `X-EAMN-Timestamp`).
3. Joomla returns `{sub,email,name,groups}` once, then invalidates the code.
4. Academy resolves user via `external_identities` → login + session regenerate.

## Lifecycle events
Joomla pushes signed events (email change, disable, delete, logout) to the academy `/bridge/events` endpoint (HMAC-verified) → academy updates/disables the mapped user. (Endpoint lands with the plugin in Phase 7.)

## Conflicts
Bridge `sub` unseen but `email` already exists → `identity_conflicts` row; admin resolves. Never auto-merged.

## Purchase links
`joomla_page_mappings` maps Joomla page path → `programme_id` or `course_variant_id`. Specific product → direct checkout; else programme picker. Managed by admins, seeded from the 7 known course pages.

## Rules
- No passwords or permanent tokens in browser URLs.
- Codes single-use, short-lived, state-bound.
- Separate databases; no cross-app writes.

## Plugin
`joomla/eamn-academy-bridge/` (Phase 7) — installable Joomla 4 package with the exchange endpoint, event emitter, and purchase-button mapping UI.
