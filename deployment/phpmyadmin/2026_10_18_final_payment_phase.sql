-- EAMN Academy final payment phase — reviewed MySQL 8 / MariaDB 10.6 fallback.
-- Prefer `php artisan migrate --force` through the gated deploy runner.
-- Use this only when production is still on the pre-2026-10-18 schema.
-- Take a phpMyAdmin export first. Do not rerun this file after a partial import.
-- MySQL/MariaDB DDL auto-commits; START TRANSACTION cannot make these ALTERs atomic.

START TRANSACTION;

ALTER TABLE `enrolments`
    ADD COLUMN `source` VARCHAR(30) NOT NULL DEFAULT 'purchased' AFTER `order_id`,
    ADD COLUMN `grant_reason` VARCHAR(500) NULL AFTER `source`,
    ADD INDEX `enrolments_source_index` (`source`);

ALTER TABLE `course_variants`
    ADD COLUMN `description` TEXT NULL AFTER `name`,
    ADD COLUMN `availability` VARCHAR(20) NOT NULL DEFAULT 'paid' AFTER `price_cents`,
    ADD COLUMN `expiry_policy` VARCHAR(20) NOT NULL DEFAULT 'none' AFTER `duration`,
    ADD COLUMN `access_duration_days` INT UNSIGNED NULL AFTER `expiry_policy`,
    ADD COLUMN `fixed_expiry_at` DATETIME NULL AFTER `access_duration_days`,
    ADD COLUMN `renewable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fixed_expiry_at`,
    ADD COLUMN `display_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `renewable`,
    ADD COLUMN `eligibility` JSON NULL AFTER `inclusions`;

ALTER TABLE `enrolments`
    ADD COLUMN `activated_at` TIMESTAMP NULL AFTER `enrolled_at`,
    ADD COLUMN `entitlement_started_at` TIMESTAMP NULL AFTER `activated_at`,
    ADD COLUMN `entitlement_ended_at` TIMESTAMP NULL AFTER `entitlement_started_at`,
    ADD INDEX `enrolments_entitlement_ended_at_index` (`entitlement_ended_at`);

ALTER TABLE `orders`
    ADD COLUMN `kind` VARCHAR(20) NOT NULL DEFAULT 'purchase' AFTER `status`,
    ADD COLUMN `renewal_of_order_id` BIGINT UNSIGNED NULL AFTER `kind`,
    ADD CONSTRAINT `orders_renewal_of_order_id_foreign`
        FOREIGN KEY (`renewal_of_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

ALTER TABLE `courses`
    ADD COLUMN `checkout_variant_id` BIGINT UNSIGNED NULL AFTER `access_days`,
    ADD CONSTRAINT `courses_checkout_variant_id_foreign`
        FOREIGN KEY (`checkout_variant_id`) REFERENCES `course_variants` (`id`) ON DELETE SET NULL;

CREATE TABLE `payment_gateway_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `gateway` VARCHAR(30) NOT NULL,
    `enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `environment` VARCHAR(20) NOT NULL DEFAULT 'sandbox',
    `sandbox_client_id` TEXT NULL,
    `sandbox_client_secret` TEXT NULL,
    `sandbox_webhook_id` TEXT NULL,
    `live_client_id` TEXT NULL,
    `live_client_secret` TEXT NULL,
    `live_webhook_id` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `payment_gateway_settings_gateway_unique` (`gateway`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @eamn_migration_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_10_18_091938_add_manual_membership_metadata_to_enrolments_table', @eamn_migration_batch),
    ('2026_10_18_121910_add_access_policy_and_renewal_fields', @eamn_migration_batch),
    ('2026_10_18_125251_add_checkout_variant_to_courses_table', @eamn_migration_batch),
    ('2026_10_18_130022_create_payment_gateway_settings_table', @eamn_migration_batch);

COMMIT;
