# EAMN Academy — Full-System Completion Audit

**Scope:** standalone Academy only. Joomla untouched. PayPal Live untouched. No production changes.

**Audit basis:** code, routes, models, policies, services, migrations, views, tests, deployment docs, and direct verification of the working tree at commit `aeb721c` plus the Phase 15D working tree. No Joomla files or production systems were modified.

## Phase 15D update

Phase 15D completed an audit and targeted extension of the existing trainer, learning, assessment, tutoring, certification, and notification workflows. Assigned-trainer course scoping, course/group/student live-session targeting, encrypted meeting URLs, attendance, lesson progress, material visibility, assignment submission and released grading, quiz attempt/scoring/release rules, tutoring credit-ledger booking and cancellation, eligibility-based certificates, QR verification, revocation, and retry-safe issuance were preserved rather than recreated.

Learning actions now consistently reject suspended, refunded, and expired enrolments. Quiz answer and submission actions also reject a different authenticated student. Assignment submissions cannot be duplicated while awaiting grading, trainer grades and manual quiz points are range-bounded, and repeat release actions avoid duplicate grade/result notifications. Live-session targets are validated against the trainer's assigned course. Notifications now cover assignment grade release, manual quiz result release, live-session schedule/cancellation, tutoring booking/cancellation, full-refund entitlement suspension, and certificate issue/reissue. Database delivery is owner-scoped; queued mail respects category preferences while the in-app record remains available.

Focused Phase 15D integrity coverage and the complete direct PHPUnit suite passed at 512 MB: **168 tests, 338 assertions**. Browser/viewport/accessibility and external-service staging verification remain required. Scheduled reminders are not implemented or claimed.

## Phase 15C update

Phase 15C adds public student-only registration, local email verification, throttled password-reset flows, intended-URL-preserving login, profile/contact/time-zone editing, verified email changes, password/session security, notification preferences, owner-scoped order history, and database/mail-backed student notifications. The `notifications` table and `users.notification_preferences` migration must be applied. Configure a production `MAIL_MAILER` and run the configured database queue worker for mail-backed notifications; database notifications remain available when delivery is retried by the queue.

No Joomla code, production infrastructure, PayPal Live setting, or live transaction was changed.

## Executive result

Academy has solid domain foundations: role-gated portal, catalogue, course access, private files, quizzes, assignments, attendance, certificates, PayPal webhook processing, Joomla bridge, and commercial approval gates.

Academy is **not launch-complete**. Main blockers:

- Admin cannot manage full catalogue, users, roles, materials, refunds, reports, or settings through UI.
- Browser-level responsive, accessibility, and complete student workflow verification remains.
- Student registration, email verification, password recovery, profile/security settings, notification preferences, order history, and in-app notifications are implemented; external mail and queue configuration remain deployment requirements.
- Student payment history is scoped to the authenticated owner and displays order/payment/refund status without provider secrets.
- Refund service and entitlement revocation are implemented; student notifications are idempotent for newly-created enrolments.
- Queue worker and scheduler require external cron configuration.
- Course video management and secure playback are incomplete.
- PayPal capture/webhook integrity needs production sandbox validation.
- No recurring subscription or institutional billing exists.

## Status definitions

- **Complete and verified:** implementation plus focused automated coverage.
- **Implemented but not end-to-end tested:** code and focused tests exist; browser/production integration remains unverified.
- **Partial:** meaningful implementation exists, but required workflow or management surface is incomplete.
- **Missing:** no usable implementation found.
- **Broken:** implementation has a defect or unsafe gap.
- **Blocked by external configuration:** code exists; required provider, cron, credentials, or deployment setup is absent.

## P0/P1/P2 summary

| Priority | Items |
|---|---|
| **P0** | Production payment validation; refund and entitlement consistency; deploy runner hardening/retirement after migration; queue execution; missing admin controls that can cause manual DB edits; current test suite unavailable until dev dependencies restored |
| **P1** | Registration/recovery; complete admin CRUD; refund UI; order/payment history; notifications; reports; video management; trainer/student end-to-end workflows; course-shell curriculum decisions |
| **P2** | Subscriptions; institutional quoting/invoicing; real-time video; richer analytics; browser automation; UI polish beyond launch accessibility baseline |

# 1. Feature inventory

## Authentication and identity

