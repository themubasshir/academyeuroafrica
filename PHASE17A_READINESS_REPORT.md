# PHASE17A — Readiness Report

## Scope

Readiness assessment only. No unapproved production changes were made. No Bridge
installation, no credential rotation, no Joomla page edits, no restores into
either production app.

## 1. Credential rotation plan

### Bak-vs-live comparison

`configuration.bak.php` was compared field-by-field against the live
`configuration.php`. Values were compared only; none are reproduced here.

```text
user:       SAME (live len=16, bak len=16)
password:   SAME (live len=16, bak len=16)
secret:     DIFFERENT (live len=32, bak len=32)
smtpuser:   SAME (live len=24, bak len=24)
smtppass:   SAME (live len=16, bak len=16)
db:         SAME
host:       SAME
dbprefix:   SAME
mailfrom:   SAME
smtphost:   SAME
smtpport:   SAME
```

The exposed `configuration.bak.php` therefore contains the **current, live**
Joomla database user/password and SMTP mailbox credentials. Only the Joomla
`secret` was regenerated since the bak was written. Prior public exposure means
those live credentials must be treated as compromised.

### Rotation checklist (requires operator approval + supplied replacements)

Order matters; rotate in this sequence to avoid locking out the live site:

1. **Joomla DB user password** — change in cPanel MySQL, then update
   `configuration.php` `$user`/`$password` in the same maintenance window.
   Test `/` and `/administrator/` immediately after.
2. **Joomla `secret`** — regenerate (32-char random). Invalidates all existing
   session/CSRF tokens; users must re-login. Safe to do any time.
3. **SMTP mailbox password** (`smtpuser`/`smtppass`, `order@…` mailbox) —
   change at mail provider, then update `configuration.php`. Test outbound
   mail from Joomla after.
4. **`malware-cleanup.php` embedded key** — file is now HTTP-denied; rotate or
   better, delete the script once operator confirms no dependency.
5. **cPanel/FTP credentials** — rotate if prior download of the exposed files
   cannot be ruled out (it cannot; see §3 log limitation).
6. **Academy DB credentials** — not present in exposed Joomla files, but rotate
   if the JPA archive's exposure window overlaps Academy deployment history.

Any value formerly inside `configuration.bak.php` or the JPA archive that is
still live must be replaced. Do not post replacement values in chat; transfer
via cPanel or the private application tree only.

## 2. Durable forensic storage

Forensic copy moved off `/tmp` to durable private storage:

```text
~/.local/share/eamn-joomla-forensics-20261017/
permissions: 700 (owner-only)
files/:      go-rwx throughout
size:        ~806 MB (includes 839,109,646-byte JPA)
```

`SHA256SUMS` was rewritten to relative paths and re-verified at the new
location — all 10 files `OK`, including:

```text
configuration.bak.php  222db9cce2f26212a324e841fabe571d4266048d04504dee3b2f0b20b8112096
error_log              2c9b9ba3b7dbe46e78060715a3de60ca3ba82a3b00445723d10fcde478144eae
JPA archive            2a3a78af1b25af3c52dc7a872e841591f79ebeafa7a9a80b1945c9d25e8c1e4a
.htaccess (before)     497b60b95e715da6febb1e19c00b410e16ff462290b8fc3e6dc83db2e41d21a5
```

The `/tmp` copy remains until this report is committed; it can then be removed.
Forensic material is not in Git and must not be committed.

## 3. Isolated restore validation

Still blocked. No local `mysql`, `mysqld`, `mariadb`, or `docker` binary exists
on this machine; no disposable MySQL instance is provisioned on the host.

Acceptable target (unchanged from 16I): a cPanel disposable database, e.g.
`euroafri_academy_restore`, with a dedicated user scoped only to that database.
Restore procedure and verification queries are in
`PHASE16I_ACCEPTANCE_REPORT.md` §2 — expected counts: 5 programmes, 7 courses,
34 modules, 137 lessons, 57 quizzes, 23 materials, 17 variants; all courses
`draft`, all variants `pending`.

Dumps validated by structure + footer + hash, not by restore:

```text
academy-20261017-154734.sql  79,029 B   sha 2d81b4dd…16a2   56 tables
academy-20261017-163136.sql  479,620 B  sha cd2c6449…798a   56 tables
```

Do not restore into `euroafri_academycli` or the Joomla database.

## 4. Academy acceptance checklist (operator, authenticated)

Unchanged from `PHASE16I_ACCEPTANCE_REPORT.md` §1. Operator performs these in an
authorized browser; no credentials in chat. Summary of pending checks:

- `/admin/dashboard` loads; sidebar renders; counts match catalogue status.
- `/admin/programmes` → 5; `/admin/courses` → 7 draft; one course per programme
  spot-checked for attached modules/lessons/quizzes/materials.
- `/admin/packages` → exactly 17 variants, all draft/pending, 0 purchasable.
- One private material URL: authorized session opens it; unauthenticated
  request redirects/denies.
- Catalogue remains 7 draft / 0 published courses, 17 pending / 0 purchasable
  variants; no public checkout link for draft variants.

## 5. Bridge installation blockers

