# Changelog

## [Unreleased] — Phase 1–7 build-out
- Phase 1: Foundation — Laravel 13 + Livewire 4 + Tailwind 4, custom role system
  (no package), `active`/`role` middleware, Joomla bridge client + consume
  endpoint, local login fallback, base layout + dashboards, audit logs.
- Phase 2: Academic core — programmes→courses→variants, modules→lessons,
  materials (polymorphic), enrolments (unique per user+variant), trainer
  assignments, lesson progress, policies, public catalogue + student dashboard.
- Phase 3: Commerce — orders/order_items, `PaymentGateway` contract, Stripe
  (REST, signature-verified webhooks) + local test adapter (local-only),
  server-side pricing, idempotent `payment_events` fulfilment, checkout flow.
- Phase 4: Classroom — groups, memberships, live sessions (encrypted meeting
  URLs, per-audience visibility), announcements.
- Phase 5: Assessment — assignments→submissions→grades with targeting
  (course/group/individual), quiz engine (attempt/time limits, auto + manual
  grading, release rules), student-privacy policies + IDOR tests.
- Phase 6: Private training — trainer availability, transaction-safe booking,
  double-booking prevention, append-only credit ledger, cancellation refunds.
- Phase 7: Joomla bridge plugin `com_eamnbridge` (SSO code exchange,
  HMAC-verified, packaged zip) + page→product mapping plan.
