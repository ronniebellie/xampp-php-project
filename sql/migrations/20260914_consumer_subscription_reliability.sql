-- Additive consumer lifecycle storage. No existing account/product records change.
-- Apply once after isolated-schema verification and backup, before code activation.
CREATE TABLE consumer_subscriptions (
 id INT NOT NULL PRIMARY KEY,
 stripe_customer_id VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NULL UNIQUE,
 stripe_subscription_id VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NULL UNIQUE,
 stripe_subscription_created BIGINT NULL,
 stripe_subscription_status VARCHAR(30) NOT NULL DEFAULT '',
 status VARCHAR(30) NOT NULL DEFAULT 'inactive',
 plan VARCHAR(20) NOT NULL DEFAULT 'free',
 cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0,
 trial_ends_at DATETIME NULL,
 access_ends_at DATETIME NULL,
 past_due_started_at DATETIME NULL,
 trial_used_at DATETIME NULL,
 last_stripe_event_created BIGINT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_consumer_subscription_user FOREIGN KEY (id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE consumer_webhook_events (
 event_id VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 event_type VARCHAR(100) NOT NULL,
 processed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE consumer_checkout_sessions (
 request_id CHAR(48) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 subscriber_id INT NOT NULL,
 stripe_customer_id VARCHAR(255) NOT NULL,
 stripe_session_id VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NULL UNIQUE,
 plan VARCHAR(20) NOT NULL,
 trial_end BIGINT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_consumer_user (subscriber_id),
 CONSTRAINT fk_consumer_checkout_user FOREIGN KEY (subscriber_id) REFERENCES users(id)
) ENGINE=InnoDB;
