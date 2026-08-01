-- Journey feedback submissions (lightweight product feedback, not a ticket system).
-- Safe to re-run: CREATE TABLE IF NOT EXISTS / INSERT IGNORE.

CREATE TABLE IF NOT EXISTS journey_feedback (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  viewed_at DATETIME NULL DEFAULT NULL,
  user_id INT NULL,
  email VARCHAR(255) NULL,
  trying_to_do TEXT NOT NULL,
  what_happened TEXT NOT NULL,
  page_url VARCHAR(1024) NULL,
  journey_phase VARCHAR(64) NULL,
  is_signed_in TINYINT(1) NOT NULL DEFAULT 0,
  is_premium TINYINT(1) NOT NULL DEFAULT 0,
  user_agent VARCHAR(512) NULL,
  PRIMARY KEY (id),
  KEY idx_jf_viewed_at (viewed_at),
  KEY idx_jf_created_at (created_at),
  KEY idx_jf_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  migration_name VARCHAR(191) NOT NULL,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_schema_migrations_name (migration_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (migration_name)
VALUES ('20260801_003_journey_feedback');
