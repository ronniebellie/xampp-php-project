-- Journey Premium Milestone 5 / R1 — P1 rollback
-- Removes only P1 journey plan tables/rows. Does not touch entitlement tables.
--
-- Apply (local example):
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root ronbelisle_premium \
--     < sql/migrations/20260729_001_journey_plans_m5_p1_down.sql

DROP TABLE IF EXISTS journey_plan_versions;
DROP TABLE IF EXISTS journey_plans;

DELETE FROM schema_migrations
WHERE migration_name = '20260729_001_journey_plans_m5_p1';
