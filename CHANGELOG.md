# Changelog

All phases committed to `main`. Dates reflect commit history.

## Phase 9 — Functional completion & integration hardening (2026-10-15)

### Implemented
- **File delivery** — `FileService` (MIME allowlist, 25 MB cap, randomised
  private-disk paths) + `MaterialController` authorising enrolled-student /
  assigned-trainer / admin downloads; submission attachments via
  `AssignmentSubmissionPolicy`.
- **Student workspace** — `learn/{enrolment}` course overview (curriculum,
  live sessions, assignments, quizzes, materials), lesson page with video
  embed + resume position + mark-complete, assignment submission w/ upload,
  quiz-taking UI, My Results, Private Training booking UI.
- **Trainer area** — dashboard with pending-grading count; per-course manage
  (modules/lessons/order/materials), sessions, groups, assignments,
  grading/release, quizzes list, full quiz builder (question types, options,
  correct marks, ordering, time/attempt/pass limits, release rules,
  draft/publish), attempt review + manual grading, student progress,
  availability editor.
- **Admin area** — programmes, courses (delivery-type checkboxes,
  draft/publish), users (search, enable/disable), orders, enrolments,
  integrations (Joomla/Stripe config check + page-map table), audit logs.
- **Video abstraction** — `VideoProvider` contract + `VideoManager` +
  `GenericProvider` (YouTube-nocookie / Vimeo / direct embed). Real provider
  (Vimeo/Cloudflare) deferred until account+cost chosen.
- **Joomla bridge hardening** — `auth/bridge/start` now generates + stores
  `state` in the academy session before redirecting to Joomla `go`; `go`
  echoes it and survives the Joomla-login redirect. Fixes CSRF-substitution
  hole where `state` was never verified. `BridgeEventsController` accepts
  HMAC-signed lifecycle events (email_changed / disabled / deleted) at
  `POST auth/bridge/events`.
- **Identity-collision fix** — conflict row now written *outside* the user
  transaction so it persists when the merge aborts (was rolled back).
- **Joomla package fix** — manifest `<files>`/`<install>`/`<uninstall>` paths
  corrected (`component/` prefix), `uninstall.sql` added; repackaged
  `joomla/com_eamnbridge.zip` (valid installable zip).
- **Deployment** — `build.sh`: PHP-extension preflight, test gate,
  `composer --no-dev`, `npm ci && build`, clean stage (no `.git`/`tests`/
  `.env`/dev artefacts), prod `.env` template injection, tarball + manifest +
  SHA-256 checksums, rollback note.

### Tests — 90 passing, 153 assertions
- `RenderSmokeTest` (14) — every student/trainer/admin Livewire surface
  renders via real HTTP; role boundaries + guest redirects.
- `StudentWorkspaceTest` (4) — workspace auth, cross-student 403,
  mark-complete writes `lesson_progress`.
- `FileAuthorizationTest` (4) — enrolled download, non-enrolled 403, guest
  redirect, MIME rejection.
- `TrainerManageTest` (4) — assigned-trainer module/lesson save, unassigned
  403, quiz question create, availability add.
- `Joomla/BridgeFlowTest` (6) — state issue, wrong-state 403, mapped login,
  new-student provision, email-collision conflict (not merge), signed
  lifecycle-disable event.

## Phase 8 — Hardening & docs (2026-10-15)
- `SecurityHeaders` middleware (web group).
- `MigrateRunnerController` — IP allowlist + signed token + allowlist +
  self-disabling one-shot `migrate` route (`deploy/migrate`). Not a general
  Artisan endpoint.
- Full docs: README, ARCHITECTURE, DATABASE, SECURITY, JOOMLA_INTEGRATION,
  DEPLOYMENT_INTERSERVER, API, TESTING, CHANGELOG.

## Phase 7 — Joomla bridge (2026-10-15)
- `com_eamnbridge` Joomla 4 component (`go` code-mint + `exchange`
  HMAC-verified server endpoint), `#__eamnbridge_codes` table, install.sql,
  packaged `com_eamnbridge.zip`.

## Phase 6 — Private training (2026-10-15)
- `trainer_availability`, `appointments`, `session_credit_ledger` (append-only).
- `BookingService` — availability + overlap check inside transaction lock
  (DB-agnostic), credit deduction, >24 h cancellation refund rule.

## Phase 5 — Assessment (2026-10-15)
- Assignments + recipients (course/group/student targeting) + submissions +
  attachments + grades (draft/released/returned).
- Quizzes + questions + options + attempts + answers; `QuizService` timing,
  attempt limits, auto-grade (mcq/multi/tf/short), manual essay, release rules.
- `AssignmentSubmissionPolicy` / `QuizAttemptPolicy` + cross-student IDOR tests.

## Phase 4 — Classroom (2026-10-15)
- `groups`, `group_members`, `live_sessions` (encrypted `meeting_url`,
  `visibleTo()` audience rules), `announcements`.

## Phase 3 — Commerce (2026-10-15)
- `orders`/`order_items`/`payment_transactions`/`payment_events`/`refunds`.
- `PaymentGateway` contract, `StripeGateway` (REST), `TestGateway`
  (local/testing only), `PaymentManager`, `OrderService` idempotent fulfil.
- Checkout routes + CSRF-exempt `webhooks/{gateway}`.

## Phase 2 — Academic core (2026-10-15)
- programmes→courses→variants→modules→lessons, polymorphic materials,
  enrolments (UNIQUE user+variant), trainer_assignments, lesson_progress,
  policies, catalogue routes, student dashboard.

## Phase 1 — Foundation (2026-10-15)
- roles/permissions pivot tables, `EnsureUserIsActive`/`EnsureRole` middleware
  (`active`, `role`), `Gate::before` super-admin, local login, bridge consume
  endpoint, layout/sidebar/dashboards, `audit_logs`.
