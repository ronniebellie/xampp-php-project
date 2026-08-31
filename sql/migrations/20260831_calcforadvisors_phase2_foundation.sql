-- CalcForAdvisors 2.0 Phase 2: additive subscriber foundation.
--
-- IMPORTANT:
--   1. Run sql/audit_calcforadvisors_phase2.sql first and review every result.
--   2. Take a database backup before applying this migration.
--   3. This migration adds nullable columns and indexes only. It does not alter
--      IDs, delete rows, modify Stripe subscriptions, or touch saved scenarios.
--   4. Apply once. Existing project migrations do not use IF NOT EXISTS because
--      production MySQL compatibility has not been established for that syntax.

ALTER TABLE calcforadvisors_subscribers
  ADD COLUMN portal_slug VARCHAR(48) NULL DEFAULT NULL AFTER trial_slug,
  ADD COLUMN advisor_name VARCHAR(255) NULL DEFAULT NULL AFTER firm_name,
  ADD COLUMN public_email VARCHAR(255) NULL DEFAULT NULL AFTER advisor_name,
  ADD COLUMN phone VARCHAR(64) NULL DEFAULT NULL AFTER public_email,
  ADD COLUMN website_url VARCHAR(512) NULL DEFAULT NULL AFTER phone,
  ADD COLUMN disclosure_text TEXT NULL AFTER website_url,
  ADD COLUMN stripe_subscription_status VARCHAR(32) NULL DEFAULT NULL AFTER status,
  ADD COLUMN trial_ends_at DATETIME NULL DEFAULT NULL AFTER stripe_subscription_status,
  ADD COLUMN access_ends_at DATETIME NULL DEFAULT NULL AFTER trial_ends_at,
  ADD COLUMN trial_used_at DATETIME NULL DEFAULT NULL AFTER access_ends_at,
  ADD COLUMN past_due_started_at DATETIME NULL DEFAULT NULL AFTER trial_used_at;

ALTER TABLE calcforadvisors_subscribers
  ADD UNIQUE KEY uk_portal_slug (portal_slug),
  ADD INDEX idx_cfa_stripe_status (stripe_subscription_status),
  ADD INDEX idx_cfa_access_ends (access_ends_at);

-- Backfill is intentionally separate. Do not infer current Stripe status or
-- paid-through dates from the legacy status alone. Those values must be
-- reconciled with Stripe in a later, reviewed migration/webhook phase.
