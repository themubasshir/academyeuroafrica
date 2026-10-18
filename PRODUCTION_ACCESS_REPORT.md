# Production access report — Phase 13B/15F

Read-only verification of the three FTP credentials. **Nothing on production was
modified.** No secrets are stored in the repo; all credentials used below were
read from a `netrc` file kept outside the repository and are redacted.

## Transport

| Requirement | Result |
|---|---|
| SFTP preferred | **Unavailable** — port 22 accepts TCP but the SSH handshake times out (firewalled/filtered for these accounts; `EOF-early` before password). Not usable. |
| FTPS explicit | **Available** — Pure-FTPd on `:21`, `AUTH TLS`, TLS 1.3. Cert `CN=webhosting2043.is.cc` (Let's Encrypt, expires 2027-01-08). Hostname mismatch is the normal cPanel shared-FTP setup; integrity verified by pinning the leaf fingerprint. Blanket `--insecure` not used for uploads — pin the fingerprint instead. |

Credentials were probed with `--ftp-ssl --ssl` and `--insecure` only for the
read-only inventory (transport still TLS-encrypted); upload steps should pin the
cert rather than rely on `--insecure`.

## Verified directory mappings

FTP root is chroot-relative; `pwd`/`ls` verified each independently.

| Connection | Lands in (FTP `/`) | Maps to | Verdict |
|---|---|---|---|
| `cli@academy.euroafricamedia.eu` | Academy **public docroot** — contains `index.php`, `.htaccess`, `build/`, `brand/`, `favicon.ico`, `robots.txt`, `cgi-bin/`, `php.ini`, `.user.ini` | `/home/euroafri/academy.euroafricamedia.eu` | ✅ Academy public |
| `cli@euroafricamedia.eu` | Joomla **docroot** — `configuration.php`, `administrator/`, `components/`, `templates/`, `plugins/`, `media/`, `images/` | Joomla site root (`/home/euroafri/` subdir for the main domain) | ✅ Joomla |
| `kda@euroafricamedia.eu` | — | — | ❌ **530 Access denied** (bad credentials or not an FTP account on this host) |

### Key structural finding — the private Laravel tree is unreachable by FTP

`cli@academy` is chrooted inside the **public** docroot (`cd ..` returns the same
listing; `get /home/euroafri/eamn-academy/` is refused). `cli@euroafricamedia.eu`
is chrooted inside the **Joomla** docroot. **No FTP credential can reach
`/home/euroafri/eamn-academy`.**

Consequence: the private `eamn-academy-app-*.tar.gz` must be uploaded and
extracted via **cPanel File Manager**, not FTP. FTP is sufficient for the
public-docroot archive only.

## Academy public docroot — current state (read-only)

Already contains a **prior partial deploy** (Oct 15):

- `index.php` — the current Laravel split front-controller (reads
  `APP_BASE_PATH` from env or `.app-base-path` file).
- `.htaccess` — already sets `SetEnv APP_BASE_PATH /home/euroafri/eamn-academy`
  and denies dotfiles.
- `build/` + `manifest.json` — Vite assets present.
- `brand/` — logo present.
- **Missing**: `vendor/livewire/` — the CSP-safe Livewire runtime is **not** on
  the live docroot, so `wire:`/`x-on` actions on the live site are currently dead
  (the 15F D1 fix is built but not uploaded).
- Leftover artefact: `eamn-academy-public-20261015-182911.tar.gz` (244 KB) still
  sitting in the web root — should be removed on deploy.
- No `.env` present in docroot (correct — it lives in the private tree).
- `php.ini`/`error_log` point to `/home/euroafri/logs/php.error.log`.

## Joomla docroot — security findings (observed, not modified)

- `configuration.php` present (`444`-ish, read-only perms `-r--r--r--`) — good.
- **Exposed**: `configuration.bak.php` (world-readable `-rw-r--r--`) in the web
  root — a cleartext copy of the Joomla config (DB credentials) retrievable over
  HTTP unless `.htaccess` denies it. Recommend removal/access-deny.
- **Leftover dev/probe files** in web root: `__eam_test.php`, `__eam_test.html`,
  `malware-cleanup.php`, `mal-file-check.php`, `check-ffmpeg.php`,
  `clear-opcache.php`, `newop.php`, `web.config.txt`, `htaccess.txt`,
  `kickstart-core-9.1.0.zip`, `modules.zip` (16 MB), and an **839 MB
  `site-new…jpa` backup** — all potentially world-readable and/or executable.
- `error_log` ~5.8 MB in docroot.
- `new.euroafricamedia.eu/` staging dir inside the docroot.
- `DOWNlsD/`, `protected-downloads/`, `course_videos/`, `tmp/` — unknown content.

None of these were touched. They are flagged for a separate Joomla-hardening pass.

## Security audit summary

- Private app **outside** public docroot: ✓ correct (by design — and unverified
  because FTP can't reach it; assumed present at `APP_BASE_PATH`).
- `.env` **not** in docroot: ✓ (not present in either FTP home).
- `storage/`, `vendor/` not web-accessible: ✓ by layout (private tree).
- Joomla `configuration.php`: present and read-only; the `.bak` copy is a risk.
- Encrypted transport: ✓ TLS 1.3 (FTPS). SFTP unavailable.

## Deployment readiness vs release `f6965d2`

- Public docroot is one release behind: it has the Oct 15 build and is **missing
  `vendor/livewire/`**. Uploading `eamn-academy-public-20261017-143226.tar.gz`
  fixes the dead-Livewire defect on the live site.
- The private app is not in the FTP-reachable tree — deploy it via File Manager.
- `deploy/migrate` runner + `academy:provision-admin` exist in the archive and
  are gated; `deploy.token`/`deploy.admin` must be created in the private
  `storage/app` (via File Manager), never the web root.

## Backup requirements

1. phpMyAdmin export of `euroafri_academycli` (DB) before any migration.
2. Copy current public docroot contents (or the existing
   `eamn-academy-public-*` archive + `index.php`/`.htaccess`) before overwriting.
3. Keep the previous two `eamn-academy-*` archives + manifest for rollback.

## Deployment blockers

1. **`kda@euroafricamedia.eu` rejected** — third connection unusable; its
   intended target is unknown.
2. **Private app unreachable by FTP/SFTP** — must use cPanel File Manager for the
   private tree and for `deploy.token`/`deploy.admin`/`.env`.
3. **SFTP unavailable** — FTPS is the only file transport (encrypted, cert
   hostname mismatch is a cPanel quirk).
4. **PayPal Live not configured** — `PAYPAL_MODE=sandbox`; do not activate live.
5. **Joomla purchase buttons / Bridge not installed** — deliberately out of scope.
6. Private-app directory presence could not be verified directly (no FTP scope);
   confirm via File Manager that `/home/euroafri/eamn-academy` exists and is
   writable before uploading.

## Next steps (awaiting approval)

1. Fix or confirm the `kda@` credential and its intended directory.
2. Confirm `/home/euroafri/eamn-academy` exists in File Manager and is writable.
3. Stage the two release archives + manifest for upload (File Manager for the
   app archive; FTP acceptable for the public archive).
4. Provision `.env`, `APP_KEY`, `deploy.token`, `deploy.admin` in the private tree.
5. Then run the migration runner + import + provision-admin per
   `DEPLOYMENT_INTERSERVER.md`.