| Feature | Status | Evidence / gap |
|---|---|---|
| Local login/logout | **Complete and verified** | `LoginController`, throttled login route, auth tests. |
| Role middleware | **Complete and verified** | `role:*` middleware and `RoleMiddlewareTest`. |
| Active-account enforcement | **Complete and verified** | `active` middleware on authenticated areas. |
| Joomla bridge SSO | **Implemented but not end-to-end tested** | HMAC/state/TTL bridge code and tests exist; Joomla plugin/lifecycle events are external and unverified. |
| Registration | **Missing** | No registration route/controller/component. |
| Password recovery | **Missing** | No forgot/reset routes, controllers, notifications, or mailables. |
| Email verification | **Missing** | No verification workflow found. |
| Profile/account settings | **Missing** | No profile or settings route/component. |

## Catalogue and academic structure

| Feature | Status | Evidence / gap |
|---|---|---|
| Public programmes/courses | **Implemented but not end-to-end tested** | Published-state checks exist; render smoke coverage only. |
| Admin programme creation/status | **Partial** | `Admin\Programmes` supports create and toggle status; no full edit/delete/module management. |
| Admin course creation/status | **Partial** | `Admin\Courses` supports create and toggle status; no full edit/delete/module/lesson UI. |
| Trainer module/lesson management | **Implemented but not end-to-end tested** | `Trainer\CourseManage` supports module/lesson CRUD, ordering, rename, material actions. |
| Admin course management | **Partial** | Admin cannot perform all catalogue actions without trainer access or database changes. |
| Importer | **Complete and verified** | Idempotent source-key importer, dry-run command, source provenance, draft-only behavior, focused tests. Production execution remains pending. |
| Commercial variant seeding | **Complete and verified** | Idempotent `catalogue:variants`, source-backed definitions, pending/fixed price states, focused tests. |
| Course shell decisions | **Blocked by product decision** | Digital Skills, Elderly, and German shells have no imported curriculum; must remain enquiry-only or receive authored content. |

## Materials and media

| Feature | Status | Evidence / gap |
|---|---|---|
| Private material storage | **Complete and verified** | Local disk points outside docroot; authorization tests exist. |
| Trainer material upload/assignment | **Implemented but not end-to-end tested** | `CourseManage::uploadMaterial`, replace/delete, visibility targeting. |
| Student material downloads | **Complete and verified** | `MaterialController` authorization and file tests. |
| Trainer-only resource visibility | **Complete and verified** | `Material::visibleTo` blocks students; focused coverage exists. |
| Video display | **Partial** | Generic YouTube/Vimeo/direct URL provider exists. No signed playback or protected streaming provider. |
| Video management UI | **Partial** | Course-management view references videos, but complete CRUD and secure provider integration are absent. |

## Administration

| Feature | Status | Evidence / gap |
|---|---|---|
| Admin dashboard | **Implemented but not end-to-end tested** | Live registry totals and links to operational management, materials, reports, and commerce surfaces. |
| Admin users | **Implemented but not end-to-end tested** | Search/filter, profile editing, role and trainer assignment controls, activation, and last-super-admin safeguards. |
| Roles and permissions | **Partial** | Roles/middleware/policies exist; role assignment is available, but no separate permission-management UI. |
| Admin enrolments | **Implemented but not end-to-end tested** | Search/filter, manual grants, suspension/restoration, expiry, audit records, and protection for commercial/refunded entitlements. |
| Admin orders | **Complete and verified** | Order list and administrator-authorized provider-verified refund workflow with entitlement reconciliation. |
| Admin packages/pricing | **Complete and verified** | `/admin/packages` supports edit, approve, unapprove, publish controls; purchase gate rechecks server-side. |
| Integration settings | **Partial** | Status display exists; no complete settings editor or credential rotation workflow. |
| Audit logs | **Partial** | Logs are recorded and viewable; no filtering/export/retention controls. |
| Materials administration | **Implemented but not end-to-end tested** | Paginated private-material inventory, visibility filtering, authorized deletion, storage cleanup, and audit logging. |
| Reports | **Implemented but not end-to-end tested** | Date-filtered student, enrolment, completion, order, revenue, refund, and certificate metrics from database records. |
| Notifications administration | **Partial** | Student notification inbox, category preferences, queued database/mail notification class, and domain event wiring exist; trainer/admin notification administration is not a separate surface. |
| Certificate administration | **Implemented but not end-to-end tested** | Manual issue, revoke, policy, and audit surfaces exist. |

