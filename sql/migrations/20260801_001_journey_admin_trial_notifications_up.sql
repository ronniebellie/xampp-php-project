-- Journey Premium — admin trial signup notification ledger
-- Idempotency key: stripe_subscription_id (one admin email per trial subscription).
-- Safe to re-run.

CREATE TABLE IF NOT EXISTS journey_admin_trial_notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  stripe_subscription_id VARCHAR(255) NOT NULL,
  stripe_event_id VARCHAR(255) NULL,
  user_id INT NULL,
  delivery_status VARCHAR(32) NOT NULL DEFAULT 'sending'
    COMMENT 'sending|sent|failed|skipped',
  delivery_error VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_jatn_stripe_subscription (stripe_subscription_id),
  KEY idx_jatn_delivery_status (delivery_status),
  KEY idx_jatn_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  migration_name VARCHAR(191) NOT NULL,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_schema_migrations_name (migration_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (migration_name)
VALUES ('20260801_001_journey_admin_trial_notifications');
