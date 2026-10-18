# Phase 18 — User Management, Role Assignment & Course Membership

Date: 2026-10-18
Status: Local implementation complete. Production deployment blocked pending explicit approval.

## Architecture audit

Existing authorization and membership structures reused:

- Roles: `super-admin`, `academy-admin`, `trainer`, `student` in `app/Models/Role.php`.
- Admin route guard: `routes/web.php:193-209`.
- Trainer-course membership: `trainer_assignments`, unique `(trainer_id, course_id)` in `database/migrations/2026_10_15_040731_create_eamn_academic_tables.php:107-113`.
- Student-course access: `enrolments` through `course_variants`, unique `(user_id, course_variant_id)` in `database/migrations/2026_10_15_040731_create_eamn_academic_tables.php:95-105`.
- Joomla identity links: `external_identities` in `database/migrations/2026_10_15_035151_create_eamn_foundation_tables.php:44-55`.
- Privileged audit records: `AuditLog::record()` in `app/Models/AuditLog.php:23-32`.
- Existing admin surfaces: `app/Livewire/Admin/Users.php`, `Courses.php`, `Enrolments.php`.
- Existing course-scoped trainer authorization: `app/Policies/CoursePolicy.php:31-40`.

## Schema impact

One additive migration required:

- `database/migrations/2026_10_18_091938_add_manual_membership_metadata_to_enrolments_table.php`
- Adds `enrolments.source`, default `purchased`, indexed.
- Adds nullable `enrolments.grant_reason`.
- Existing order/payment rows remain unchanged.
- No migration has run against production.
- No PayPal transaction or order is created for manual access.

No duplicate role, trainer, group, order, entitlement, audit, or Joomla mapping tables added.

## Completed locally

### User management

- Add user from `/admin/users`.
- Email normalized to lowercase and validated unique.
- New users have no generated password.
- Password setup uses existing one-time Laravel password-reset flow.
- Generated passwords never displayed.
- Edit name, email, timezone, roles, trainer courses, and student course variants.
- Enable/disable users.
- Resend invitation for non-Joomla-linked accounts.
- Search by name/email and filter by role.
- Existing last-super-admin protection retained.
- `super-admin` assignment restricted to current `super-admin`.
- Privileged actions audited.

### Trainer membership

- Multiple trainers per course supported through existing unique pivot.
- Repeated assignments use `firstOrCreate`.
- Removed assignments delete only current authorization rows; historical grading/audit records remain.
- Existing trainer policy continues to scope access by assigned course.

### Student membership

- Assign course variants from user management.
- Manual enrolments use existing enrolment records.
- Duplicate rows prevented by existing unique constraint and `firstOrNew`.
- Manual rows marked `source=manual` and carry administrator reason.
- Removing manual membership suspends enrolment and logs `enrolment.revoked`.
- Commercial enrolments cannot be changed by manual workflows.
- Existing progress and certificates remain attached to enrolment.
- Existing enrolment screen now requires explicit grant reason.

### Course UI

- User management exposes trainer and student course assignment controls.
- Existing enrolment screen remains available for bulk-style repeated grants; true multi-select batch action is not implemented.
- Dedicated course member page with separate Trainers/Students tabs is not implemented; course membership controls remain in user editor and enrolment screen.

## Tests

- Full PHPUnit suite: `196 passed`, `421 assertions`.
- Pint: passed.
- PHP syntax checks: passed for changed PHP and migration files.
- Latest split build: `dist/eamn-academy-app-20261018-152842.tar.gz`, `dist/eamn-academy-public-20261018-152842.tar.gz`, and `dist/eamn-academy-20261018-152842.manifest.txt`.
- Latest archive SHA-256:
  - app: `2262979ee14d88be55c34abd150f80f1894199fede218c7223b32335add9cc66`
  - public: `020c0287722a650697f9dee3efef1aa91a992431e9063eb45e072e21063792f6`
- Archive verification confirms changed runtime files and migration are present. Tests are intentionally excluded from production archive by `build.sh`.
- Added tests cover:
  - administrator user creation and invitation without password
  - academy-admin cannot promote to super-admin
  - manual enrolment source/reason and no order
  - existing commercial enrolment protection
  - existing last-super-admin protection
  - existing trainer assignment and course authorization

## Not complete / blocked

- Batch enrolment action not implemented.
- Dedicated course member page with separate Trainers/Students tabs not implemented; assignment controls currently live in user editor and enrolment screen.
- Invitation delivery depends on configured mail transport; no SMTP changes made.
- Production route/controller baseline remains unresolved from Phase 17; this release must not deploy until production private-tree inspection confirms compatible route source, controller files, and cache state.
- Migration requires explicit production authorization. Do not run `migrate`, `migrate:fresh`, or rollback in production.

## Deployment safety

Release must include both split-layout archives from `build.sh`:

- Private app archive -> `/home/euroafri/eamn-academy/` via cPanel File Manager.
- Public archive -> existing academy public docroot.
- Manifest includes file hashes and archive hashes.
- Preflight runs tests, PHP extension checks, Composer, Vite, Livewire asset publication, secret sanitization, and staged-file checks.

Required rollback:

1. Keep current private/public archives before replacement.
2. Take DB backup before any authorized migration.
3. Re-extract prior private and public archives together.
4. Restore DB backup only if migration has run and rollback requires schema restoration.
5. Clear Laravel caches through approved cPanel/host procedure.
6. Verify `/up`, `/login`, `/admin/users`, and `/admin/courses` before reopening access.

No production files, database, Joomla, payment, SMTP, pricing, or SSO configuration changed.
