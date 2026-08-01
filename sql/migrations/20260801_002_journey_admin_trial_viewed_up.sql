-- Journey Premium admin trials — viewed/unviewed tracking.
-- Keeps delivery_* columns for backward compatibility; they are no longer used for email.
-- Safe to re-run.

ALTER TABLE journey_admin_trial_notifications
  ADD COLUMN viewed_at DATETIME NULL DEFAULT NULL
    COMMENT 'When an admin reviewed this trial in the admin dashboard'
    AFTER delivery_error;

CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  migration_name VARCHAR(191) NOT NULL,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_schema_migrations_name (migration_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (migration_name)
VALUES ('20260801_002_journey_admin_trial_viewed');
