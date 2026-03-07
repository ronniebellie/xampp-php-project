-- Allow NULL stripe IDs for free signups (no Stripe).
-- Run once on calcforadvisors DB.

ALTER TABLE calcforadvisors_subscribers
  MODIFY stripe_customer_id VARCHAR(255) NULL DEFAULT NULL,
  MODIFY stripe_subscription_id VARCHAR(255) NULL DEFAULT NULL;

-- UNIQUE on stripe_subscription_id still applies; multiple NULLs are allowed in MySQL.
