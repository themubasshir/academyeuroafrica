-- Destructive schema rollback for the final payment phase.
-- Prefer restoring the pre-release phpMyAdmin export. This removes encrypted
-- gateway credentials and all access-policy values added by this release.
-- MySQL/MariaDB DDL auto-commits; START TRANSACTION cannot make these ALTERs atomic.

START TRANSACTION;

DROP TABLE IF EXISTS `payment_gateway_settings`;

ALTER TABLE `courses`
    DROP FOREIGN KEY `courses_checkout_variant_id_foreign`,
    DROP COLUMN `checkout_variant_id`;

ALTER TABLE `orders`
    DROP FOREIGN KEY `orders_renewal_of_order_id_foreign`,
    DROP COLUMN `renewal_of_order_id`,
    DROP COLUMN `kind`;

ALTER TABLE `enrolments`
    DROP INDEX `enrolments_entitlement_ended_at_index`,
    DROP COLUMN `entitlement_ended_at`,
    DROP COLUMN `entitlement_started_at`,
    DROP COLUMN `activated_at`;

ALTER TABLE `course_variants`
    DROP COLUMN `eligibility`,
    DROP COLUMN `display_order`,
    DROP COLUMN `renewable`,
    DROP COLUMN `fixed_expiry_at`,
    DROP COLUMN `access_duration_days`,
    DROP COLUMN `expiry_policy`,
    DROP COLUMN `availability`,
    DROP COLUMN `description`;

ALTER TABLE `enrolments`
    DROP INDEX `enrolments_source_index`,
    DROP COLUMN `grant_reason`,
    DROP COLUMN `source`;

DELETE FROM `migrations` WHERE `migration` IN (
    '2026_10_18_091938_add_manual_membership_metadata_to_enrolments_table',
    '2026_10_18_121910_add_access_policy_and_renewal_fields',
    '2026_10_18_125251_add_checkout_variant_to_courses_table',
    '2026_10_18_130022_create_payment_gateway_settings_table'
);

COMMIT;
