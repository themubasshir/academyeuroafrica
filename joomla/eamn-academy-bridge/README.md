# EAMN Academy Bridge (Joomla 4)

Installable component `com_eamnbridge` for euroafricamedia.eu. Do NOT deploy to
the live site without explicit authorisation.

## What it does
- `task=go` — browser flow: logged-in Joomla user gets a single-use code and is
  redirected to the academy `/auth/bridge/consume` endpoint (SSO).
- `task=exchange` — server-to-server: academy POSTs the code with an
  HMAC-SHA256 signature; returns `{sub,email,name,groups}` once, then burns the code.

## Security
- Codes: 128-bit random, SHA-256 stored, 60s TTL, single-use, bound to user+state.
- Exchange verified via `X-EAMN-Timestamp` + `X-EAMN-Signature` (HMAC over `ts\nbody`), 5-min replay window.
- No passwords or permanent tokens in URLs.
- Disabled (`block=1`) users are rejected.

## Install
1. Zip `component/` contents + `com_eamnbridge.xml` as `com_eamnbridge.zip`.
2. Joomla admin → System → Install → Extensions → upload.
3. Configure component options: Academy Base URL, Shared HMAC Secret (must match
   academy `JOOMLA_BRIDGE_SECRET`), Client ID.
4. Run `admin/install.sql` if the table isn't created by the installer.

## Purchase buttons
On each course page, link the "Purchase Course" button to:
```
index.php?option=com_eamnbridge&task=go&target=programme&id=<ID>
```
Use `target=variant&id=<ID>` when the page maps to one specific product; the
academy routes straight to checkout. Otherwise `target=programme` shows the
programme's course picker.

## Page → product map
| Joomla path | Suggested target |
|---|---|
| /courses/training-awareness-institute | programme |
| /courses/special-german-language-school | programme |
| /courses/training | programme |
| /courses/digital-inclusion-training-for-elderly | programme |
| /courses/start-small-earn-big | programme |
| /courses/business-wealth-academy | programme |
| /courses/no-easy-target™-security-academy | programme |

(`id` values come from the academy's `joomla_page_mappings` admin — set after
programmes/products exist. Do not invent them.)
