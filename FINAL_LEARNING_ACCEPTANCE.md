# EAMN Academy — Learning Workflow Acceptance

Prepared 2026-10-18. No production deployment or course publication was performed.

## Completed

- Assigned trainers and Academy administrators can add, edit, reorder, preview, publish, draft, and remove lessons within course modules.
- Lesson content supports sanitised rich text, private primary PDFs, approved YouTube/Vimeo embeds, and private MP4/WebM uploads.
- Uploaded PDFs and videos remain on Laravel's private `local` disk. Every view, download, and stream request checks the viewer's active course enrolment or staff assignment.
- Private video responses support HTTP byte ranges for seeking. Playback position only moves forward, resumes on return, and completes at the configured threshold. The interface states that playback records do not prove attention.
- PDF and text completion requires an explicit student action. Merely opening a PDF never completes it.
- Course progress identifies required and optional lessons.
- Existing quizzes retain server-side attempt limits, objective grading, manual grading, feedback, and history. Answer option IDs are validated against the authoritative question.
- Quizzes and assignments can be required or optional for certification.
- Assignments support deadlines, optional late work, numeric or pass/fail evaluation, trainer feedback, returned work, configured resubmission limits, and preserved attempt history.
- Existing certification policies evaluate only required published activities. Pending or ungraded required work blocks eligibility.
- Lesson completion, quiz grading, assignment grading, attendance, trainer approval, and administrator approval synchronously recalculate eligibility. Shared hosting does not require a permanent queue worker.
- Automatic issuance uses the existing sequential number, branded PDF, QR verification URL, active-certificate uniqueness constraint, dashboard, audit, reissue, and revocation systems.
- Automatic triggers cannot replace a revoked certificate. Course access expiry and certificate validity remain separate policies.

## Protected storage requirements

Keep `storage/app/private` outside the public document root. Its `lesson-videos`, `materials`, `submissions`, and `certificates` subdirectories are created as needed and must be writable by PHP. Never create a public symlink to this directory.

Recommended host limits for large private video uploads:

```ini
upload_max_filesize = 512M
post_max_size = 520M
max_execution_time = 300
```

The application limit is 512 MB for MP4/WebM and 25 MB for PDFs and ordinary course/submission files. If cPanel cannot safely support large uploads, use approved YouTube/Vimeo hosting instead. HTTPS is required for protected playback and certificate verification.

## Deployment

1. Export the complete production database and retain the prior app/public archives.
2. Upload the new private app archive outside the document root and the public archive into the Academy document root.
3. Preserve `.env`, `APP_KEY`, private storage content, and writable permissions.
4. Run `php artisan migrate --force` through the gated migration runner. If unavailable, import `deployment/phpmyadmin/2026_10_18_learning_workflow.sql` once after confirming the migration is absent.
5. Clear configuration, route, view, and application caches.
6. Leave all existing course and lesson publication states unchanged. Build one draft acceptance course and test it with non-production users.
7. Verify a private PDF, ranged video playback, lesson completion, failed and passed quiz attempts, assignment return/resubmission/grading, attendance or approval where configured, PDF certificate download, and public QR verification.
8. Reconfirm the existing direct checkout price, expiry, PayPal Sandbox capture, enrolment, and renewal behavior before accepting the release.

## Rollback

Preferred rollback: maintenance mode, restore the pre-release database export, restore both prior code archives, preserve the prior `.env`, and clear caches.

The fallback `deployment/phpmyadmin/2026_10_18_learning_workflow_rollback.sql` removes the new workflow fields and deletes assignment attempts after attempt 1. Use it only when that data loss is accepted. A complete database restore is safer after real learner activity.

## Acceptance checklist

- [x] Draft/published lesson builder and module assignment
- [x] Required/optional lessons and assessments
- [x] Private primary PDF delivery and explicit acknowledgement
- [x] Private uploaded video validation, ranged streaming, resume, and completion threshold
- [x] Approved hosted-video validation
- [x] Secure quiz answers, attempts, grading, feedback, and pass requirement
- [x] Assignment deadlines, files, feedback, pass criteria, resubmission rules, and history
- [x] Pending assessment blocks certification
- [x] Synchronous automatic certificate trigger coverage
- [x] Branded PDF, sequential number, QR verification, and student availability
- [x] Repeated-trigger idempotency and active-certificate uniqueness
- [x] Expired enrolment and revoked certificate safeguards
- [x] Reviewed Laravel migration and phpMyAdmin SQL/rollback
- [ ] Production backup and archive upload
- [ ] Production PHP upload/timeout limits confirmed
- [ ] Host-level acceptance course completed with real PDF/video files
- [ ] PayPal Sandbox and existing-course regression accepted on host
- [ ] EAMN Academy v1.0 production release approved

## Status

The application implementation is ready for host-level acceptance. Production remains unchanged. Remaining blockers are the production backup, cPanel storage and PHP-limit verification, real-file acceptance testing, PayPal Sandbox regression on the host, and explicit v1.0 release approval.
