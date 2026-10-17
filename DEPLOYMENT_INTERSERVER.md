# Deployment — InterServer shared hosting (no SSH)

## Constraints honoured
- No SSH/terminal on prod. No Docker/Redis/Supervisor/Node/WebSockets.
- Composer + Vite run locally; only built artefacts uploaded.
- Subdomain docroot is **fixed** — cannot be repointed. Split layout used.

## Split layout (required)

The `academy.euroafricamedia.eu` docroot is a fixed, chrooted web root and
cannot host the private application. Deploy as two pieces:

| Piece | Contents | Destination |
|---|---|---|
| `eamn-app-<stamp>.tar.gz` | Laravel app **minus** `public/` (app/, bootstrap/, config/, database/, resources/, routes/, storage/, vendor/, artisan, composer.*) + `.env.production.example` | `/home/euroafri/eamn-academy/` — upload + extract via **cPanel File Manager** (not FTP). Must sit **outside** the web root. |
| `eamn-public-<stamp>.tar.gz` | Only `public/` contents: `index.php`, `.htaccess`, `build/`, `brand/`, `favicon.ico`, `robots.txt` | `/home/euroafri/academy.euroafricamedia.eu/` — the **existing** academy docroot (FTP `/`, contains `cgi-bin/`/`php.ini`). |

`public/index.php` resolves the private app path at runtime, in order:
1. `APP_BASE_PATH` env var (`SetEnv` in docroot `.htaccess`, or PHP-FPM env).
2. An `.app-base-path` file placed next to `index.php` containing the path.

…then calls `$app->usePublicPath(__DIR__)` so `public_path()` still points at the
docroot where `brand/`/`build/` live, while `base_path()`/`storage_path()` point
at the private dir.

## Build (local / CI)
`./build.sh` performs, in order:
1. **Preflight** — tools + PHP extensions (`mbstring openssl pdo pdo_mysql curl fileinfo xml ctype json tokenizer zip gd intl bcmath`).
2. `php artisan test` — hard gate; aborts on failure.
3. `composer install --no-dev --prefer-dist --optimize-autoloader`.
4. `npm ci && npm run build`.
5. Stage **two** trees: private app (excludes `public/`, `dist`, `node_modules`, `.git`, `tests`, `.env*`, storage runtime, `phpunit.xml`, `.ai`, `vendor` copied separately) and web root (`public/` flat).
6. `.env.production.example` ships only inside the **app** archive; injects `PAYMENT_GATEWAY=paypal` + a commented `APP_BASE_PATH` hint.
7. `tar` both; manifest with PHP/Laravel versions + SHA-256 of every file + archive checksums.

## Config caching
Do **not** run `config:cache` locally — it bakes local env into the artefact. Run on host **after** creating the real `.env`.

## First deploy
1. `./build.sh` locally → two archives in `dist/`.
2. **cPanel File Manager**: upload `eamn-app-*.tar.gz` to `/home/euroafri/`, extract → produces `/home/euroafri/eamn-academy/` (private).
3. **File Manager or FTP**: upload `eamn-public-*.tar.gz`, extract into `/home/euroafri/academy.euroafricamedia.eu/` (the existing docroot — replaces `index.php`, adds `build/`, `brand/`, `.htaccess`).
4. Create `/home/euroafri/eamn-academy/.env` (never shipped):
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://academy.euroafricamedia.eu
   ACADEMY_URL=https://academy.euroafricamedia.eu
   APP_BASE_PATH=/home/euroafri/eamn-academy
   APP_KEY=<generate on host>
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=euroafri_academycli
   DB_USERNAME=euroafri_academycli
   DB_PASSWORD=<secret>
   SESSION_DRIVER=database
   QUEUE_CONNECTION=database
   CACHE_STORE=database
   PAYMENT_GATEWAY=paypal
   PAYPAL_MODE=sandbox
   PAYPAL_CLIENT_ID=<sandbox id>
   PAYPAL_CLIENT_SECRET=<sandbox secret>
   PAYPAL_WEBHOOK_ID=<sandbox webhook>
   PAYPAL_CURRENCY=EUR
   PAYPAL_BRAND_NAME="EAMN Academy"
   JOOMLA_BASE_URL=https://euroafricamedia.eu
   JOOMLA_CLIENT_ID=eamn-academy
   JOOMLA_BRIDGE_SECRET=<generate>
   DEPLOY_ALLOWED_IPS=<your public IP>

   # Email — cPanel SMTP for the domain. Keep MAIL_MAILER=log until SMTP is verified.
   MAIL_MAILER=smtp
   MAIL_HOST=mail.euroafricamedia.eu
   MAIL_PORT=465
   MAIL_ENCRYPTION=tls
   MAIL_USERNAME=<mailbox>
   MAIL_PASSWORD=<mailbox secret>
   MAIL_FROM_ADDRESS="academy@euroafricamedia.eu"
   MAIL_FROM_NAME="${APP_NAME}"
   ```
   Generate `APP_KEY` on host via `php artisan key:generate` (run from app dir).
5. Create `storage/app/deploy.token` with a random one-time secret for the migration runner. Keep it in private `storage/app`; never place it in the web root.
6. `POST https://academy.euroafricamedia.eu/deploy/migrate` with `{token, expires_at, run:"migrate --force"}`, where `expires_at` is an ISO-8601 timestamp in the near future. The request must originate from `DEPLOY_ALLOWED_IPS`; a successful run consumes the token and writes its command marker.
7. For each additional allowed command, create a **new** one-time token and make a separate non-expired request, for example `{token, expires_at, run:"db:seed --class=RoleSeeder --force"}`. Do not delete completion markers to replay commands; use cPanel cron/CLI instead when a command must be deliberately rerun.
8. `php artisan config:cache route:cache view:cache` on host (cron one-shot or probe).
9. Verify `/up`, `/` → `/login`, assets, then PayPal sandbox round-trip.

