# PHASE 15E — Browser Acceptance

Date: 2026-10-17 (rerun after P0 fix)
Commit tested: working tree incl. Livewire/CSP fix
Environment: local Laravel app at `http://127.0.0.1:8000`, SQLite, deterministic local-only fixture (`academy:acceptance-fixture`).

## Scope and safety

- Joomla not modified.
- Production not touched.
- PayPal Live not enabled.
- Fixture is local/testing-only (`app()->environment(['local','testing'])` guard); no production seeders touched.
- No real customer data used; fixture accounts use `@eamn.test` addresses, credentials not repeated here.
- Browser automation used Playwright Chromium installed outside project dependencies. No Playwright package/config added to project.

## P0 defects found and fixed this run

Two related defects made **every** Livewire interaction dead in a real browser while still rendering HTTP-200 pages. All earlier "rendered" passes were misleading: buttons clicked but committed nothing.

| # | Defect | Evidence | Fix |
|---|--------|----------|-----|
| 1 | Livewire runtime never loaded. Layout emitted only `@livewireScriptConfig` (config blob), never the `livewire.js` script tag. `window.Livewire === undefined`. | `document.querySelector` showed no livewire script src; `typeof window.Livewire === "undefined"`; zero POSTs to `livewire-*/update` on any `wire:click`. | `app.blade.php:71` changed `@livewireScriptConfig` → `@livewireScripts` (emits script tag + config). |
| 2 | After script loaded, every `wire:click`/`x-on` action threw `EvalError`: CSP `script-src 'self' 'unsafe-inline'` lacks `unsafe-eval`, blocking Alpine's `new Function` evaluator. | Browser `PAGE-ERR: Evaluating a string as JavaScript violates CSP ... 'unsafe-eval' is not allowed` on every click; no `livewire-*/update` request fired. | `config/livewire.php` `csp_safe => env('LIVEWIRE_CSP_SAFE', true)` → serves `livewire.csp.min.js` (evaluator-based, no `new Function`). Keeps strict CSP. |

Verification of fix: `window.Livewire` defined; `wire:click="markComplete"` → POST `livewire-*/update` 200 → `lesson_progress` row persisted. Mobile nav drawer (`x-data`/`@click`/`x-show` expressions) works under CSP build.

Supporting deploy change: `composer.json` `post-update-cmd` now runs `livewire:publish --assets` so the CSP asset exists on deploy; `/public/vendor` gitignored; `acceptance-screenshots*` gitignored.

New fixture: `php artisan academy:acceptance-fixture` — idempotent (updateOrCreate/firstOrCreate keyed by slug/email), guards non-local env, resets mutable attempt/progress state for repeatability, emits JSON metadata (IDs/routes) without password. Covered by `tests/Feature/AcceptanceFixtureCommandTest.php` (idempotency + non-local refusal).

## Browser-tested workflows (Playwright Chromium, 33 checks)

Results JSON: `acceptance-screenshots-15e1/results.json`. Screenshots in same dir (gitignored). **33 PASS / 0 FAIL.**

### Student
| Workflow | Status | Evidence |
|---|---|---|
| Login → `/dashboard` | PASS | reached dashboard |
| Dashboard shows fixture course | PASS | `01-student-dashboard.png` |
| Course open via dashboard-rendered link | PASS | `02-student-course.png` (real `/learn/{enrolment}` href, not hardcoded ID) |
| Lesson open via Lessons tab | PASS | navigated to lesson route |
| Mark complete persists | PASS | `lesson_progress` row created; checkmark rendered on lessons tab (`03-student-lesson-complete.png`) |
| Progress counter increments | PASS | overview shows 1/2 completed |
| Materials tab renders student material | PASS | `04-student-materials.png` |
| Authorised material download | PASS | `files/materials/{id}` → 200 + expected content |
| Trainer-only material denied | PASS | `files/materials/{trainer_id}` → 403 |
| Assignment open + submit | PASS | `assignment_submissions` row persisted with body (`05-student-assignment-submitted.png`) |
| Quiz start + answer + submit | PASS | `Submitted` state + attempt persisted (`06-student-quiz-submitted.png`) |
| Results page shows quiz score | PASS | `07-student-results.png` |
| Logout | PASS | returned to `/login` |

