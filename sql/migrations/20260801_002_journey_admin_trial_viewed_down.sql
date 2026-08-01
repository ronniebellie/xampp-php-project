-- Rollback viewed_at on journey_admin_trial_notifications.

ALTER TABLE journey_admin_trial_notifications
  DROP COLUMN viewed_at;

DELETE FROM schema_migrations
WHERE migration_name = '20260801_002_journey_admin_trial_viewed';
