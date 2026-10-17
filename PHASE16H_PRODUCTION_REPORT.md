# PHASE16H — Production Catalogue Import and SMTP Recovery

## Scope

- Production application: `https://academy.euroafricamedia.eu`
- Private application path: `/home/euroafri/eamn-academy`
- Authorized write scope: Academy application/database only
- Excluded: Joomla, Joomla SSO Bridge install, PayPal Live

## Stage A — Database backup

PASS.

A restricted one-time runner command created a private MySQL dump before catalogue writes:

```text
storage/app/private/backups/academy-20261017-154734.sql
79,029 bytes
SHA-256: 2d81b4dd8146d287268aae05e53f57343886aceaabec78d5dd826c4e4ae716a2
```

Backup verification:

- Downloaded successfully through the private FTPS account.
- 56 `CREATE TABLE` statements found.
- `Dump completed` footer present.
- No `MYSQL_PWD` or credential material present in the dump.
- Pre-import catalogue state was empty: 0 programmes, courses, modules, lessons, quizzes, materials.
- One pre-existing controlled-mail failure existed in `failed_jobs`.

## Stage B — Catalogue import

PASS after one production data-encoding fix.

### Root cause found during import

The first real import stopped at the flagship course module because DOCX heading patterns were byte-oriented. An em dash was parsed into malformed text and MySQL rejected the module title:

```text
Incorrect string value: '\x80\x94 UND...' for column course_modules.title
```

The parser and runner were fixed:

- Unicode-safe DOCX heading parsing added.
- Invalid UTF-8 stripped from parsed DOCX text.
- Restricted runner output sanitized before JSON encoding.

The failed first attempt left an incomplete, non-published subset. The subsequent idempotent import completed it without duplicating rows.

### Import result

Authorized command:

```text
catalogue:import
```

Result:

```text
Processed 263 rows.
programme created:2  existing:3
course    created:3  existing:4
module    created:34 existing:0
lesson    created:137 existing:0
quiz      created:57 existing:0
material  created:20 existing:3
```

Final database verification:

```text
programmes:     5
courses:        7
course_modules: 34
lessons:        137
quizzes:        57
materials:      23
```

Publication and material verification:

```text
courses draft:        7
materials missing:    0
trainer materials:   20
student materials:    3
```

All courses remain unpublished drafts. Imported material files exist on the private disk and are not public URLs.

## Stage C — Commercial variants

PASS.

Dry run reported 17 planned variants. Real command created exactly 17 variants:

```text
catalogue:variants
```

Price review:

- Fixed-price variants preserved.
- Missing or ranged source prices left unpriced and pending.
- No price invented.

Final variant state:

```text
total:              17
non_draft:           0
non_pending:         0
purchasable_state:   0
```

No variant is publicly purchasable.

A missing Phase 14 admin route/component pair was also restored:

```text
/admin/packages
app/Livewire/Admin/Variants.php
resources/views/livewire/admin/variants.blade.php
```

Unauthenticated access now redirects to `/login`, proving the route is registered without exposing admin data.

## Stage D — SMTP

BLOCKED by mailbox authentication.

Non-secret configuration report:

```text
default:       smtp
transport:     smtp
host:          mail.euroafricamedia.eu
port:          465
encryption:    null
username:      aca…
password_set:  true
from:          academy@euroafricamedia.eu
```

Controlled queued notification result:

- Notification queued to the authorized deploy recipient.
- Database queue worker picked it up.
- SMTP rejected authentication:

```text
535 Incorrect authentication data
```

Final queue state:

```text
jobs:        0
failed_jobs: 1
```

The single failed job is the controlled mail test. Mailbox password rotation or corrected SMTP credentials are required. Do not retry unrelated jobs blindly.

## Stage E — Final verification

Verified:

- `/up`: HTTP 200
- `/login`: HTTP 200
- `/admin/dashboard`: unauthenticated HTTP 302 to `/login`
- `/admin/courses`: unauthenticated HTTP 302 to `/login`
- `/admin/packages`: unauthenticated HTTP 302 to `/login`
- `/vendor/livewire/livewire.js`: HTTP 200
- Scheduler + bounded worker: operational every minute
- Queue: worker drains to zero; failed job is only SMTP-auth test
- Database backup: present, downloadable, dump-complete

Not fully verifiable without authenticated browser credentials:

- Admin dashboard contents
- Imported course/module UI inspection
- Variant administration interactions
- Livewire mutations after login

Current production routes correspond to an older release for some newer student/admin surfaces. The requested Phase 14 variant administration route was restored only; unrelated missing newer surfaces were not deployed.

## Commits

```text
7f64399 fix: render bounded queue worker flag correctly
f1c186d fix: allow catalogue dry-run through runner
0472f57 fix: expose minimal command and queue diagnostics
e6e50c7 feat: add restricted deploy and mail diagnostics
ff798c1 fix: use literal mail-test action URL
1b22b4f feat: add restricted production database backup
d7348f8 fix: sanitize DOCX text and add catalogue diagnostics
2bb2ff2 fix: sanitize runner command output
2445b35 fix: parse Unicode DOCX headings for MySQL
ba35b47 feat: allow commercial variant dry run
402c7ac fix: allow repeatable read-only deploy diagnostics
423350e feat: add non-secret mail diagnostics
```

## Remaining blockers

1. SMTP mailbox credentials must be rotated or corrected securely.
2. One controlled queued notification remains in `failed_jobs` because of SMTP 535.
3. Authenticated browser/admin verification requires valid admin credentials or an authorized password reset after SMTP works.
4. Real import and variants are now complete; no further catalogue writes required.
5. Joomla remains untouched.
6. Joomla SSO Bridge remains uninstalled.
7. PayPal remains non-live; no package is purchasable.