## Student functionality

| Feature | Status | Evidence / gap |
|---|---|---|
| Student dashboard | **Partial** | Component renders course, live-session, and assignment summaries; no dedicated recent-notifications panel. |
| Purchased-course access | **Complete and verified** | Enrolment/course policies and access tests, including suspended/refunded/expired access rejection. |
| Lessons and progress | **Implemented but not end-to-end tested** | `LessonShow` records position/completion only for active enrolments; browser flow untested. |
| Private downloads | **Complete and verified** | Authorization tests. |
| Live training/session view | **Implemented but not end-to-end tested** | Targeted course/group/student sessions, encrypted meeting URLs, and active-entitlement visibility are implemented; browser flow untested. |
| Assignments | **Implemented but not end-to-end tested** | Student submission is owner/course/entitlement scoped and cannot be overwritten while awaiting grading; browser flow remains untested. |
| Quizzes | **Complete and verified** | Attempt lifecycle, entitlement checks, score calculation, result-release privacy, and cross-student answer protection are covered. |
| Results/grades | **Implemented but not end-to-end tested** | Owner-scoped results and release notifications exist; complete browser history/status UX is unverified. |
| Attendance | **Implemented but not end-to-end tested** | Trainer attendance recording exists; student visibility workflow needs verification. |
| Tutoring bookings | **Implemented but not end-to-end tested** | UTC-backed availability, student-time-zone display, transaction-scoped overlap prevention, credit ledger, cancellation policy/audit, and booking/cancellation notifications exist. |
| Appointment credit purchase/grant | **Partial** | Ledger service exists, but no caller grants credits; no admin UI or commerce entitlement mapping. |
| Certificates/downloads | **Implemented but not end-to-end tested** | Eligibility, issuance/PDF/download/QR/revocation code exists; issue/reissue notifications are queued and production queue path is unverified. |
| Order/payment history | **Implemented but not end-to-end tested** | Student owner-scoped order/payment/refund history route/component exists; browser flow remains unverified. |
| Profile/settings | **Implemented but not end-to-end tested** | Profile, timezone, email verification, password security, and notification preferences exist; browser flow remains unverified. |
| Responsive/empty-state quality | **Implemented but not end-to-end tested** | Render smoke exists; no browser viewport/accessibility suite. |

## Trainer functionality

| Feature | Status | Evidence / gap |
|---|---|---|
| Trainer dashboard | **Partial** | Route/view exists; operational completeness not verified. |
| Assigned-course scope | **Complete and verified** | Policies check `TrainerAssignment`; academic privacy tests exist. |
| Course/module/lesson management | **Implemented but not end-to-end tested** | `CourseManage` has substantive CRUD actions. |
| Groups/students | **Implemented but not end-to-end tested** | Group CRUD and student listing exist. |
| Session scheduling/cancellation | **Implemented but not end-to-end tested** | `Sessions` supports assigned-course-scoped schedule/cancel, validates group/student targets, and notifies the affected student audience. |
| Attendance | **Implemented but not end-to-end tested** | Save flow exists; authorization and privacy need browser validation. |
| Assignments/grading | **Implemented but not end-to-end tested** | Create, grade, return-for-revision, release flow, bounded scores, and idempotent release notification exist. |
| Quiz administration | **Implemented but not end-to-end tested** | Builder, publish, attempts, bounded manual marking, finalise, and idempotent result notification exist. |
| Resource visibility | **Implemented but not end-to-end tested** | Trainer-only material branch exists. |
| Availability scheduling | **Implemented but not end-to-end tested** | Add/remove availability exists. |
| Student performance monitoring | **Partial** | Student/attempt/certification views exist; no consolidated performance report. |
| Trainer notifications | **Partial** | Student-facing notification delivery is wired to trainer grading/session actions; a separate trainer notification inbox is not implemented. |

## Commerce and payments

