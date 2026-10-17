# PHASE16I — Production Acceptance and Joomla Containment

## Scope

Completed within approved limits:

- Academy SMTP recovery verified.
- Post-import Academy backup created and verified.
- Joomla diagnostic/backup HTTP exposure contained by a narrow `.htaccess` denylist.
- Joomla bridge package prepared, not installed.
- No production accounts modified.
- No Joomla course pages changed.
- No Joomla Bridge installation performed.
- No public course publication performed.

## 1. Administrator acceptance — manual checklist

Operator must perform these checks in an authorized administrator browser session. Do not provide passwords, cookies, or session tokens in chat.

### Dashboard access

- Sign in as an authorized administrator.
- Confirm `/admin/dashboard` loads without PHP/Livewire errors.
- Confirm sidebar/admin navigation renders on desktop and mobile.
- Confirm dashboard counts are consistent with production catalogue status:
  - 5 programmes
  - 7 courses
  - 34 modules
  - 137 lessons
  - 57 quizzes
  - 23 materials
  - 17 variants

### Programmes and courses

- Open `/admin/programmes`; verify all 5 programmes are present.
- Open `/admin/courses`; verify all 7 imported courses are present.
- Open one course in each programme where practical; verify modules/lessons/quizzes/materials are attached to the expected course.
- Confirm all 7 courses remain `draft` and are not public.

### Livewire course editing

- Open one draft course in `/admin/courses`.
- Confirm Livewire editor controls render without browser-console errors.
- Make only a reversible draft-field check if explicitly approved; restore the original value immediately.
- Do not toggle publish, enrolment availability, pricing approval, or purchasability.
- Confirm no unexpected publication occurs after any Livewire interaction.

### Commercial variants

- Open `/admin/packages`.
- Verify exactly 17 variants.
- Verify all variants remain draft/pending approval.
- Verify zero purchasable variants.
- Verify source prices are preserved only where explicitly present.
- Verify missing/ranged prices remain pending; no invented prices.

### Private material authorization

- While signed in as administrator, open one private material download from a draft course.
- Confirm administrator/trainer-authorized material opens.
- In a separate unauthenticated browser/private window, request the same material URL; expected result is authentication redirect or authorization denial, never raw file bytes.
- Do not upload or delete production materials during acceptance.

### Account/profile functionality

- Open profile/account screens in view-only mode.
- Confirm administrator profile data renders.
- Do not change roles, passwords, email addresses, or account status.
- If trainer/student test accounts are later approved, create them through normal workflows only and document them separately.

### No unexpected publication

- Confirm catalogue status remains:
  - courses: 7 draft / 0 published
  - variants: 17 pending / 0 purchasable
- Confirm no checkout link is publicly reachable for draft variants.
- Confirm no Joomla purchase buttons were changed.

## 2. Backup restore readiness

### Existing backups

Pre-import Academy dump:

```text
storage/app/private/backups/academy-20261017-154734.sql
79,029 bytes
SHA-256: 2d81b4dd8146d287268aae05e53f57343886aceaabec78d5dd826c4e4ae716a2
56 CREATE TABLE statements
Dump completed footer present
```

Post-import Academy dump:

```text
storage/app/private/backups/academy-20261017-163136.sql
479,620 bytes
SHA-256: cd2c6449e431b4502698cd2ecc2a8bf39d872eac43e77454e1aa11ca4f14798a
56 CREATE TABLE statements
Dump completed footer present
No MYSQL_PWD material
```

The post-import dump contains the catalogue rows:

```text
7 courses
17 course_variants
23 materials
```

### Isolated restore status

Pending. No disposable MySQL database is available to this session, and no database secrets were requested or logged.

### Safe restore procedure

Do not restore into `euroafri_academycli` or any live Joomla/Academy database.

1. In cPanel, create a separate disposable MySQL database, for example `euroafri_academy_restore`.
2. Create or assign a separate database user with privileges only on that disposable database.
3. Upload `academy-20261017-163136.sql` outside the public document root using cPanel File Manager or the existing private application tree.
4. Import through phpMyAdmin:
   - Select only the disposable database.
   - Import → choose the SQL file.
   - Format: SQL.
   - Do not enable “partial import” unless needed for a very large dump.
5. If SSH is later available, the equivalent isolated command is:

```bash
mysql -u DISPOSABLE_USER -p DISPOSABLE_DB < academy-20261017-163136.sql
```

6. Verify row counts after restore:

