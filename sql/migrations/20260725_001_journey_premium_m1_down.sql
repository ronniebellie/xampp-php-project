-- Journey Premium Milestone 1 — rollback
-- Removes only Milestone 1 tables/rows. Does not touch users or calcforadvisors_*.
--
-- Apply (local example):
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root ronbelisle_premium \
--     < sql/migrations/20260725_001_journey_premium_m1_down.sql

DROP TABLE IF EXISTS stripe_webhook_events;
DROP TABLE IF EXISTS user_product_subscriptions;

DELETE FROM schema_migrations
WHERE migration_name = '20260725_001_journey_premium_m1';

-- schema_migrations itself is left in place (shared bookkeeping).
-- To remove it when empty and unused:
--   DROP TABLE IF EXISTS schema_migrations;