| Feature | Status | Evidence / gap |
|---|---|---|
| Fixed-price variants | **Complete and verified** | Integer cents, currency, nullable pending prices, source provenance. |
| Approval/publication controls | **Complete and verified** | `CourseVariant::isPurchasable()` requires published course, published variant, approved status, and non-null price. |
| Checkout authentication | **Partial** | Checkout requires authenticated active user; guest checkout absent. This is acceptable only if SSO/login journey is finalized. |
| Server-side totals | **Complete and verified** | Order creation loads variant and computes price server-side. Client amount is not trusted. |
| PayPal sandbox checkout | **Implemented but not end-to-end tested** | REST create/capture code and gateway tests exist; real sandbox transaction not verified. |
| PayPal webhook signature | **Implemented but not end-to-end tested** | PayPal verification endpoint used; production webhook configuration unverified. |
| Payment idempotency | **Complete and verified** | Unique payment event key, paid-order guard, enrolment `firstOrCreate`; focused webhook tests. |
| Duplicate payment prevention | **Partial** | Event replay guarded, but gateway transaction uniqueness and concurrent payment-attempt policy need strengthening. |
| Payment error recovery | **Partial** | Cancel leaves pending; return catches capture errors; no retry/reconciliation UI or customer notification. |
| Refund service | **Implemented but not end-to-end tested** | `OrderService::refund()` calls gateway, bounds amounts, records `Refund`, logs audit, sends a queued student update, and suspends full-refund entitlements. |
| Refund entitlement handling | **Implemented but not end-to-end tested** | Full refunds suspend linked enrolments and set expiry; partial refunds preserve access by documented policy. |
| Order history | **Implemented but not end-to-end tested** | Admin and owner-scoped student history exist; browser and provider reconciliation remain. |
| Recurring subscriptions | **Missing** | Only one-time PayPal CAPTURE flow. No subscription models, plans, billing retries, or cancellation. |
| Institutional offers | **Partial** | Range/quote data exists as pending; no quote, invoice, approval, or institutional billing workflow. |
| PayPal Live | **Blocked by explicit policy** | Must remain disabled. `PAYPAL_MODE=sandbox` required. |

## Learning and certification

| Feature | Status | Evidence / gap |
|---|---|---|
| Lesson progression | **Implemented but not end-to-end tested** | Position/completion persistence is limited to active enrolments; browser flow remains unverified. |
| Completion eligibility | **Complete and verified** | Eligibility service checks lessons, quizzes, assignments, attendance, trainer/admin approvals according to policy; inactive enrolments cannot auto-issue certificates. |
| Assignment submission/grading | **Implemented but not end-to-end tested** | Submission/grading components enforce owner, assigned trainer, active-entitlement, score-range, and duplicate-submission protections; browser flow absent. |
| Quiz scoring | **Complete and verified** | Attempt lifecycle, active-entitlement enforcement, owner-only answers, scoring, and result release tests pass. |
| Correct-answer privacy | **Complete and verified** | Result release rules prevent premature answer/result exposure. |
| Attendance | **Implemented but not end-to-end tested** | Attendance model/component exists; edge-case coverage limited. |
| Certificate issuance | **Implemented but not end-to-end tested** | Eligibility, queued job, PDF storage, audit trail, idempotent issue/reissue, and student notifications. |
| PDF layout | **Complete and verified** | Prior certificate layout fix and certification tests. Production font/storage validation remains. |
| QR verification | **Complete and verified** | Non-sequential certificate reference and public verify route. |
| Revocation | **Implemented but not end-to-end tested** | Admin revoke and public revoked status exist. |
| Certificate audit trail | **Implemented but not end-to-end tested** | Audit model/component exists. |

# 2. Security findings

## Strengths

- Private app split from public docroot.
- `.env`, `APP_KEY`, vendor, and private storage excluded from deployment archives.
- Course purchase gate rechecks all publication and pricing conditions server-side.
- PayPal webhook verification uses PayPal verification endpoint, not client callback trust.
- Payment events are idempotency-protected by unique gateway event ID.
- Private materials pass authorization through controller/model checks.
- Trainer access is scoped through `TrainerAssignment` policies.
- Quiz results and correct answers have release controls.
- Certificate references are non-sequential and verification is rate-limited.
- Deploy runner uses IP allowlist, one-time consumed token, explicit expiry, fixed command allowlist, throttling, audit logging, execution locking, and per-command completion markers.

## P0/P1 security and integrity risks