```sql
SELECT COUNT(*) FROM programmes;        -- 5
SELECT COUNT(*) FROM courses;           -- 7
SELECT COUNT(*) FROM course_modules;    -- 34
SELECT COUNT(*) FROM lessons;           -- 137
SELECT COUNT(*) FROM quizzes;           -- 57
SELECT COUNT(*) FROM materials;         -- 23
SELECT COUNT(*) FROM course_variants;   -- 17
```

7. Verify publication safety:

```sql
SELECT status, COUNT(*) FROM courses GROUP BY status;          -- draft only
SELECT approval_status, COUNT(*) FROM course_variants GROUP BY approval_status;
```

8. Drop the disposable database only after validation is recorded.

## 3. SMTP recovery

Confirmed operational.

Provider check:

```text
mail.euroafricamedia.eu:465
implicit TLS available
AUTH PLAIN LOGIN advertised
configured mailbox credentials authenticate successfully
```

Laravel result:

- Configuration cache cleared.
- One controlled notification queued to the authorized deploy recipient.
- Queue worker processed it.
- Operator confirmed mailbox delivery.
- `jobs: 0`
- `failed_jobs: 1` remains the older controlled failure from `2026-10-17 15:32:14`; it was not retried.

## 4. Joomla containment

### Forensic preservation

Before `.htaccess` was changed, forensic copies were downloaded outside the Joomla public document root to:

```text
/tmp/eamn-joomla-forensics-20261017/files/
/tmp/eamn-joomla-forensics-20261017/metadata.txt
/tmp/eamn-joomla-forensics-20261017/SHA256SUMS
```

Remote metadata was captured first. `.htaccess` was preserved before modification.

Selected hashes:

```text
.htaccess before: 497b60b95e715da6febb1e19c00b410e16ff462290b8fc3e6dc83db2e41d21a5
.htaccess after:  3db7b365b237a21b696e3a16de6ffd1506023a7f803511d1e509ae11fc71e611
JPA archive:      2a3a78af1b25af3c52dc7a872e841591f79ebeafa7a9a80b1945c9d25e8c1e4a
error_log:        2c9b9ba3b7dbe46e78060715a3de60ca3ba82a3b00445723d10fcde478144eae
```

### Access control applied

A narrow, reversible `.htaccess` `<FilesMatch>` denylist was added. It does not broadly disable Joomla PHP execution.

Denied public filenames:

```text
__eam_test.php
check-ffmpeg.php
clear-opcache.php
mal-file-check.php
malware-cleanup.php
newop.php
configuration.bak.php
site-new.euroafricamedia.eu-20260801-081905cest-Q85QxB5SHswU3mnN.jpa
error_log
```

Post-change verification:

```text
/__eam_test.php                 403
/check-ffmpeg.php               403
/clear-opcache.php              403
/mal-file-check.php             403
/malware-cleanup.php            403
/newop.php                      403
/configuration.bak.php          403
/site-new...jpa                 403
/error_log                      403
```

### Joomla smoke tests

After the denylist:

```text
/                                                   200
/index.php                                          200
/administrator/                                     200
/index.php?option=com_users&view=login              200
/courses                                            200
/courses/training-awareness-institute               200
/courses/special-german-language-school             200
/courses/training                                   200
/courses/digital-inclusion-training-for-elderly     200
/courses/start-small-earn-big                       200
/courses/business-wealth-academy                    200
/courses/no-easy-target™-security-academy           200
```

`/login` returned 404 before/after containment; that appears to be an existing routing condition, not caused by this change.

### Script inspection as text

- `__eam_test.php`: plain PHP test endpoint.
- `check-ffmpeg.php`: executes `ffmpeg -version` and prints output; unnecessary public executable.
- `clear-opcache.php`: resets OPcache without authentication; denial-of-service/performance risk.
- `mal-file-check.php`: recursively scans `/home/euroafri` for malware filenames and discloses filesystem metadata; information disclosure and expensive scan risk.
- `malware-cleanup.php`: keyed cleanup script capable of recursively deleting exact target filenames under `/home/euroafri`; embedded key should be treated as exposed.
- `newop.php`: discloses OPcache internals.
- `configuration.bak.php`: contains recoverable legacy configuration values, including credential material; treat all credentials in it as exposed.
- Public JPA archive: valid `JPA1` archive; likely contains full site/configuration data and must remain inaccessible.
- `error_log`: publicly reachable before containment; contains path/error information.

### Log findings

PHP `error_log` was inspected; Apache access logs were unavailable through the Joomla FTP account (`logs/` and `access-logs/` directories were empty/inaccessible).

Findings:

- No direct references to the contained diagnostic filenames in `error_log`.
- Historical fatal errors referenced a now-missing `cache_handler.php` and `Create_Function()` on 9–10 Oct; file is no longer present via FTP. This is suspicious historical residue and supports credential rotation plus a full malware scan.
- Current repeated warnings are from `plugins/system/sppagebuilder/sppagebuilder.php` lines 83 and 86.
- `base64_decode` appears twice in the error log text; no direct eval/shell-exec indicators found in the inspected PHP error log.

