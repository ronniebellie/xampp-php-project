-- Add password support to calcforadvisors subscribers
-- Run once: mysql -u root -p ronbelisle_premium < sql/alter_calcforadvisors_add_password.sql

ALTER TABLE calcforadvisors_subscribers
ADD COLUMN password_hash VARCHAR(255) NULL DEFAULT NULL AFTER status;