1. **Refund/access mismatch — addressed in Phase 15A.** Completed full refunds now atomically suspend only enrolments linked to the refunded order; partial refunds preserve access by documented policy. Provider status, idempotency data, and immutable audit records are stored. Student messaging remains a later notification feature.
2. **Duplicate gateway transaction possibility — reduced in Phase 15A.** Payment initiation locks the pending order and reuses an existing initiated checkout transaction; event and enrolment uniqueness constraints remain active. MySQL race verification is still required.
3. **Deploy runner exposure — controlled temporary risk until retired.** IP restriction, one-time token consumption, explicit expiry, fixed command allowlist, audit logging, and an execution lock are enforced. Remove or disable the route after synchronization and never expose source archives or deploy tokens publicly.
4. **Production payment validation — P0.** Run PayPal sandbox capture, webhook signature, duplicate webhook, malformed payload, amount mismatch, currency mismatch, and delayed capture tests before launch.
5. **Queue dependency — P0 for certificates/async work.** `MaybeIssueCertificate` implements `ShouldQueue`; cron worker must be configured and monitored, or certificate issuance must have a safe retry/reconciliation path.
6. **Authentication lifecycle — P1.** No recovery or registration flow. Joomla SSO may cover public acquisition, but local staff/student recovery remains absent.
7. **Video privacy — P1.** Generic provider returns public embed URLs and signed playback returns `null`; do not represent this as protected streaming.

# 3. UI/UX deficiencies

- Admin dashboard has no meaningful metrics or pending-work queue.
- Admin lists lack full CRUD and clear empty/error states verification.
- No student profile, settings, order history, or recovery UI.
- No notification center or email status visibility.
- No reports or export surfaces.
- Refund and entitlement state are not visible to admins/students.
- Course shell offerings need explicit enquiry-only presentation until curriculum exists.
- Browser-level responsive and accessibility testing is absent.
- Payment pending/failure states need retry and support instructions.
- Trainer performance views are fragmented across components.

# 4. Production readiness

## Deployment

| Area | Status | Finding |
|---|---|---|
| Split paths | **Complete and verified** | Private app `/home/euroafri/eamn-academy/`; public docroot unchanged. |
| Bootstrap path | **Implemented but not end-to-end tested** | `public/index.php` resolves private `APP_BASE_PATH`; host validation pending. |
| `.env`/APP_KEY preservation | **Complete and verified** | Build excludes secrets. |
| Migrations | **Implemented but not end-to-end tested** | Two Phase 14 migrations; MySQL migration run still pending. |
| Private storage writable | **Blocked by external configuration** | Must validate cPanel permissions and `storage/framework` directories. |
| Cache/session | **Blocked by external configuration** | Database/file stores require tables and writable paths. |
| Queue | **Blocked by external configuration** | Requires cron `queue:work --stop-when-empty --max-time=50`. |
| Scheduler | **Blocked by external configuration** | Requires minute cron for `schedule:run`; no substantive scheduled tasks currently found. |
| PHP extensions | **Blocked by external configuration** | Need PHP 8.3 with `mbstring`, `openssl`, `pdo`, DB driver, `curl`, `fileinfo`, `xml`, `zip`, and GD/Imagick as required by PDF/QR stack. |
| Logging | **Blocked by external configuration** | Configure private log path, rotation, and monitoring. |
| Backups | **Blocked by external configuration** | cPanel/phpMyAdmin DB backup and private storage backup procedure required. |
| Debug mode | **Blocked by external configuration** | Production must set `APP_DEBUG=false`. |

## Required environment/configuration

