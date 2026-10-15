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
5. Create `storage/app/deploy.token` with a one-time secret for the migration runner.
6. `POST https://academy.euroafricamedia.eu/deploy/migrate` with `{token, run:"migrate --force"}` → runs once, writes `deploy.done`, refuses again.
7. `POST .../deploy/migrate` `{token, run:"db:seed --class=RoleSeeder --force"}` (second call needs flag removed — see note) **or** run `php artisan db:seed --class=RoleSeeder` via cPanel cron one-shot / `exec`-capable probe.
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
`POST deploy/migrate` — IP allowlist + single-use token + self-disabling flag + command allowlist (`migrate --force`, `db:seed --class=RoleSeeder --force`) + audit log. Each successful call writes `storage/app/deploy.done`; to run again (e.g. the seeder) delete that flag file via File Manager first.

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
