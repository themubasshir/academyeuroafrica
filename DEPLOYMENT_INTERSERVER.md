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
   ```
   Generate `APP_KEY` on host via `php artisan key:generate` (run from app dir).
5. Create `storage/app/deploy.token` with a random one-time secret for the migration runner. Keep it in private `storage/app`; never place it in the web root.
6. `POST https://academy.euroafricamedia.eu/deploy/migrate` with `{token, expires_at, run:"migrate --force"}`, where `expires_at` is an ISO-8601 timestamp in the near future. The request must originate from `DEPLOY_ALLOWED_IPS`; a successful run consumes the token and writes its command marker.
7. For each additional allowed command, create a **new** one-time token and make a separate non-expired request, for example `{token, expires_at, run:"db:seed --class=RoleSeeder --force"}`. Do not delete completion markers to replay commands; use cPanel cron/CLI instead when a command must be deliberately rerun.
8. `php artisan config:cache route:cache view:cache` on host (cron one-shot or probe).
9. Verify `/up`, `/` → `/login`, assets, then PayPal sandbox round-trip.

## Writable dirs on host
`storage/` and `bootstrap/cache/` under the private dir must be writable by the PHP user (`euroafri`). cPanel's `MultiPHP`/`php.ini` user owns them; `chmod 755` is sufficient under suPHP/LSAPI.

## Cron (cPanel → Cron Jobs)
```
* * * * * php /home/euroafri/eamn-academy/artisan schedule:run >> /dev/null 2>&1
* * * * * php /home/euroafri/eamn-academy/artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

## Migration runner (no SSH)
`POST deploy/migrate` is a temporary, fixed-allowlist endpoint. It requires the configured source IP, a one-time token, a future `expires_at` timestamp, and an execution lock. It never accepts arbitrary Artisan input. A successful command consumes `storage/app/deploy.token` and writes `deploy.done.<md5(command)>`; replay is denied. After production synchronisation, remove the token and disable/remove the route in the next release. Never delete completion markers or leave the runner enabled as a general deployment mechanism.

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
