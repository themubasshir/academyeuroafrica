# EAMN Academy — Architecture

## Stack
- Laravel 13 (PHP 8.3), MySQL/InnoDB prod, SQLite dev/tests.
- Livewire 4 (full-page components) + Alpine + Tailwind 4 via Vite (compiled locally).
- Queue: `database` driver, cron-driven `queue:work --stop-when-empty`.
- Cache/session: `database`/`file`. No Redis, Supervisor, Node, WebSockets on prod.

## Boundaries
- HTTP layer (controllers, Livewire) is thin. Business logic lives in `app/Services/**` and domain actions — never in controllers or Blade.
- Authorisation: `EnsureRole` middleware for area gating, Policies for per-model access. `Gate::before` grants all to `super-admin`.
- Student privacy: policies scope every per-student record; queries filtered by `enrolment.user_id` / `submission.user_id`, never frontend hiding.

## Identity
Primary sign-in = Joomla bridge SSO (`Services\Joomla\BridgeClient` + `BridgeAuth`). Local password login retained for staff fallback and dev. `external_identities` maps `joomla` subject → `users.id`. Email collisions → `identity_conflicts` (manual resolution, no auto-merge).

## Roles
`super-admin`, `academy-admin`, `trainer`, `student` — own pivot tables, no external RBAC package.

## Key subsystems (built in phases)
| Phase | Area | Services/Models |
|---|---|---|
| 2 | Academic | Programme → Course → CourseVariant; Module → Lesson; Material; Enrolment |
| 3 | Commerce | Order → OrderItem; `PaymentGateway` contract, Stripe + test adapter; payment_events idempotency |
| 4 | Classroom | LiveSession, Group, GroupMember, Announcement |
| 5 | Assessment | Assignment→Submission→Grade; Quiz→Question→Option→Attempt |
| 6 | Private training | TrainerAvailability, Appointment, SessionCreditLedger (append-only) |
| 7 | Joomla | plugin `com_eamnbridge`, `BridgeStart`/`Consume`/`Events` controllers |
| 8 | Hardening | `SecurityHeaders`, `MigrateRunnerController` |
| 9 | Functional UI | File delivery (`FileService`+`MaterialController`), student workspace (`learn/*`), trainer CRUD, admin lists, `VideoProvider`/`VideoManager`/`GenericProvider`, booking UI |

## Content vs progress
Course content is shared (one copy). Per-student state (enrolment, lesson_progress, quiz_attempts, submissions) lives in separate rows keyed by user — never duplicated content.

## Files
- Private materials/submissions on `local` disk, randomised stored names, MIME allowlist, served via authorised controller — never public web root.
- `Domain\Files\FileService` stores uploads; `Http\Controllers\Files\MaterialController` authorises per-download (enrolled student / assigned trainer / admin).
- Meeting URLs stored encrypted, exposed only to eligible users.

## Video
`Services\Video\Contracts\VideoProvider` + `VideoManager` resolve an embed/playback URL per `course_videos.provider`. `GenericProvider` covers YouTube-nocookie/Vimeo/direct embed with no credentials. A real provider (Vimeo/Cloudflare Stream) is wired via the same contract once account+cost is chosen — playback always stays behind enrolment authorisation; no transcoding on shared hosting.

## Status — implemented vs pending credentials
- **Working end-to-end (dev/test):** auth+roles, bridge SSO (contract-tested), catalogue, checkout (test gateway), enrolment, student workspace, files, trainer CRUD, quiz engine+builder, booking+credits, admin lists.
- **Needs prod credentials/config:** real Stripe keys + live webhook, Joomla staging install of `com_eamnbridge` + lifecycle emitter + 7 page mappings, real video provider account, InterServer `.env` + cron.
