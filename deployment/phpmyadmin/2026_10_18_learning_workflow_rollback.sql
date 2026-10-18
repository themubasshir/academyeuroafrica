-- Destructive rollback for migration 2026_10_18_131446.
-- Prefer restoring the pre-release database export. Resubmission attempts after
-- attempt 1 are deleted so the prior two-column uniqueness rule can be restored.
-- MySQL/MariaDB DDL auto-commits.

ALTER TABLE `assignment_grades`
    DROP COLUMN `passed`;

DELETE FROM `assignment_submissions` WHERE `attempt_no` > 1;
ALTER TABLE `assignment_submissions`
    DROP INDEX `assignment_submissions_assignment_id_user_id_attempt_no_unique`,
    DROP COLUMN `attempt_no`,
    ADD UNIQUE KEY `assignment_submissions_assignment_id_user_id_unique` (`assignment_id`, `user_id`);

ALTER TABLE `assignments`
    DROP COLUMN `allow_late_submissions`,
    DROP COLUMN `max_submissions`,
    DROP COLUMN `allow_resubmission`,
    DROP COLUMN `passing_score`,
    DROP COLUMN `evaluation_type`,
    DROP COLUMN `is_required`;

ALTER TABLE `quizzes`
    DROP COLUMN `is_required`;

ALTER TABLE `lesson_progress`
    DROP COLUMN `acknowledged_at`,
    DROP COLUMN `progress_percent`;

ALTER TABLE `lessons`
    DROP FOREIGN KEY `lessons_primary_material_id_foreign`,
    DROP INDEX `lessons_primary_material_id_foreign`,
    DROP COLUMN `primary_material_id`,
    DROP COLUMN `completion_threshold`,
    DROP COLUMN `is_required`;

DELETE FROM `migrations`
WHERE `migration` = '2026_10_18_131446_add_learning_workflow_fields';