## MySQL preflight (run before `migrate --force`)

16 migrations, portable Laravel schema API only — **no raw SQL, no `PRAGMA`, no
SQLite-only syntax**. Verified expectations for MySQL 8 / utf8mb4:

- `DB_CONNECTION=mysql`, charset `utf8mb4`, collation `utf8mb4_unicode_ci`
  (`config/database.php:76-77`).
- Longest composite unique index is `(gateway, gateway_transaction_id)` =
  two varchar(255) → ~2040 bytes, under InnoDB's 3072-byte limit. No index on
  `text`/`longText`. No index name exceeds 64 chars.
- Every FK references a table created earlier in the same or an earlier
  migration; `users`/`cache`/`jobs` run first (`0001_01_01_*`), then
  foundation → academic → commerce → classroom → assessment → private training
  → certification, with the `add_*` migrations last.
- `cascadeOnDelete`/`restrictOnDelete`/`nullOnDelete` are all declared
  explicitly.
- cPanel MySQL often needs a **short** FK/index prefix: if `migrate` fails with
  `errno 150` or `Specified key was too long`, set `DB_COLLATION=utf8mb4_unicode_ci`
  and confirm the DB is InnoDB before re-running.

Expected table inventory (55 tables, migration order):
`users, password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches,
failed_jobs, roles, permissions, permission_role, role_user,
external_identities, identity_conflicts, bridge_auth_codes,
joomla_page_mappings, audit_logs, programmes, courses, course_variants,
course_modules, lessons, course_videos, materials, enrolments,
trainer_assignments, lesson_progress, orders, order_items,
payment_transactions, payment_events, refunds, groups, group_members,
live_sessions, announcements, assignments, assignment_recipients,
assignment_submissions, assignment_attachments, assignment_grades, quizzes,
quiz_questions, quiz_options, quiz_attempts, quiz_answers,
trainer_availability, appointments, session_credit_ledger,
certification_policies, session_attendances, enrolment_approvals, certificates,
certificate_audits, notifications` (+ `migrations` bookkeeping table).

Preflight checklist (File Manager / phpMyAdmin, before the run):
1. DB + user exist in cPanel; user has ALL PRIVILEGES on `euroafri_academycli`.
2. DB charset `utf8mb4`, collation `utf8mb4_unicode_ci`.
3. `.env` has `DB_CONNECTION=mysql` and no `DB_DATABASE=database/database.sqlite`.
4. `storage/` and `bootstrap/cache/` are writable (755 under LSAPI).
5. phpMyAdmin export (empty DB is fine) taken as the rollback point.
6. `migrate --force` run via the gated runner; expect
   `migrations` table with 16 rows (`select count(*) from migrations`).

Do **not** run `migrate:fresh` or `migrate:rollback` on production. There is no
`doctrine/dbal` dependency and none is needed.

## Course catalogue import (Stage 5)

The client source is a `.rar` (`EAMN Academy+Training.rar`) and is **not**
shipped in either archive. Extract and upload the source tree, then import.

1. Extract locally: `bsdtar -xf "EAMN Academy+Training.rar"` (or WinRAR on
   Windows) — must yield `TRAINING-Courses-Academy/` and
   `EAMN COURSES COMPLETE FOLDER/`.
