# Journey Premium Milestone 1 — Database migration

## What this adds

- `user_product_subscriptions` — product-scoped entitlements (`product_key = 'journey'`)
- `stripe_webhook_events` — idempotent webhook event ledger
- `schema_migrations` — lightweight applied-migration bookkeeping

## What this does **not** change

- `users.subscription_status` / `users.stripe_subscription_id`
- Any `calcforadvisors_*` table
- Journey public pages or localStorage

## Local apply

```bash
# From repo root; adjust DB credentials to match includes/db_config.php
/Applications/XAMPP/xamppfiles/bin/mysql -u root ronbelisle_premium \
  < sql/migrations/20260725_001_journey_premium_m1_up.sql
```

Re-running the up migration is safe (`CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE`).

## Local rollback

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root ronbelisle_premium \
  < sql/migrations/20260725_001_journey_premium_m1_down.sql
```

## Verify

```sql
SHOW CREATE TABLE user_product_subscriptions\G
SHOW CREATE TABLE stripe_webhook_events\G
SHOW INDEX FROM user_product_subscriptions;
SHOW INDEX FROM stripe_webhook_events;
SELECT * FROM schema_migrations WHERE migration_name = '20260725_001_journey_premium_m1';
```

Expect:

- Unique index on `user_product_subscriptions.stripe_subscription_id`
- Unique index on `stripe_webhook_events.stripe_event_id`
- One schema_migrations row for this migration name

## Production

**Status: applied** on production database `ronbelisle_premium` at **2026-07-25T22:39:23Z**.

Pre-migration backup (server):

`/var/backups/ronbelisle-mysql/ronbelisle_premium_pre_journey_m1_20260725T223910Z.sql.gz`

Verified after apply:

- Tables `user_product_subscriptions`, `stripe_webhook_events`, and `schema_migrations` exist
- `schema_migrations` contains `20260725_001_journey_premium_m1`
- Legacy `users.subscription_status` distribution unchanged
- `calcforadvisors_subscribers` row count unchanged
- No persistent fabricated Journey subscription or webhook rows left after verification

Code deploy for Milestone 1 may ship without the production migration on other environments. Helpers degrade safely when tables are absent (Checkout/webhook handlers are not live yet).
