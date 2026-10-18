# PHASE17B — Credential Rotation and Enrolment Bridge Readiness

## Scope and stop line

Phase 17B code and tests completed locally. No Joomla Bridge installation, no
production credential rotation, no Joomla sales-page edits, no course
publication, and no PayPal Live activation occurred.

Production credential changes require operator confirmation immediately before
each individual rotation. Replacement values must be supplied through an
approved private channel; never chat, Git, reports, logs, or diagnostic output.

## 1. Credential rotation status

### Status

```text
Joomla DB credentials       NOT ROTATED — confirmed compromised
Joomla application secret   NOT ROTATED — operator confirmation required
Joomla SMTP credentials     NOT ROTATED — confirmed compromised
Cleanup-script key          NOT ROTATED/REMOVED — operator action required
cPanel/FTP credentials      NOT ROTATED — exposure cannot be ruled out
Academy credentials         NOT CHANGED — no Joomla backup value used
```

Phase 17A comparison showed `configuration.bak.php` and live
`configuration.php` contain identical current Joomla DB username/password and
SMTP username/password. Treat both credential pairs as compromised. The
backup's Joomla `secret` differs from live, but the exposed historical value
still requires replacement because it may have been used for session/CSRF
material during its exposure window.

### Dependency-aware execution plan

Do not run these steps until the operator confirms each step separately.

#### Step 1 — Joomla DB password

Authoritative provider: cPanel → MySQL Databases (Joomla database user).

Consumers:

- Joomla live `configuration.php`: `$user`, `$password`.
- Joomla database connection only; no Academy application config uses this
  Joomla DB credential.
- The JPA archive and `configuration.bak.php` are evidence copies, not runtime
  consumers; do not edit or reuse them.

Execution: create replacement DB password in cPanel, update Joomla
`configuration.php` through the private server file path, then test `/`, one
course page, and `/administrator/`. Confirm DB-backed login/page rendering
before proceeding.

#### Step 2 — Joomla application `secret`

Authoritative consumer: Joomla 5.4.9 `configuration.php` `$secret`.

Compatibility: Joomla 5.4.9 reads this value as application cryptographic
secret. Replacing it is compatible with Joomla 5.4.9, but invalidates existing
Joomla sessions and CSRF tokens. It does not configure the Academy Bridge HMAC
secret; keep these secrets distinct.

Execution: generate a new high-entropy value privately, update `$secret`,
clear Joomla cache if required by the hosting panel, then test fresh Joomla
login, logout, administrator login, and one course page. Expect existing users
to re-authenticate.

#### Step 3 — Joomla SMTP password

Authoritative provider: mail provider/cPanel mailbox for existing Joomla SMTP
user.

Consumers:

- Joomla live `configuration.php`: SMTP username/password and SMTP transport
  settings.
- Academy Laravel SMTP is a separate configuration and is not changed by this
  rotation; its successful delivery test remains valid.

Execution: change mailbox password at provider, update Joomla
`configuration.php`, send one controlled Joomla test message to an approved
recipient, verify delivery, then remove any temporary diagnostic output.

#### Step 4 — exposed cleanup key and scripts

`malware-cleanup.php` was HTTP-denied during Phase 16I. Preferred action:
remove it from the public document root after operator confirms no dependency.
If retention is required, replace its embedded key and move it outside the web
root with owner-only permissions. Do not expose replacement key in any report.

#### Step 5 — cPanel/FTP credentials

Authoritative provider: hosting control panel. Rotate after Steps 1–4 if prior
access to public backups/configuration cannot be ruled out. Recreate private
FTPS connection material locally; do not commit it.

### Verification gate

After each step record only: timestamp, provider action, affected service,
HTTP/application smoke result, mail result where applicable, and operator
confirmation. Record no secret values.

## 2. Academy-initiated enrolment SSO repair

Implemented locally:

- `GET /auth/bridge/package?path=/courses/...` resolves the normalized Joomla
  page path through `joomla_page_mappings`.
- A mapping with `course_variant_id` selects only that server-side variant.
- A programme mapping may select an explicitly supplied variant only when that
  variant belongs to the mapped programme.
- Unknown mappings, missing associations, mismatched programme/variant pairs,
  draft variants, unapproved variants, missing prices, unpublished courses,
  and non-purchasable variants return 404.
- Selected variant ID, mapping ID, and ten-minute expiry persist in the session;
  URL price, role, and checkout values are never trusted.
- `url.intended` stores Laravel-generated checkout URL from the validated
  variant, never a caller-supplied URL.
- Unauthenticated users redirect to Academy `bridge.start`; authenticated
  active users go directly to checkout.
- `BridgeStartController` revalidates the session intent against current DB
  purchasability before creating SSO state.
- Bridge state expires after ten minutes and is removed on consume.
- Successful identity exchange clears the one-time package intent after session
  regeneration.
- Existing admin/trainer restrictions remain unchanged. Joomla claims create
  new users as students only; mapped Academy admins remain refused by
  `BridgeAuth`.

