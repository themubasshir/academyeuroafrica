# Phase 15F — Production deployment preparation

Prepared 2026-10-17 against commit `8bad6b7` + this change. **Nothing on
production was executed.** Joomla untouched, PayPal stays `sandbox`, no SSH used.

The detailed runbook lives in `DEPLOYMENT_INTERSERVER.md`; this file records the
concrete build artefacts and the defects found and fixed while preparing them.

## 1. Deployment archives

Built by `./build.sh` → `dist/`:

| Archive | Size | SHA-256 |
|---|---|---|
| `eamn-academy-app-20261017-143226.tar.gz` | 12 MB | `afcfdaa03c54f26dd8dd35fda8cbb3dedfb75c76675ab8c82b8f71afdf6522ae` |
| `eamn-academy-public-20261017-143226.tar.gz` | 2.1 MB | `8476d5cad54ae4121bbe65330be4d985d777b3a40b047e8e7bbda99fbbc5a04f` |

Manifest with per-file checksums: `eamn-academy-20261017-143226.manifest.txt`.

## 2. Archive contents

- **App** → `/home/euroafri/eamn-academy/` (private, outside web root):
  `app/`, `bootstrap/`, `config/` (incl. new `deploy.php`), `database/`
  (all 16 migrations), `resources/`, `routes/`, `vendor/` (prod deps only),
  `artisan`, `composer.*`, `.env.production.example`, `storage/` runtime dirs
  (`framework/{cache/data,cache/locks,views,sessions}`, `logs`,
  `app/{private,public}`) with `.gitkeep`.
- **Public** → `academy.euroafricamedia.eu` docroot (flat):
  `index.php`, `.htaccess`, `build/` (Vite manifest + hashed assets + fonts),
  `brand/`, `favicon.ico`, `robots.txt`, **`vendor/livewire/`** — the CSP-safe
  `livewire.csp.js`/`livewire.csp.min.js` runtime.
- **Excluded**: `.env*`, `database/*.sqlite`, `tests/`, `phpunit.xml`,
  `node_modules`, `.git`, `storage/qa`, `acceptance-screenshots`, dev/agent
  docs, `hot`, `deploy.*` markers.

## 3. Defects found and fixed in this stage

| # | Defect | Fix |
|---|---|---|
| D1 | Public archive had **no `vendor/livewire/`** → prod Livewire dead despite 15E fix (page `@livewireScripts` serves static `/vendor/livewire/livewire.csp.js`). | `build.sh` now runs `livewire:publish --assets` before staging and asserts the CSP file exists. |
| D2 | `find … -name X -o -name Y -delete` precedence bug — `-delete` bound only to the last pattern, so `*.sqlite`/`hot`/`deploy.token` were **never deleted**; `database/database.sqlite` shipped to prod. | Explicit `-type f \( … \) -delete` groups; added `database/*.sqlite*`, `storage/qa`, `acceptance-screenshots` to excludes; hard `FATAL` asserts on `.env`/sqlite/livewire files. |
| D3 | `.env.production.example` had `DB_CONNECTION=sqlite` and a **duplicate** `APP_BASE_PATH` (commented hint matched `grep -q` so the live line was appended under it). | `sed` flips DB to `mysql` + real host defaults; `^APP_BASE_PATH=` now matches only uncommented lines and the stale comment is deleted; `FATAL` asserts enforce `APP_ENV=production`, `APP_DEBUG=false`, `DB_CONNECTION=mysql`, single `APP_BASE_PATH`. |
| D4 | Runner `Cache::lock` used default `database` store → `cache_locks` doesn't exist pre-migrate → lock threw on the very first call. | `Cache::store('file')->lock(...)` — file store creates its own dir. |
| D5 | Runner read `env('DEPLOY_ALLOWED_IPS')` → returns `''` after `config:cache` → 404s. | `config('deploy.allowed_ips')` via new `config/deploy.php`. |
| D6 | `--flag`/`--value` params were passed through unfiltered → could smuggle options into allowed commands. | Per-command `ALLOWED_PARAMS` whitelist; free-form values rejected. |
| D7 | No way to provision a production admin or drain the `database` queue without SSH/ Supervisor. | New `academy:provision-admin` (reads email from `storage/app/deploy.admin`, never the request) + `queue:work`/`queue:retry` in the allowlist; `bootstrap/app.php` `withSchedule` drains the queue every minute with `withoutOverlapping`. |

## 4. Required migrations

16 migrations → **55 tables**. MySQL-clean (no raw SQL/PRAGMA; largest
composite index ~2 KB < 3072 B InnoDB limit). See `DEPLOYMENT_INTERSERVER.md`
for the preflight checklist and full ordered inventory.

## 5. No-SSH migration instructions

Gated one-time runner `POST /deploy/migrate`: IP allowlist + one-time token +
future `expires_at` + file lock + per-command done-marker. See
`DEPLOYMENT_INTERSERVER.md` — each of `migrate --force`,
`db:seed --class=RoleSeeder --force`, `catalogue:import`, `catalogue:variants`,
`academy:provision-admin` needs a fresh `storage/app/deploy.token`.

## 6. Course import instructions

Source `.rar` extracted with `bsdtar`, uploaded to
`storage/app/import-source/` (outside web root), then run via the gated runner.
Verified dry-run on the real source: **7 courses · 34 modules · 137 lessons ·
57 quizzes · 23 materials · 5 programmes · 17 variants**, all `draft`/`pending`,
idempotent, never publishes.

## 7. Cron & email

- One cPanel cron line: `php …/artisan schedule:run` every minute. The scheduler
  owns `queue:work --stop-when-empty --max-time=50` under a `withoutOverlapping`
  mutex — **do not add a second queue worker**.
- `MAIL_MAILER=log` default; SMTP config + verification steps in
  `DEPLOYMENT_INTERSERVER.md`. `StudentAccountNotification` is queued with
  `afterCommit`, so an SMTP failure lands in `failed_jobs` without breaking the
  page or the in-app `notifications` record.

## 8. Rollback plan

cPanel/phpMyAdmin DB export before each release + keep the prior two archives.
Rollback = re-extract both archives into the two dirs and restore the DB dump.
No code path writes to Joomla or production until `.env` is in place.

## 9. Production verification checklist

13-step browser checklist in `DEPLOYMENT_INTERSERVER.md` (livewire runtime gate,
login, admin, courses, registration+verify, trainer, student, quizzes,
certificates, file authz, responsive, no-local-surface).

## 10. Remaining blockers

- Real PayPal sandbox creds + reachable webhook (checkout/capture/refund
  unverified); `PAYPAL_MODE=sandbox`, all offers unpublished.
- SMTP mailbox creds; registration verification unverified on host.
- `.rar` upload + on-host import not yet executed.
- Real admin email/name needed before `academy:provision-admin` runs.