2. Upload the whole extracted folder into
   `/home/euroafri/eamn-academy/storage/app/import-source/` via File Manager
   (or set `IMPORT_SOURCE_ROOT` in `.env` to wherever it lands, **outside** the
   web root).
3. Dry run (writes nothing) via the gated runner: fresh token, then
   `run:"catalogue:import"` — but first validate the source path locally or with
   `IMPORT_SOURCE_ROOT` set, since the runner has no `--host` escape hatch for paths.
   Expected dry-run totals (verified locally against the real source):
   7 courses · 34 modules · 137 lessons · 57 quizzes · 23 materials ·
   5 programmes, 263 rows processed.
4. Real import: fresh token, `run:"catalogue:import"`.
5. Commercial variants: fresh token, `run:"catalogue:variants"` → 17 variants.
6. **Everything stays `status=draft` / `approval_status=pending`.** Nothing is
   published or purchasable. Verify with phpMyAdmin:
   `select count(*) from course_variants where status<>'draft' or approval_status<>'pending'` → 0.

Idempotency: importer uses `firstOrNew` on `slug`/`source_key`; variants use
`firstOrNew(['source_key'])` and are **`locked`** (skipped) once an admin
changes `approval_status` away from `pending`, so a re-run never overwrites
administrator edits. Re-running an import is safe; `MISSING`/`SKIP` lines mean
the source file changed, not data loss.

## Email readiness (Stage 7)

- Ship state: `MAIL_MAILER=log` is the `.env.example` default, so nothing sends
  until SMTP is configured — no accidental bounces or leaked mail.
- `StudentAccountNotification` implements `ShouldQueue` with `afterCommit=true`,
  so notifications are persisted to `notifications` (in-app, always works) and
  the mail channel is queued. **If SMTP is down or `MAIL_MAILER=log`, the job
  still succeeds and the in-app notification still appears**; a hard SMTP
  rejection lands the job in `failed_jobs` (visible, retryable via
  `queue:retry all`) without breaking the user action or the DB transaction.
- Verify SMTP safely: set `MAIL_MAILER=log` first and confirm the notification
  appears in-app; then switch to SMTP and use an admin-triggered notification to
  one controlled mailbox. Never test by mass-notifying real students.
- Registration sends a verification mail. With `MAIL_MAILER=log`, users are
  created but unverified (no bounce); switch to SMTP before opening
  registration to the public. Admin accounts are provisioned pre-verified via
  `academy:provision-admin`, so admin access never depends on mail.
- SPF/DKIM: send from a real mailbox on the domain and keep
  `MAIL_FROM_ADDRESS` on `euroafricamedia.eu` to avoid spam foldering.
- `MAIL_PASSWORD` is a secret: `.env` only, never in the repo, archives, this
  doc, or any command output.

## Production acceptance (Stage 8)

Run in a real browser against `https://academy.euroafricamedia.eu` **after**
migrate + provisioning + import. Requires one admin, and a student created via
public registration (or a second provisioned account).

1. **Livewire runtime (P0 gate)** — open any page, F12 console: `window.Livewire`
   is defined and there are **no** `EvalError`/CSP errors. Network tab shows
   `/vendor/livewire/livewire.csp.js` loading with HTTP 200. If this fails the
   public archive missed `public/vendor/` — re-extract it.
2. **Login** — valid admin creds → dashboard; wrong password → error, no session;
   deactivated user refused.
3. **Admin dashboard** — `/admin/dashboard` loads with real counts, no
   exception text (confirms `APP_DEBUG=false`).
4. **Course management** — create/edit a programme and course; save persists
   after reload; variant edit shows draft+pending; no path publishes an offer.
5. **Student registration + verification** — register a real test student,
   receive the verification mail, verify, log in.
6. **Livewire interactivity** — a mutating action (e.g. mark lesson complete)
   POSTs `/livewire-*/update` → 200 and persists after reload.
7. **Trainer assignment** — assign a trainer to a course as admin; trainer sees
   the course and cannot open an unassigned one (403).
8. **Student course access** — enrolled student opens lessons/quizzes; a
   non-enrolled student is refused.
9. **Quizzes + assignments** — submit, grade as trainer, release grade, student
   sees the result.
10. **Certificates** — issue, download as owner, `/verify/{ref}` works
    unauthenticated, and a non-owner download is refused.
11. **Private file authorization** — `/files/materials/{id}` returns 200 for an
    authorized user and 403 for an unauthorized one; no direct
    `/storage/...` URL resolves.
12. **Responsive** — 390px and 768px: nav drawer opens/closes, no horizontal
    scroll, no oversized text inputs.
13. **No local-only surface** — `/test-checkout/...` 404s; `/deploy/migrate`
    returns 404 once the token is removed.

