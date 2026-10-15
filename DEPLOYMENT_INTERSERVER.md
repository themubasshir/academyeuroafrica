# Deployment — InterServer shared hosting (no SSH)

## Constraints honoured
- No SSH/terminal on prod. No Docker/Redis/Supervisor/Node/WebSockets.
- Composer + Vite run locally; only built artefacts uploaded.

## Build (local / CI)
`./build.sh` performs, in order:
1. **Preflight** — required tools (`php composer node npm tar`) and PHP extensions (`mbstring openssl pdo curl fileinfo xml ctype json tokenizer`).
2. `php artisan test` — hard gate; build aborts on any failure.
3. `composer install --no-dev --prefer-dist --optimize-autoloader`.
4. `npm ci && npm run build`.
5. Stage a clean tree excluding `dist/`, `node_modules/`, `.git`, `tests/`, `.env*`, cache/log/app-storage, `phpunit.xml`, `.ai`.
6. Copy `.env.example` → `.env.production.example` inside the bundle (the *only* env file shipped); inject `PAYMENT_GATEWAY=stripe` so the test gateway can't be selected by accident.
7. `tar -czf dist/eamn-academy-<stamp>.tar.gz` + manifest (`<stamp>.manifest.txt`) with PHP/Laravel versions, per-file SHA-256, archive checksum.

## Config caching
Do **not** run `config:cache` locally — it would bake local/placeholder env into the artefact. Run it on the host **after** creating the real `.env`.

## Layout on host
- App dir **outside** web root: `/home/<user>/eamn-academy/`
- Subdomain `academy.euroafricamedia.eu` docroot → `/home/<user>/eamn-academy/public`
- Only `public/` is web-reachable. `.env`, `storage/`, `vendor/` never exposed.
- `storage/` and `bootstrap/cache` writable by PHP user (cPanel perms).

## First deploy
1. Upload `dist/eamn-academy-<stamp>.tar.gz` via cPanel File Manager/FTP; extract outside docroot.
2. Point subdomain docroot at `.../eamn-academy/public`.
3. Create `.env` on host (never shipped): MySQL creds, `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://academy.euroafricamedia.eu`, `JOOMLA_BASE_URL`, `JOOMLA_BRIDGE_SECRET`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `PAYMENT_GATEWAY=stripe`, `DEPLOY_ALLOWED_IPS`.
4. Run one-time migration via the controlled deploy runner (below).
5. `php artisan config:cache route:cache view:cache` (on host, via the runner or cron one-shot if no SSH).
6. Verify `/up` health + login flow.

## Migration runner (no SSH)
`POST deploy/migrate` — gated by IP allowlist + signed token + self-disabling flag file + audit log. Runs `Artisan::call('migrate',['--force'=>true])` once, then refuses. **Not** a general Artisan endpoint. After use it is dead until the flag file is removed (manual file op on next deploy).

## Cron (cPanel → Cron Jobs)
```
* * * * * php /home/<user>/eamn-academy/artisan schedule:run >> /dev/null 2>&1
* * * * * php /home/<user>/eamn-academy/artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```
Verify cron support in the panel first.

## Secrets & gateway safety
- Test-only payment gateway is gated to `local`/`testing` env (`app()->environment`), and `build.sh` ensures `PAYMENT_GATEWAY=stripe` is present in the shipped template. On prod the test routes are not registered at all.
- No dev secrets, `.git`, local `.env`, or test files ship in the tarball.

## Backup & rollback
- DB: cPanel/phpMyAdmin export before each release.
- Code: keep prior tarball; rollback = re-extract previous archive + restore DB snapshot.
- `down` flag file for maintenance window if needed.

## Never do
- No web-based general Artisan runner. No `storage:link` dependency. No public `.env`/vendor. No symlink reliance on FTP.
