-- Add trial white-label columns for free subscribers (30-day trial).
-- Run once: mysql -u root -p ronbelisle_premium < sql/alter_calcforadvisors_trial_columns.sql

ALTER TABLE calcforadvisors_subscribers
  ADD COLUMN firm_name VARCHAR(255) NULL DEFAULT NULL AFTER plan,
  ADD COLUMN logo_url VARCHAR(512) NULL DEFAULT NULL AFTER firm_name,
  ADD COLUMN trial_slug VARCHAR(32) NULL DEFAULT NULL AFTER logo_url;

ALTER TABLE calcforadvisors_subscribers ADD UNIQUE KEY uk_trial_slug (trial_slug);
