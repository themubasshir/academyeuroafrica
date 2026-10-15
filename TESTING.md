# Testing

- Runner: PHPUnit (`php artisan test` / `vendor/bin/phpunit`).
- DB: SQLite in-memory via `RefreshDatabase`.
- Factories: `UserFactory` + role states (`->student() ->trainer() ->academyAdmin() ->superAdmin()`); factories for Programme, Course, CourseVariant, Enrolment, Group, LiveSession, Assignment, AssignmentSubmission, Quiz, QuizQuestion, QuizAttempt, Appointment, TrainerAvailability.
- Livewire: `Livewire::actingAs($user)->test(Class, params)->call(...)` for component actions; real HTTP `get(route(...))` for render smoke.

## Current coverage — 90 tests / 153 assertions (all passing)

| File | What it proves |
|---|---|
| `tests/Feature/Auth/LoginTest` | login, wrong password, disabled-account block at login + per-request, throttle |
| `tests/Feature/RoleMiddlewareTest` | guest redirect, per-role area gating, super-admin |
| `tests/Feature/Joomla/BridgeAuthTest` | HMAC-signed exchange call, claims parsing, failure path |
| `tests/Feature/Joomla/BridgeFlowTest` | state issue+verify, wrong-state 403, mapped login, new-student provision, email-collision → `identity_conflicts` (not merge), signed lifecycle-disable event |
| `tests/Feature/RenderSmokeTest` | all student/trainer/admin surfaces render via HTTP; role boundaries; guest redirects |
| `tests/Feature/StudentWorkspaceTest` | workspace auth, cross-student 403, lesson `markComplete` |
| `tests/Feature/FileAuthorizationTest` | enrolled download, non-enrolled 403, guest redirect, MIME allowlist |
| `tests/Feature/TrainerManageTest` | module/lesson save, unassigned-trainer 403, quiz question create, availability add |
| *(Phases 2–8 suites)* | enrolment unique, webhook idempotency, IDOR policies, quiz engine, double-booking, credit ledger, refund, deploy runner, security headers |

## Still to add / live-verify
- Stripe real-webhook contract test (needs test-mode signing secret).
- Live Joomla round-trip on a staging site (plugin install, real session).
- Browser-level (Dusk/Playwright) for JS interactions — currently server-render only.
