# Deployment — InterServer shared hosting (no SSH)

## Constraints honoured
- No SSH/terminal on prod. No Docker/Redis/Supervisor/Node/WebSockets.
- Composer + Vite run locally; only built artefacts uploaded.

## Build (local / CI)
1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build`
3. `php artisan test` — must pass
4. `php artisan config:cache route:cache view:cache` (with prod `.env` placeholder-safe values; regenerate `APP_KEY` on host)
5. Package: app code + `vendor/` + `public/build/` + `bootstrap/cache` — as `eamn-academy.tar.gz`

## Layout on host
- App dir **outside** web root: `/home/<user>/eamn-academy/`
- Subdomain `academy.euroafricamedia.eu` docroot → `/home/<user>/eamn-academy/public`
- Only `public/` is web-reachable. `.env`, `storage/`, `vendor/` never exposed.
- `storage/` and `bootstrap/cache` writable by PHP user (cPanel → correct perms).

## First deploy
1. Upload tarball via cPanel File Manager or FTP; extract outside docroot.
2. Point subdomain docroot at `.../eamn-academy/public`.
3. Create `.env` on host (MySQL creds, `APP_KEY`, `JOOMLA_BRIDGE_SECRET`, `APP_DEBUG=false`, `APP_ENV=production`, HTTPS URL).
4. Run one-time migration via the controlled deploy runner (see below).
5. Verify `/up` health route + login flow.

## Migration runner (no SSH)
A route gated by: signed token + IP allowlist + single-use + audit log — runs `Artisan::call('migrate', ['--force'=>true])` then self-disables (flag file). Implemented in Phase 8. NOT a general Artisan console.

## Cron (cPanel → Cron Jobs)
```
* * * * * php /home/<user>/eamn-academy/artisan schedule:run >> /dev/null 2>&1
* * * * * php /home/<user>/eamn-academy/artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```
Verify cron support in the panel first; if unavailable, use cPanel's scheduled-task equivalent or document manual trigger.

## Backup & rollback
- DB: cPanel/phpMyAdmin export before each release.
- Code: keep prior `eamn-academy.tar.gz`; rollback = re-extract previous tarball + restore DB snapshot.
- `maintenance` mode via a `down` flag file placed by deploy script if supported, else brief window.

## Never do
- No web-based Artisan runner. No `storage:link` dependency. No public `.env`/vendor. No symlink reliance on FTP.
