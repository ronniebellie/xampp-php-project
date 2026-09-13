-- Apply once BEFORE Phase 5 code, after backup and schema preflight. InnoDB required.
-- Additive only: preserve account IDs, email, Stripe identities, and scenarios.
ALTER TABLE calcforadvisors_subscribers
  ADD COLUMN cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN stripe_subscription_created BIGINT NULL,
  ADD COLUMN last_stripe_event_created BIGINT NULL;
CREATE TABLE cfa_webhook_events (
  event_id VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
  event_type VARCHAR(100) NOT NULL,
  processed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE cfa_checkout_sessions (
  request_id CHAR(48) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
  subscriber_id INT NOT NULL,
  stripe_customer_id VARCHAR(255) NOT NULL,
  stripe_session_id VARCHAR(255) NULL,
  plan VARCHAR(20) NOT NULL,
  trial_end BIGINT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_session (stripe_session_id),
  KEY idx_subscriber (subscriber_id)
) ENGINE=InnoDB;
