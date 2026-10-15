CREATE TABLE IF NOT EXISTS `#__eamnbridge_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code_hash` char(64) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `state_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code_hash` (`code_hash`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