Package `dist/com_eamnbridge.zip` (sha `60701c5f…50ec`) is built, lint-clean,
manifest-valid. Installed Joomla version confirmed: **5.4.9** (manifest
`administrator/manifests/files/joomla.xml`, `libraries/src/Version.php`).
Component APIs used (`Factory`, `Input`, `UserFactoryInterface`, `Registry`)
exist in the Joomla 5 tree; manifest declares Joomla 4 target — treat as
compatible pending staging install, since Joomla 5 retains these APIs.

Not installable/activatable yet. Blockers:

### 5.1 No shared secret

`JOOMLA_BRIDGE_SECRET` is unset in Academy `.env`; Joomla-side `bridge_secret`
unset. `BridgeHelper::handleExchange` throws 503 without it
(`joomla/eamn-academy-bridge/component/src/BridgeHelper.php:89`).

### 5.2 Enrolment-redirect gap (design vs README)

The README's suggested purchase-button link

```text
index.php?option=com_eamnbridge&task=go&target=programme&id=<ID>
```

cannot work as written: `handleGo` requires a 64-hex `state`
(`BridgeHelper.php:41-44`) and throws 400 without it. Only the Academy-side
`BridgeStartController` mints `state`
(`app/Http/Controllers/Auth/BridgeStartController.php:29`) — but it drops
`target`/`id` from the Joomla redirect (`BridgeStartController.php:38-42`)
and its allowlist (`BridgeStartController.php:21-25`) covers only
`/dashboard`, `/trainer/dashboard`, `/admin/dashboard`. No programme/variant
target exists.

Second half of the gap: `BridgeConsumeController` validates `code`+`state`
then ignores `target`/`id` entirely — landing is `redirect()->intended()`
against session `url.intended` or the role dashboard
(`BridgeConsumeController.php:60-64`). Even if a code carried
`target=programme&id=N`, the user lands on their dashboard, not the picker or
checkout.

So today: login SSO supported (Academy-initiated only); enrolment/course
redirects unsupported. `joomla_page_mappings` table exists but nothing reads
it during the bridge flow; `Admin\Integrations` only lists rows.

Resolution options (pick before install):

- **A. Joomla-initiated with state passthrough.** Joomla `go` generates its own
  state, stores it in the Joomla session, includes it in the consume redirect,
  and Academy verifies it via the exchange response instead of `session
  .bridge_state`. Weakens the CSRF binding unless the state is echoed through
  the signed exchange payload; requires Academy+component changes.
- **B. Academy-initiated per page (recommended, smallest change).** Sales-page
  buttons link to `https://academy…/auth/bridge/start?target=/courses/<slug>`
  instead of Joomla `task=go`. Extend `ALLOWED_TARGETS` (or resolve via
  `joomla_page_mappings` by path) so `url.intended` becomes the
  programme-picker/checkout route. `handleGo` unchanged; state binding stays
  intact. Consume already honours `url.intended`.
- **C. Map on consume.** Extend consume to accept `target`/`id`, look up
  `joomla_page_mappings`, redirect to picker/checkout. Still needs the Joomla
  `state` problem solved (option A) or Academy-side start (option B).

Option B preserves the existing CSRF/account-substitution binding and needs no
component change — only an Academy-side target allowlist/mapping lookup plus
button URLs on the 7 pages.

### 5.3 Untested on live Joomla

11 Academy feature tests pass against a faked exchange; the component has never
run inside Joomla. Staging install required before production — see 16I §6
activation procedure.

### 5.4 Lifecycle emitter not wired

`BridgeEventsController` accepts signed `disabled|deleted|email_changed`
events, but no Joomla-side plugin pushes them. Account disable/delete on
Joomla will not propagate until an emitter plugin exists (JOOMLA_INTEGRATION.md
"Outstanding"). Login-side `block` check at `BridgeHelper.php:121` does cover
exchange-time.

### 5.5 Other install prerequisites

- Joomla backup + verified restore before install (currently unverifiable,
  §3).
- Credential rotation first (§1) — installing a new component while known
  credentials are compromised is not advisable.
- `JOOMLA_BASE_URL`, `JOOMLA_CLIENT_ID` unset (defaults); confirm values before
  enablement.

## 6. Status summary

```text
Credential comparison        DONE — bak holds live DB + SMTP creds; secret differs
Forensics durable storage    DONE — ~/.local/share/…, SHA256 re-verified, 700
Isolated restore             BLOCKED — no disposable MySQL target
Academy acceptance           PENDING — operator manual checklist (16I §1)
Bridge package               READY — not installed
Bridge secret                NOT SET — either side
Bridge enrolment redirects   GAP — state requirement + ignored target/id
Joomla version               5.4.9 (component declares J4; APIs present in J5)
```

## 7. Recommended order of operations

1. Operator approves and supplies replacement credentials (§1); rotate in the
   listed order.
2. Provision disposable MySQL database; run isolated restore validation (§3).
3. Decide redirect design — recommend option B (§5.2); implement Academy-side
   mapping lookup.
4. Staging Joomla install of `com_eamnbridge`; configure shared secret;
   end-to-end `go`/`exchange`/`events` test.
5. Operator completes Academy acceptance checklist.
6. Only then: production install, wire lifecycle emitter, map the 7 pages,
   publish course offers.
