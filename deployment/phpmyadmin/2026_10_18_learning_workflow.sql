-- EAMN Academy learning workflow — reviewed MySQL 8 / MariaDB 10.6 fallback.
-- Prefer `php artisan migrate --force` through the gated deploy runner.
-- Import only when migration 2026_10_18_131446 is not already recorded.
-- Export the complete production database first. DDL auto-commits.

ALTER TABLE `lessons`
    ADD COLUMN `is_required` TINYINT(1) NOT NULL DEFAULT 1 AFTER `requires_previous`,
    ADD COLUMN `completion_threshold` TINYINT UNSIGNED NOT NULL DEFAULT 90 AFTER `is_required`,
    ADD COLUMN `primary_material_id` BIGINT UNSIGNED NULL AFTER `completion_threshold`,
    ADD INDEX `lessons_primary_material_id_foreign` (`primary_material_id`),
    ADD CONSTRAINT `lessons_primary_material_id_foreign`
        FOREIGN KEY (`primary_material_id`) REFERENCES `materials` (`id`) ON DELETE SET NULL;

ALTER TABLE `lesson_progress`
    ADD COLUMN `progress_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `position_seconds`,
    ADD COLUMN `acknowledged_at` TIMESTAMP NULL AFTER `progress_percent`;

ALTER TABLE `quizzes`
    ADD COLUMN `is_required` TINYINT(1) NOT NULL DEFAULT 1 AFTER `passing_score`;

ALTER TABLE `assignments`
    ADD COLUMN `is_required` TINYINT(1) NOT NULL DEFAULT 1 AFTER `max_points`,
    ADD COLUMN `evaluation_type` VARCHAR(20) NOT NULL DEFAULT 'numeric' AFTER `is_required`,
    ADD COLUMN `passing_score` INT UNSIGNED NULL AFTER `evaluation_type`,
    ADD COLUMN `allow_resubmission` TINYINT(1) NOT NULL DEFAULT 1 AFTER `passing_score`,
    ADD COLUMN `max_submissions` INT UNSIGNED NOT NULL DEFAULT 2 AFTER `allow_resubmission`,
    ADD COLUMN `allow_late_submissions` TINYINT(1) NOT NULL DEFAULT 0 AFTER `max_submissions`;

ALTER TABLE `assignment_submissions`
    DROP INDEX `assignment_submissions_assignment_id_user_id_unique`,
    ADD COLUMN `attempt_no` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `user_id`,
    ADD UNIQUE KEY `assignment_submissions_assignment_id_user_id_attempt_no_unique`
        (`assignment_id`, `user_id`, `attempt_no`);

ALTER TABLE `assignment_grades`
    ADD COLUMN `passed` TINYINT(1) NULL AFTER `score`;

SET @eamn_learning_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_10_18_131446_add_learning_workflow_fields', @eamn_learning_batch);