## Remaining blockers before go-live

- **Real PayPal credentials**: `PAYPAL_CLIENT_ID`/`SECRET`/`WEBHOOK_ID` unset and
  no reachable webhook in sandbox → checkout/capture/refund/webhook unverified.
  Keep `PAYPAL_MODE=sandbox` and all variants unpublished.
- **Email**: SMTP mailbox credentials not yet provided; registration
  verification unverified on-host.
- **Catalogue source**: `.rar` must be uploaded; import executed on host.
- **Admin identity**: real administrator email/name needed before provisioning.
- **`storage/app/public` symlink**: not required (private disk streams through
  the controller); do not run `storage:link` unless a public disk is added.

## Writable dirs on host
`storage/` and `bootstrap/cache/` under the private dir must be writable by the PHP user (`euroafri`). cPanel's `MultiPHP`/`php.ini` user owns them; `chmod 755` is sufficient under suPHP/LSAPI.

## Cron (cPanel → Cron Jobs)
**Exactly one** cron line. The scheduler already drains the queue
(`bootstrap/app.php` → `withSchedule`), holding a `withoutOverlapping` mutex.
Adding a second standalone `queue:work` line would run two workers and can
process the same job twice — do not add it.
```
* * * * * /usr/local/bin/php /home/euroafri/eamn-academy/artisan schedule:run >> /home/euroafri/eamn-academy/storage/logs/cron.log 2>&1
```
Use the cPanel PHP binary path (`php -v` in MultiPHP, usually
`/usr/local/bin/php` or `ea-php83`). The scheduled job runs
`queue:work --stop-when-empty --max-time=50` in the background, so a slow SMTP
send cannot pile up ticks; the mutex (30 min expiry) prevents overlap and the
`--max-time` bound guarantees the worker exits.

Verify after setup:
- `storage/logs/cron.log` shows a `schedule:run` entry each minute.
- `php artisan schedule:list` lists one queue:work entry.
- `select count(*) from failed_jobs` stays 0; `jobs` drains to 0.

## Migration runner (no SSH)
`POST deploy/migrate` is a temporary, fixed-allowlist endpoint. Guard stack
(all required): source IP in `DEPLOY_ALLOWED_IPS`, a one-time token in
`storage/app/deploy.token`, a future `expires_at` timestamp, a **file-store**
execution lock (`Cache::store('file')`, works before `cache_locks` exists), and
a per-command `deploy.done.<md5>` replay guard. Each `--flag`/`--value` is
further whitelisted per command and free-form values are rejected, so the
allowlist can never become arbitrary Artisan input. The IP list is read via
`config('deploy.allowed_ips')` so it survives `config:cache`.

Allowlist: `migrate --force`, `db:seed --class=RoleSeeder --force`,
`catalogue:import`, `catalogue:variants`, `academy:provision-admin`,
`queue:work --stop-when-empty --max-time=60`, `queue:retry all`.

A successful command consumes `deploy.token` (so each command needs a fresh
token) and writes `deploy.done.<md5>`; replay is denied. After synchronisation,
remove the token and the `deploy.migrate` route in a later release. Never
delete completion markers to replay; never leave the runner as a general
deployment mechanism.

### Admin provisioning (one-time, gated)
`academy:provision-admin` reads the admin email from
`storage/app/deploy.admin` (written via File Manager — **not** the HTTP
request, so the route can't mint arbitrary users). It generates a random 20-char
password, prints it once in the gated JSON response, and never stores or mails it.

1. Write the real admin email (only the email) to `storage/app/deploy.admin`.
2. Fresh `deploy.token`, then POST `deploy/migrate` with `run:"academy:provision-admin"`.
3. Capture the one-time password from the response JSON, log in, change it.
4. Delete `deploy.admin`.

Re-run with `--rotate` (CLI/schedule) to reset a locked-out admin.

## Secrets & gateway safety
- Test gateway gated to `local`/`testing` env — routes not registered on prod.
- `PAYPAL_MODE=sandbox` until live cutover; live creds + live `PAYPAL_WEBHOOK_ID` required for live.
- No `.env`, `.git`, tests, `DemoSeeder`, or local DB ship in either archive.

## Backup & rollback
- DB: cPanel/phpMyAdmin export before each release.
- Code: keep prior two archives; rollback = re-extract both + restore DB.
- `storage/framework/down` for maintenance window.

## Never do
- No `.htaccess`-only protection of an exposed full app (split layout removes the need).
- No `storage:link` (private disk streams via controller; no public symlink required).
- No web-based general Artisan runner beyond the gated one-time migrator.
- No `APP_KEY` reuse from local.