### Containment recommendation

Keep the denylist temporarily, then permanently remove or relocate these files outside the public document root after the operator confirms no dependency:

- `__eam_test.php`
- `check-ffmpeg.php`
- `clear-opcache.php`
- `mal-file-check.php`
- `malware-cleanup.php`
- `newop.php`
- `configuration.bak.php`
- public `.jpa` backups
- `error_log`

Preferred permanent controls:

- Store backups outside the web root.
- Disable public `error_log`; log to private server logs.
- Remove diagnostic scripts after use.
- Keep `configuration.php` non-backup copies out of public source reach.

## 5. Credentials requiring rotation

Rotate after containment, without posting values in chat:

- Joomla database user/password referenced by any configuration backup or JPA archive.
- Joomla `secret` referenced by `configuration.bak.php`.
- Joomla SMTP mailbox credentials referenced by `configuration.bak.php`.
- Any embedded cleanup-script key in `malware-cleanup.php`.
- cPanel/FTP credentials if these files may have been downloaded before containment.
- Any admin credentials used during earlier recovery if their transport cannot be ruled out.

Reason: `configuration.bak.php`, the public JPA archive, and public diagnostic scripts existed in the document root. HTTP access is now denied, but prior exposure cannot be ruled out without Apache access logs.

## 6. Joomla Bridge preparation

Prepared package:

```text
dist/com_eamnbridge.zip
SHA-256: 60701c5f93521f10e74ca17a59a5b9e4ec59667a34995185524c1db0308d50ec
```

Validation:

- `com_eamnbridge.xml` parses as a valid Joomla component manifest.
- `component/eamnbridge.php` passes PHP syntax check.
- `component/src/BridgeHelper.php` passes PHP syntax check.
- Academy Joomla bridge feature tests: 11 passed.
- Package is not installed.

Installer compatibility:

- Target: Joomla 4 component installer.
- Component: `com_eamnbridge` version `1.0.0`.
- Site entry point supports `task=go` and `task=exchange`.
- Installer creates `#__eamnbridge_codes`.
- Uninstaller drops `#__eamnbridge_codes`.
- Required options:
  - Academy Base URL: `https://academy.euroafricamedia.eu`
  - Shared HMAC Secret: must match Academy `JOOMLA_BRIDGE_SECRET`
  - Client ID: `eamn-academy`

Academy side currently has no `JOOMLA_BRIDGE_SECRET` configured. Generate a new high-entropy secret locally, then place the same value in:

- Academy private `.env` as `JOOMLA_BRIDGE_SECRET`
- Joomla component parameter `bridge_secret`

Do not send the secret through chat or store it in the Joomla document root.

### Safe activation procedure

Perform only on a staging/disposable Joomla install first:

1. Confirm Joomla filesystem and database backup restore.
2. Install `dist/com_eamnbridge.zip` through Joomla Extensions.
3. Configure base URL, client ID, and shared secret.
4. Set matching `JOOMLA_BRIDGE_SECRET` in Academy `.env`.
5. Run Academy `config:clear`.
6. As a guest, open:

```text
https://academy.euroafricamedia.eu/auth/bridge/start?target=/dashboard
```

Expected: redirect to Joomla bridge/login, then back to Academy consume.

7. Log in with an authorized non-admin Joomla account.
8. Confirm one-time code is consumed and Academy session opens the correct role dashboard.
9. Reuse the same URL/code; expected failure.
10. Test blocked/disabled Joomla account; expected denial.
11. Confirm audit events in Academy.
12. Only then schedule production install and map the seven course pages.

### Rollback plan

- Joomla admin → Manage → uninstall `com_eamnbridge`.
- Confirm `#__eamnbridge_codes` is dropped.
- Remove `JOOMLA_BRIDGE_SECRET` from Academy `.env`.
- Run Academy `config:clear`.
- Restore Joomla files/database from pre-install backup if uninstall fails.
- Leave course page links unchanged until SSO is proven.

## 7. Remaining launch blockers

- Manual administrator acceptance pending.
- Disposable MySQL database unavailable; post-import restore validation pending.
- Joomla Apache access logs unavailable; historical suspicious requests cannot be fully ruled out.
- Joomla credentials/secrets in preserved backup/config material require rotation.
- `JOOMLA_BRIDGE_SECRET` not configured.
- Joomla Bridge not installed.
- Seven course pages unchanged.
- No courses or packages published/purchasable.
