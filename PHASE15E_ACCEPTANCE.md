# PHASE 15E — Browser Acceptance

Date: 2026-10-17
Commit tested: `fe1c734`
Environment: local Laravel app at `http://127.0.0.1:8000`, SQLite, local/demo data only.

## Scope and safety

- Joomla not modified.
- Production not touched.
- PayPal Live not enabled.
- Demo seeder ran only in local environment.
- No real customer data used.
- Development accounts were used from `DemoSeeder`; credentials are not repeated here.
- Browser automation used Playwright Chromium installed outside project dependencies. No Playwright package or config added to project.

## Preparation

| Check | Status | Evidence |
|---|---|---|
| Laravel development server | PASS | `php artisan serve --host=127.0.0.1 --port=8000`; `/login` returned HTTP 200 |
| Database migrations | PASS | `php artisan migrate:status --no-interaction`; all listed migrations `[1] Ran` |
| Local demo accounts | PASS | `php artisan db:seed --class=DemoSeeder --no-interaction`; active verified admin, student, trainer confirmed without exposing credentials |
| Frontend assets | PASS | `npm run build` completed; Vite manifest/assets generated |
| Browser automation | PASS | Playwright Chromium drove local browser flows |

## Browser-tested workflows

Screenshots are stored in `acceptance-screenshots/`.

### Administrator

| Workflow | Status | Notes |
|---|---|---|
| Login | PASS | Browser form submission reached `/admin/dashboard` |
| Dashboard | PASS | HTTP 200 and dashboard content rendered |
| Programme management UI | PASS | Create form and seeded programme list rendered; edit/archive controls present |
| Course management UI | PASS | Create form and seeded course list rendered; curriculum/edit controls present |
| Trainer assignment UI | PASS | Course form exposed primary trainer selection; seeded trainer visible |
| Material visibility UI | PASS | Material library rendered enrolled/group/student-targeted records |
| User management UI | PASS | User page rendered |
| Enrolment management UI | PASS | Enrolment page rendered |
| Commercial variants | PASS | Package page rendered pending/published controls and approve actions |
| Orders/refunds UI | PASS | Orders page rendered |
| Reports | PASS | Operational reports and metrics rendered |
| Logout | PASS | Browser logout returned to `/login` |
| Actual create/edit/upload/approve mutation flows | NOT CLAIMED | Not executed in this run; no mutation was made to seeded demo records |

### Student

| Workflow | Status | Notes |
|---|---|---|
| Login | PASS | Browser form submission reached `/dashboard` |
| Logout | PASS | Browser logout returned to `/login` |
| Dashboard | PASS | Welcome, enrolled courses, live classes, assignment due item rendered |
| Profile | PASS | Profile page rendered |
| Orders | PASS | Order history page rendered |
| Notifications | PASS | Notifications page rendered |
| Results | PASS | Results page rendered |
| Private training | PASS | Training page rendered |
| Certificates | PASS | Certificates page rendered |
| Course access | BLOCKED | Seeded enrolment IDs did not match hard-coded probe path `/learn/2`; route itself returned HTTP 200 but expected course title was not present. The student dashboard showed both enrolled courses and browser links must be followed in a corrected run. |
| Lesson progression | NOT CLAIMED | No successful lesson interaction recorded |
| Private material downloads | NOT CLAIMED | No successful download recorded |
| Assignment submission | NOT CLAIMED | No seeded assignment browser submission completed |
| Quiz attempts/results | NOT CLAIMED | No seeded quiz browser attempt completed |
| Email verification | BLOCKED | Demo users are already verified; no real mail transport available (`MAIL_MAILER=log`) |
| Password recovery/change | NOT CLAIMED | No browser mutation completed |
| Certificates download | NOT CLAIMED | No issued certificate in seeded state |

### Trainer

| Workflow | Status | Notes |
|---|---|---|
| Login | PASS | Browser form submission reached `/trainer/dashboard` |
| Dashboard | PASS | Trainer dashboard rendered |
| Assigned courses | PASS | Assigned course management page rendered |
| Lesson management | PASS | Course management page rendered |
| Live-session scheduling UI | PASS | Sessions page returned HTTP 200 and rendered session UI |
| Attendance | NOT CLAIMED | No attendance mutation completed |
| Assignment grading UI | NOT CLAIMED | No seeded submission grading completed |
| Quiz administration UI | PASS | Quiz administration page rendered |
| Student performance UI | PASS | Students page rendered |
| Tutoring availability | PASS | Availability page rendered |
| Booking/cancellation | NOT CLAIMED | No browser booking/cancellation mutation completed |
| Unassigned course access | NOT CLAIMED | Authorization negative test not completed |
| Other students’ private records | NOT CLAIMED | Authorization negative test not completed |
| Logout | PASS | Browser logout returned to `/login` |

## Responsive and accessibility

| Viewport | Status | Findings |
|---|---|---|
| Desktop 1440 px | PASS | Screenshots captured for admin, student, trainer pages |
| Tablet 768 px | PASS | Login page rendered without horizontal overflow; labels present; initial focus landed on Email. Screenshot: `acceptance-screenshots/responsive-login-768.png` |
| Mobile 390 px | PASS | Login page rendered without horizontal overflow; labels present; initial focus landed on Email. Screenshot: `acceptance-screenshots/responsive-login-390.png` |
| Keyboard navigation | PASS (basic login check) | Login page initial focus landed on Email; full keyboard-only role-page run not completed |
| Labels/error feedback/focus | PASS (basic login check) | Email, Password, Remember me labels present and focus visible state was inspectable; full role-page accessibility audit not recorded |

## Payment acceptance

| Test | Status | Reason |
|---|---|---|
| PayPal Sandbox checkout | BLOCKED | `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, and `PAYPAL_WEBHOOK_ID` are unset locally |
| Successful capture | BLOCKED | Sandbox credentials unavailable |
| Duplicate callback | BLOCKED | Reachable external callback and sandbox event unavailable |
| Enrolment creation from payment | BLOCKED | Sandbox checkout unavailable |
| Refund | BLOCKED | Sandbox payment unavailable |
| Entitlement suspension | BLOCKED | Sandbox payment unavailable |
| Failed payment | BLOCKED | Sandbox payment unavailable |
| Local test checkout | NOT CLAIMED | Not substituted for requested PayPal Sandbox acceptance |

## Findings and defects

1. **Probe defect, not application defect:** initial automation used `/learn/2` as a fixed path and expected Radio Presentation Fundamentals. Seeded enrolment IDs are not stable across existing local database state. Correct automation must obtain the actual `href` from the student dashboard. No code fix applied.
2. No reproducible P0/P1 application defect was established from completed browser checks.
3. No verified defect warranted a code change. No regression test added.

## Final verification

```text
php -d memory_limit=512M ./vendor/bin/phpunit
# OK (168 tests, 338 assertions)
vendor/bin/pint --dirty --format agent
# passed
git diff --check
# passed
```

## Readiness recommendation

### Client demonstration

**CONDITIONAL.** Core authenticated dashboards and role landing pages rendered successfully in real Chromium interactions at desktop size. Demo must avoid claiming untested mutation flows, responsive/accessibility completion, payment completion, or certificate/lesson/quiz/assignment success. Run corrected course-link flow before demonstration.

### Production deployment

**NOT READY / BLOCKED.** Required browser coverage is incomplete; tablet/mobile and dedicated accessibility checks are untested; payment Sandbox credentials/callback are unavailable; several requested student/trainer mutation and authorization-negative workflows are untested. Do not deploy based on this acceptance run.
