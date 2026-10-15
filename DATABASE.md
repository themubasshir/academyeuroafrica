# Database

MySQL/InnoDB in production; SQLite for dev/tests. Money as integer cents; times
UTC `dateTimeTz`. FK constraints + unique indexes enforce integrity.

## Identity
- `users` — name, email, password (nullable for SSO-only), `status`, `disabled_at`, `timezone`
- `external_identities` — `provider`+`subject` unique → `user_id` (Joomla map)
- `identity_conflicts` — email collisions pending admin resolution
- `bridge_auth_codes` — hashed single-use codes (academy-side mirror)
- `roles`, `permissions`, `role_user`, `permission_role`
- `audit_logs` — actor, action, subject morph, payload, ip

## Academic
- `programmes` 1→n `courses` 1→n `course_variants` (price_cents, delivery_type, capacity)
- `courses` 1→n `course_modules` 1→n `lessons`; `lessons` 1→1 `course_videos`
- `materials` — polymorphic `attachable` (course/module/lesson/assignment), private disk, randomised path, MIME+size
- `enrolments` — `user_id`+`course_variant_id` UNIQUE (individual, never batch-bound), `order_id`, expiry
- `trainer_assignments` — `trainer_id`+`course_id` UNIQUE (authorisation boundary)
- `lesson_progress` — `enrolment_id`+`lesson_id` UNIQUE

## Commerce
- `orders` 1→n `order_items`; `payment_transactions` (gateway+txn unique)
- `payment_events` — `gateway`+`gateway_event_id` UNIQUE (idempotency anchor)
- `refunds`

## Classroom
- `groups` 1→n `group_members` (optional layer)
- `live_sessions` — course OR group OR student target; encrypted `meeting_url`
- `announcements` — course- or group-scoped

## Assessment
- `assignments` + `assignment_recipients`; `assignment_submissions` (assignment+user UNIQUE) + attachments + `assignment_grades` (submission UNIQUE)
- `quizzes` + `quiz_questions` + `quiz_options`; `quiz_attempts` (quiz+user+attempt_no UNIQUE) + `quiz_answers` (attempt+question UNIQUE)

## Private training
- `trainer_availability`
- `appointments` — indexed `(trainer_id, starts_at)`; overlap prevented in service transaction
- `session_credit_ledger` — append-only; balance = SUM(delta)

## Joomla mapping
- `joomla_page_mappings` — `path` unique → `programme_id` or `course_variant_id`

## Key invariants
- No duplicated course content per student — per-student state lives only in enrolments/progress/submissions/attempts.
- `enrolments` UNIQUE(user, variant) prevents duplicate fulfilment.
- `payment_events` UNIQUE(gateway, event) makes webhooks idempotent.
- Credit balance derived from ledger, not a mutable counter.
