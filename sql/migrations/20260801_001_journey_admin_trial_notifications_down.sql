-- Rollback Journey admin trial notification ledger.

DROP TABLE IF EXISTS journey_admin_trial_notifications;

DELETE FROM schema_migrations
WHERE migration_name = '20260801_001_journey_admin_trial_notifications';