Relevant locations:

- `app/Http/Controllers/Auth/BridgePackageController.php`
- `app/Http/Controllers/Auth/BridgeStartController.php:29-49`
- `app/Http/Controllers/Auth/BridgeConsumeController.php:29-66`
- `routes/web.php:76-84`
- `app/Models/CourseVariant.php:39-46`
- `app/Domain/Checkout/OrderService.php:41-56`

Required Joomla sales-page shape after production review:

```text
https://academy.euroafricamedia.eu/auth/bridge/package?path=/courses/<approved-path>
```

Do not use the broken direct Joomla `task=go` entry. Do not edit the seven
Joomla pages in this phase.

## 3. Automated test results

Added bridge feature coverage for:

- Package preserved through unauthenticated SSO intent.
- Unknown mapping rejected.
- Draft package rejected.
- Expired state rejected.
- Replayed state rejected.
- Existing identity mapping respected.
- Email collision does not auto-merge.
- Joomla group claims cannot elevate a newly provisioned user.
- Existing Academy admin identity refused.
- Existing lifecycle disable event behavior.

Results:

```text
BridgeFlowTest: 15 passed, 33 assertions
Full suite:     187 passed, 393 assertions
Pint:           passed
Diff check:     passed
```

No real checkout was activated. `CheckoutController` and `OrderService` still
read current `CourseVariant` pricing server-side; `OrderService::create`
rejects non-purchasable variants before creating order items.

## 4. Backup restore readiness

### Provisioning instructions

No isolated database was provisioned in this session. No production database
was touched.

Operator procedure:

1. In cPanel, create disposable database `euroafri_academy_restore`.
2. Create a dedicated disposable DB user with privileges only on that database.
3. Upload `academy-20261017-163136.sql` outside both public document roots.
4. Import through phpMyAdmin into only disposable database.
5. Verify counts below.
6. Verify status/approval safety.
7. Drop disposable database and user after evidence capture.

Expected verification SQL:

```sql
SELECT COUNT(*) FROM programmes;       -- 5
SELECT COUNT(*) FROM courses;          -- 7
SELECT COUNT(*) FROM course_modules;   -- 34
SELECT COUNT(*) FROM lessons;          -- 137
SELECT COUNT(*) FROM quizzes;          -- 57
SELECT COUNT(*) FROM materials;        -- 23
SELECT COUNT(*) FROM course_variants;  -- 17
SELECT status, COUNT(*) FROM courses GROUP BY status;
SELECT approval_status, COUNT(*) FROM course_variants GROUP BY approval_status;
```

Expected catalogue state: all 7 courses draft; all 17 variants pending; zero
purchasable. Restore result remains **pending** until cPanel disposable DB
exists. Do not use `euroafri_academycli` or any Joomla DB.

## 5. Joomla lifecycle integration audit

Academy endpoint already supports signed lifecycle events at
`app/Http/Controllers/Auth/BridgeEventsController.php:21-52`:

- `disabled` and `deleted` set mapped Academy account status to `disabled`.
- Unknown Joomla subject is a signed no-op.
- `email_changed` is intentionally not applied; recovery email remains local.
- Timestamp window is five minutes; HMAC covers `timestamp + newline + body`.

Joomla component package currently has no lifecycle emitter plugin. The
component's `exchange` checks Joomla `block` at
`joomla/eamn-academy-bridge/component/src/BridgeHelper.php:117-123`, so a blocked
Joomla account cannot complete a new exchange, but existing Academy sessions
need event propagation or Academy active middleware to terminate them on next
request.

Conclusion: a Joomla lifecycle plugin is required for disable/delete
synchronization. Not implemented or deployed in Phase 17B. It requires a
staging Joomla install, local plugin tests, signed event fixture tests, retry
policy, and production review before deployment.

## 6. Remaining production blockers

- Operator confirmation still required before each DB, secret, SMTP, cleanup,
  and cPanel/FTP rotation.
- Replacement credentials not supplied through secure channel.
- Disposable MySQL restore not provisioned or verified.
- `JOOMLA_BRIDGE_SECRET` remains unset on Academy and Joomla.
- Joomla Bridge not installed; Joomla 5.4.9 staging test pending.
- Lifecycle emitter plugin absent.
- Seven Joomla sales pages remain unchanged.
- `joomla_page_mappings` production associations require operator review.
- Courses remain unpublished; variants remain non-purchasable.
- PayPal Live remains disabled.

## 7. Required operator decisions

1. Confirm **Step 1 only**: Joomla DB password rotation through cPanel,
   followed by live Joomla smoke checks.
2. After Step 1 verification, confirm Step 2: Joomla application secret
   rotation, acknowledging forced session/CSRF invalidation.
3. After Step 2 verification, confirm Step 3: Joomla SMTP password rotation and
   controlled mail test.
4. Provision disposable restore DB and return only table/count results, never
   credentials.
5. Review package-entry URL and mapping associations before any Joomla page
   edit.

No production rotation or deployment occurs without the relevant confirmation.
