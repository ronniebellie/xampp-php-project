-- Add optional banner/header image URL for trial white-label pages.
-- Run once: mysql -u root -p ronbelisle_premium < sql/alter_calcforadvisors_banner_url.sql

ALTER TABLE calcforadvisors_subscribers
  ADD COLUMN banner_url VARCHAR(512) NULL DEFAULT NULL AFTER logo_url;
