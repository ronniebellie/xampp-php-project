-- Review ledger for calculator account rows in the unified Recent Signups page.
-- Journey Premium continues using journey_admin_trial_notifications.
CREATE TABLE IF NOT EXISTS admin_signup_reviews (
  source VARCHAR(32) NOT NULL,
  record_id BIGINT UNSIGNED NOT NULL,
  viewed_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (source, record_id),
  KEY idx_admin_signup_reviews_viewed (viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (migration_name)
VALUES ('20260904_001_admin_signup_reviews');
