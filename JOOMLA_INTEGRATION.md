# Joomla Integration — EAMN Academy Bridge

Joomla 4 cannot act as an OIDC provider without an extra extension, so a separate, audited bridge protocol is used.

## Flow (login)
1. Browser hits `GET /auth/bridge/start` on the academy. Academy generates a `state` nonce, stores it in the session, then redirects the browser to Joomla:
   `index.php?option=com_eamnbridge&task=go&state=…&target=…&id=…`
2. **Joomla `go` task** (`com_eamnbridge` component): if the Joomla user is a guest, it redirects through Joomla login *carrying state/target/id* so the flow survives the round-trip. For a logged-in user it mints a single-use code (128-bit, SHA-256 stored, 60 s TTL) bound to `user_id` + `state`, then redirects to
   `https://academy.euroafricamedia.eu/auth/bridge/consume?code=…&state=…&target=…&id=…`
3. Academy `consume` verifies `state` against `session.bridge_state` (CSRF/account-substitution guard), then calls Joomla **server-to-server**:
   `POST index.php?option=com_eamnbridge&task=exchange`
   with `X-EAMN-Signature` = HMAC-SHA256(`timestamp\nbody`, shared secret) and `X-EAMN-Timestamp` (5-min replay window).
4. Joomla verifies signature, burns the code (single-use delete-before-validate), returns `{sub,email,name,groups}` once.
5. Academy resolves `external_identities(joomla, sub)` → login + session regenerate, honours `url.intended` (same-site paths only) for `target`.

## Lifecycle events
Joomla pushes signed events to `POST /auth/bridge/events` (HMAC-verified, same scheme as exchange, CSRF-exempt):
- `email_changed` → update user email
- `disabled` / `deleted` → set `status=disabled`, `disabled_at=now()`

Unmapped `sub` → `{"ok":true,"matched":false}` (no-op). Academy endpoint implemented (`BridgeEventsController`); the Joomla-side *emitter* plugin still needs to be wired to Joomla user events on a staging install — see **Outstanding**.

## Conflicts
Bridge `sub` unseen but `email` already exists → `identity_conflicts` row written *outside* the user transaction (so it persists when the merge aborts); admin resolves manually. Never auto-merged.

## Purchase links
`joomla_page_mappings` maps Joomla page path → `programme_id` or `course_variant_id`. Direct variant → checkout; else programme picker. Managed via **Admin → Integrations**; seeded from the 7 known course pages.

## Rules
- No passwords or permanent tokens in browser URLs.
- Codes single-use, 60 s TTL, state-bound, hashed at rest.
- Separate databases; no cross-app writes.

## Plugin package
Source: `joomla/eamn-academy-bridge/` — installable Joomla 4 component.
Rebuilt zip: `joomla/com_eamnbridge.zip` (manifest `<files>`/`<install>`/`<uninstall>` paths fixed, `uninstall.sql` added).

## Outstanding — requires staging Joomla
- Install `com_eamnbridge.zip` on a staging Joomla 4 site; confirm `go`/`exchange`/`events` end-to-end.
- Wire the lifecycle *emitter* (plugin events on `onUserAfterSave`/`onUserAfterDelete`/disable) to push signed events to `/auth/bridge/events`.
- Seed the 7 real `joomla_page_mappings` rows.
- Never install on the live site without authorization.
