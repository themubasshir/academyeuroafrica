# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Three confirmed roles use this private portal (no public registration):

- **Students** — paying adult learners across Africa and Europe. Job: access purchased courses, attend live/prerecorded lessons, submit assignments, take quizzes, view private results, book 1-on-1 training. Predominantly arrive already authenticated via Joomla SSO after a course purchase.
- **Trainers** — working professionals delivering courses. Job: build/manage course content, run live sessions, grade assignments, build quizzes, track student progress, manage private-training availability.
- **Administrators** (`super-admin`, `academy-admin`) — operate programmes, courses, users, enrolments, orders, integrations and audit logs.

## Product Purpose

EAMN Academy is the **private learning portal** for EuroAfrica Media Network. It is not a marketing site: the public Joomla website (euroafricamedia.eu) handles course marketing. The academy's job is delivery — enrolment-gated access to course content, live sessions, assessment, private training, and the session-credit ledger. Success means a paying member reaches their learning within seconds of purchase, and staff administer the catalogue efficiently.

## Positioning

The only learning portal bound to the EuroAfrica Media Joomla identity. Single sign-on carries a member's purchase straight into their dashboard; no neighbouring LMS shares that identity map, the session-credit ledger, or the Africa–Europe intercultural-competence catalogue.

## Operating Context

- Guest → lands on branded login. Authenticated → role-scoped dashboard (student / trainer / admin).
- New accounts are created only as part of a Joomla course purchase — never a standalone public registration flow. Course/purchase intent must be preserved through Joomla auth → account creation → checkout.
- Live sessions are hosted externally (Zoom/Teams); meeting URLs stored encrypted.
- Video delivered via a pluggable `VideoProvider` behind enrolment authorisation.
- Deployed to InterServer shared hosting (no SSH, Redis, Supervisor, Node on prod).

## Capabilities and Constraints

Confirmed, must preserve:

- Laravel 13, PHP 8.3, Livewire 4 (full-page components), Alpine, Tailwind 4, Vite.
- Joomla bridge SSO (`BridgeStart`/`Consume`/`Events`); `external_identities` maps joomla subject → `users.id`. Local password login kept for staff fallback + dev.
- PayPal REST Orders v2 checkout (CAPTURE), server-side capture, verified webhooks, idempotent fulfil, refunds. Test gateway exists for local.
- Role-based access: `EnsureRole` middleware + Policies; `Gate::before` grants all to `super-admin`. `active` middleware drops disabled accounts.
- Per-student privacy: enrolment/lesson_progress/quiz_attempts/submissions scoped by user — never frontend hiding.
- Programmes → Courses → CourseVariants; Modules → Lessons; Materials; Assignments→Submissions→Grades; Quizzes→Questions→Options→Attempts; LiveSessions, Groups, Announcements; TrainerAvailability, Appointments, append-only SessionCreditLedger.
- Private file delivery via authorised controller (MIME allowlist, randomised stored names), never public web root.
- Audit logs; deploy migration runner (IP+token gated).

Constraints: do not break PayPal, Joomla identity mapping, role access, enrolments, video delivery, grading, bookings, or the credit ledger. No architecture replacement.

## Brand Commitments

- **Official logo (binding):** `https://euroafricamedia.eu/images/eman.png` — PNG 291×55, RGBA transparent. Used by Joomla in the site header, off-canvas menu and footer. Stored locally at `public/brand/eman-logo.png`. Do not recreate or approximate it.
- **Academy lockup:** `[eman.png logo]` + the word **Academy** in complementary type. Reusable brand component used on login, app nav, student/trainer/admin dashboards, checkout, mobile nav.
- **Name:** EAMN Academy / EuroAfrica Media Network. Public copy tone: institutional, internationally credible, Africa–Europe bridging.
- Site brand signals (from Joomla template): deep institutional blue header `#08355c`, link blue `#0345bf`, menu on white. Display/body faces on the public site: Inter, Roboto.

## Evidence on Hand

- Real logo asset: `public/brand/eman-logo.png`.
- Real catalogue data comes from the DB (seeders). No fabricated testimonials/pricing; course/programme names are real records.
- Public marketing copy lives on the Joomla site, not here.

## Product Principles

1. **Portal, not brochure.** Every screen serves a signed-in task; marketing is out of scope.
2. **Identity carries trust.** The authentic EuroAfrica mark + "Academy" appears consistently at every entry point.
3. **Calm for learners, efficient for staff.** Density and hierarchy tuned per role, one shared visual identity.
4. **Purchase → learn without friction.** Joomla SSO and PayPal flows stay unbroken; enrolment state is sacred.
5. **Privacy by construction.** Per-student data scoped at the query level; the UI never relies on hiding.

## Accessibility & Inclusion

WCAG AA target: ≥4.5:1 body contrast, ≥3:1 large text, visible focus rings, ≥44px touch targets, labelled controls, `prefers-reduced-motion` honoured. International audience — plain language, no unexplained jargon.
