-- Journey Premium Milestone 1 — forward migration
-- Safe to re-run: uses CREATE TABLE IF NOT EXISTS / CREATE INDEX patterns.
-- Does NOT modify users.subscription_status or calcforadvisors_* tables.
--
-- Database: same as includes/db_config.php (default local: ronbelisle_premium)
--
-- Apply (local example):
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root ronbelisle_premium \
--     < sql/migrations/20260725_001_journey_premium_m1_up.sql

CREATE TABLE IF NOT EXISTS user_product_subscriptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  product_key VARCHAR(64) NOT NULL COMMENT 'e.g. journey; future products share this table',
  stripe_customer_id VARCHAR(255) NULL,
  stripe_subscription_id VARCHAR(255) NOT NULL,
  stripe_price_id VARCHAR(255) NULL,
  stripe_product_id VARCHAR(255) NULL,
  stripe_status VARCHAR(64) NOT NULL DEFAULT '',
  entitlement_status VARCHAR(64) NOT NULL DEFAULT 'none',
  trial_start DATETIME NULL,
  trial_end DATETIME NULL,
  current_period_start DATETIME NULL,
  current_period_end DATETIME NULL,
  cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0,
  canceled_at DATETIME NULL,
  ended_at DATETIME NULL,
  latest_invoice_id VARCHAR(255) NULL,
  last_stripe_event_created BIGINT NULL COMMENT 'Stripe event.created unix for ordering hints',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_ups_stripe_subscription (stripe_subscription_id),
  KEY idx_ups_user_product (user_id, product_key),
  KEY idx_ups_product_entitlement (product_key, entitlement_status),
  KEY idx_ups_customer (stripe_customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stripe_webhook_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  stripe_event_id VARCHAR(255) NOT NULL,
  event_type VARCHAR(128) NOT NULL,
  stripe_created_at BIGINT NULL COMMENT 'Stripe event.created unix timestamp',
  livemode TINYINT(1) NULL,
  processing_status VARCHAR(32) NOT NULL DEFAULT 'received' COMMENT 'received|processing|processed|failed',
  attempts INT NOT NULL DEFAULT 0,
  last_error VARCHAR(512) NULL,
  processed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_swe_stripe_event_id (stripe_event_id),
  KEY idx_swe_type_status (event_type, processing_status),
  KEY idx_swe_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional bookkeeping row for operators (idempotent via unique name).
CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  migration_name VARCHAR(191) NOT NULL,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_schema_migrations_name (migration_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (migration_name)
VALUES ('20260725_001_journey_premium_m1');