- `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `APP_BASE_PATH`
- MySQL `DB_*`
- `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`
- PayPal sandbox credentials and webhook ID
- Joomla bridge base URL, client ID, secret, and code TTL
- SMTP/mail credentials if notifications are introduced
- `DEPLOY_ALLOWED_IPS` and one-time deploy token during migration only

## Production execution order

1. Back up database and private storage.
2. Upload private app archive only; preserve `.env` and `APP_KEY`.
3. Verify PHP extensions, writable directories, DB connection, `APP_DEBUG=false`.
4. Run `migrate --force` through gated runner.
5. Upload source tree to private `storage/app/import-source/`.
6. Run `catalogue:import`.
7. Run `catalogue:variants`.
8. Confirm all courses remain draft and all variants remain pending/unpublished.
9. Run smoke checks: `/up`, login, private-file denial, `.env` denial, checkout denial for pending offers.
10. Disable/remove migration runner and token after synchronization.
11. Do not approve variants, publish courses, edit Joomla, or enable PayPal Live.

# 5. End-to-end workflow audit

| Workflow | Status | Required acceptance |
|---|---|---|
| Admin creates course | **Partial** | Admin creates, edits, publishes, archives course without DB access. |
| Admin uploads lessons/resources | **Partial** | Admin or authorized trainer uploads, assigns visibility, replaces, deletes, downloads. |
| Trainer schedules class | **Implemented but not end-to-end tested** | Assigned trainer creates session, capacity/time validation works, student sees it. |
| Student registers | **Implemented but not end-to-end tested** | Student-only local registration and email verification are implemented; external mail delivery and browser journey remain. |
| Student purchases sandbox package | **Implemented but not end-to-end tested** | Approved fixed-price variant, PayPal sandbox capture, verified webhook, one order/enrolment. |
| Correct enrolment | **Complete and verified** | Fulfilment uses order item and unique enrolment constraint. |
| Lessons/progress | **Implemented but not end-to-end tested** | Unauthorized student blocked; completion persists across sessions. |
| Assignment submission | **Implemented but not end-to-end tested** | Student submits only own assignment; trainer sees assigned-course submission. |
| Trainer grading | **Implemented but not end-to-end tested** | Grade/revision/release transitions and privacy verified. |
| Quiz completion | **Complete and verified** | Score and release rules covered by tests. |
| Certificate eligibility | **Implemented but not end-to-end tested** | All configured completion requirements and approvals enforced. |
| Certificate issuance | **Implemented but not end-to-end tested** | Queue worker issues exactly one certificate; retry is safe. |
| QR verification | **Complete and verified** | Valid, revoked, unknown, and tampered refs return correct results. |
| Refund/revoked entitlement | **Implemented but not end-to-end tested** | Full refunds update provider/refund state, suspend linked access, audit the change, and queue an owner-scoped student notification; staging provider reconciliation remains. |
| Cross-student privacy | **Complete and verified in focused areas** | Policies/material tests cover core access; browser regression still required. |

# 6. Test coverage

Phase 15D direct verification on 2026-10-16: `php -d memory_limit=512M ./vendor/bin/phpunit` passed **168 tests, 338 assertions**. The normal Artisan test command remains subject to the known Dompdf child-process 128 MB constraint; direct PHPUnit is the successful full-suite command. Coverage includes:

- login and role middleware
- catalogue import and commercial approval
- course/enrolment access
- file authorization
- quiz engine and student privacy
- classroom access
- scheduling/booking
- commerce order service and webhook idempotency
- PayPal gateway behavior
- certificates
- Joomla bridge
- trainer management
- render smoke
- deploy runner

The suite includes PayPal mode/configuration and refund-entitlement regression coverage, deployment-runner expiry/replay coverage, Phase 15B administrator workflow coverage, and Phase 15D learning-integrity coverage. MySQL remains an external staging verification requirement.

Missing high-value tests:

- browser-level checkout and PayPal sandbox flow
- concurrent duplicate capture/payment attempts
- refund plus entitlement revocation against PayPal Sandbox
- MySQL migration and MySQL transaction behavior, including concurrent booking/payment attempts
- queue retry/idempotency plus notification delivery for certificate issuance
- registration/recovery browser and mail delivery workflows
- responsive/accessibility checks
- browser-level admin CRUD workflows
- upload limits, MIME validation, oversized files, and malformed source documents
- production-like private storage and cPanel path smoke tests

# 7. Proposed implementation order

## P0 — launch safety

1. Verify MySQL migrations and production bootstrap in staging.
2. Complete PayPal sandbox integration tests, including webhook and amount/currency integrity.
3. Validate gateway transaction uniqueness/concurrency protection under MySQL.
4. Validate refund entitlement policy, admin action, audit, student notification, and reconciliation.
5. Validate queue worker, certificate/notification retry, storage permissions, logs, backups, and deploy-runner retirement.

**P0 acceptance:** no client-supplied amount affects order; duplicate callbacks create no duplicate payment/enrolment; refund state and access state agree; certificate jobs retry safely; production secrets/private files remain inaccessible.

## P1 — agreed Academy experience

1. Complete admin CRUD: modules, lessons, material replacement, permission-level management, and remaining order/reconciliation operations. Programme/course/user/trainer-assignment/material inventory/enrolment workflows are implemented but require end-to-end browser verification.
2. Complete delivery of payment-pending/failure notifications and a dedicated dashboard recent-notifications panel.
3. Add trainer/admin notification inboxes only if operationally required.
4. Complete reports and operational dashboards.
5. Finish trainer/student browser workflows and responsive/accessibility checks.
6. Decide curriculum or enquiry-only status for empty course shells.
7. Add protected video provider or explicitly limit product to public embeds.

**P1 acceptance:** administrator completes normal catalogue and commerce operations without database edits; student completes purchase-to-certificate journey; trainer manages assigned delivery; all private data remains scoped.

## P2 — optional enhancements

- PayPal subscriptions or another recurring billing provider.
- Institutional quotes/invoices and purchase orders.
- Real-time classroom/video infrastructure.
- Advanced analytics/export.
- Full browser automation and visual regression suite.

# 8. Direct verification notes (2026-10-16)

- Working tree: Phase 15A commit `69b8798`; Phase 15B administrator portal changes are currently uncommitted.
- Expected authenticated student, trainer, and admin Livewire routes are present, including student account, learning, tutoring, certificates, and trainer operations; checkout, webhook, certificate, file, bridge, and migration routes are also present.
- `bootstrap/app.php` registers `SecurityHeaders` on the web middleware stack and aliases `active`/`role`; this previously unverified security control is confirmed in code.
- `composer show --direct`: Laravel 13.36.0, Livewire 4.4.7, Dompdf 3.1.2, Endroid QR Code 6.0.9. The installed vendor tree omits development packages despite `composer.json` declaring PHPUnit 12.5 and Pint 1.27 under `require-dev`.
- `php artisan migrate:status`: all migrations, including the commercial catalogue migrations, are applied to the local SQLite database. This does not validate MySQL compatibility or production execution.
- Phase 15A corrected PayPal live detection to require explicit `PAYPAL_MODE=live`; sandbox and invalid modes fail closed, incomplete credentials are rejected before HTTP, and the admin Integrations view reports the configured environment.
- Payment initiation now locks the pending order and reuses an existing initiated checkout transaction, reducing duplicate provider orders. Webhook/event and enrolment uniqueness constraints remain in force; MySQL concurrency verification is still required.
- Refund processing now requires provider `COMPLETED`/`PENDING` status, stores provider and idempotency data, records audit events, suspends only order-linked enrolments on a completed full refund, and preserves access for partial refunds by documented policy. Admin Orders exposes the authorised refund action. Duplicate refund attempts reuse the idempotency record.
- Phase 15B adds administrator programme and course lifecycle editing, trainer assignment, searchable role-filtered user administration, last-super-admin safeguards, manual non-commercial enrolment management, a private-material inventory/deletion surface, and database-backed operational reports. Modules, lessons, uploads, replacement, ordering, and private audience controls remain in the existing authorised trainer course-management workflow; imported `source_key` fields are not editable through the new administration forms.
- Phase 15C/15D provide `app/Notifications`, student order/history/profile/recovery routes, a notification inbox, notification preferences, and queued database/mail delivery. Phase 15D additionally wires assignment grade, manual quiz result, live-session schedule/cancellation, tutoring booking/cancellation, full-refund entitlement change, and certificate issue/reissue notifications. No scheduled reminder job exists.

# 9. External dependencies

- InterServer cPanel/File Manager and fixed docroot behavior.
- MySQL production database.
- PayPal sandbox credentials, webhook registration, and reachable HTTPS callback.
- Joomla bridge plugin, client ID, secret, and lifecycle event emitter.
- SMTP provider and configured from address for queued mail notifications.
- Cron execution for database queue worker; scheduled reminders are not implemented.
- PHP 8.3 extensions and writable private storage.
- Product decisions for empty course shells, prices/ranges, refund access policy, and institutional offers.

# 9. Hard stops

- Do not modify Joomla pages.
- Do not enable PayPal Live.
- Do not approve or publish commercial variants before pricing/content review.
- Do not expose `.env`, deploy token, source archive, vendor, or private storage.
- Do not claim recurring billing, protected streaming, reports, notifications, or full admin management until implemented and tested.
