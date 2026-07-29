-- Journey Premium Milestone 5 / R1 — P1 forward migration
-- Account-backed Journey plan snapshots (cloud save foundation).
-- Safe to re-run: CREATE TABLE IF NOT EXISTS / INSERT IGNORE.
-- Does NOT modify users.*, user_product_subscriptions, or calcforadvisors_*.
--
-- Apply (local example):
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root ronbelisle_premium \
--     < sql/migrations/20260729_001_journey_plans_m5_p1_up.sql

CREATE TABLE IF NOT EXISTS journey_plans (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  schema_version INT UNSIGNED NOT NULL DEFAULT 1,
  payload LONGTEXT NOT NULL COMMENT 'JSON: progress + calculators',
  client_updated_at DATETIME(3) NULL COMMENT 'Client clock from last successful save',
  server_updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
    ON UPDATE CURRENT_TIMESTAMP(3),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_journey_plans_user (user_id),
  KEY idx_journey_plans_server_updated (server_updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journey_plan_versions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  journey_plan_id BIGINT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  schema_version INT UNSIGNED NOT NULL DEFAULT 1,
  payload LONGTEXT NOT NULL,
  reason VARCHAR(32) NOT NULL DEFAULT 'autosave' COMMENT 'autosave|import|manual|force',
  created_at TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  KEY idx_jpv_plan_created (journey_plan_id, created_at),
  KEY idx_jpv_user_created (user_id, created_at),
  CONSTRAINT fk_jpv_plan
    FOREIGN KEY (journey_plan_id) REFERENCES journey_plans (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (migration_name)
VALUES ('20260729_001_journey_plans_m5_p1');