### Trainer
| Workflow | Status | Evidence |
|---|---|---|
| Login → `/trainer/dashboard` | PASS | `08-trainer-dashboard.png` |
| Assigned course manage access | PASS | `09-trainer-manage.png` |
| Module creation via form | PASS | "Browser Added Module" persisted |
| Attendance record + save | PASS | `10-trainer-attendance.png`, saved flash |
| Assignment grading + release | PASS | `11-trainer-grading.png`, grade released |
| Quiz attempts page | PASS | `12-trainer-quiz-attempts.png` |
| Student progress list | PASS | "Acceptance Student" listed |
| Unassigned course access | PASS | `trainer/courses/{unassigned}/manage` → 403 |
| Logout | PASS | returned to `/login` |

### Admin
| Workflow | Status | Evidence |
|---|---|---|
| Login → `/admin/dashboard` | PASS | reached dashboard |
| Programme create + edit | PASS | "Browser Mutation Programme Edited" persisted |
| Course create + trainer assign | PASS | "Browser Mutation Course" persisted |
| Variant (package) edit | PASS | "Acceptance Standard Edited" persisted (`13-admin-packages.png`) |
| Logout | PASS | returned to `/login` |

### Security negatives
| Check | Status | Evidence |
|---|---|---|
| Unauthenticated `/admin/dashboard` | PASS | 302 → `/login` |
| CSRF missing token POST `/login` | PASS | 419 |
| Student → trainer-only material | PASS | 403 |
| Trainer → unassigned course | PASS | 403 |

### Responsive (mobile drawer verified functional under CSP build)
| Viewport | Page | Overflow | Narrow text fields | Status |
|---|---|---|---|---|
| 768px | dashboard / course / profile | none | none | PASS |
| 390px | dashboard / course / profile | none | none | PASS |
| 390px | nav drawer open/close | — | — | PASS (`aria-expanded` toggles, no page errors) |

Note: responsive check excludes checkbox/radio/file inputs from the 150px text-field-width rule (they're legitimately small); all text inputs/selects/textareas were wide enough, no horizontal overflow.

## Not exercised / still blocked

| Item | Status | Reason |
|---|---|---|
| PayPal Sandbox checkout/capture/refund/webhook | BLOCKED | `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, `PAYPAL_WEBHOOK_ID` unset locally; no reachable sandbox callback |
| Email verification real-mail flow | BLOCKED | `MAIL_MAILER=log`; fixture users pre-verified |
| Password recovery/change browser mutation | NOT CLAIMED | not exercised this run |
| Certificate issuance/download | NOT CLAIMED | no issued certificate in fixture |
| Booking/cancellation | NOT CLAIMED | not exercised |
| Local test checkout | NOT CLAIMED | not substituted for PayPal acceptance |
| Full keyboard-only + screen-reader audit | PARTIAL | labels/focus/overflow verified; no dedicated AT run |

## Findings and defects

1. **P0 (fixed):** Livewire interactivity dead app-wide — missing script tag + CSP eval block. See table above.
2. No remaining reproducible P0/P1 defect after fix.
3. Probe-only issues corrected in harness (Livewire round-trip wait, dynamic route discovery, CSP-aware field-width rule, fixture state reset) — these were test-harness defects, not app defects.

## Final verification

```text
php -d memory_limit=512M ./vendor/bin/phpunit
# OK (170 tests, 352 assertions)
vendor/bin/pint --dirty --format agent
# passed
git diff --check
# passed
php artisan academy:acceptance-fixture --no-interaction
# idempotent; JSON metadata emitted; non-local env refused
```

## Readiness recommendation

### Client demonstration
**READY (local).** Student, trainer, and admin core learning workflows verified end-to-end in real Chromium against a deterministic local fixture: enrolment workspace, lesson completion, material download with authorization, assignment submit→grade→release, quiz attempt→result, attendance, admin mutations, role-based 403s, and mobile drawer/responsive at 390/768px. Do not demo payment flows or claim certification/AT accessibility coverage.

### Production deployment
**CONDITIONAL.** Application-level P0 fixed and browser-verified. Remaining gates: PayPal Sandbox credentials + reachable callback for payment acceptance; real-mail verification path; accessibility/AT audit; certificate/booking flows. Deploy `livewire:publish --assets` step is wired via `post-update-cmd`.
