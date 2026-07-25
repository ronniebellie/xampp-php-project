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

**Do not apply in production until explicitly approved.**

Code deploy for Milestone 1 may ship without the production migration. Helpers degrade safely when tables are absent (Checkout/webhook handlers are not live yet).

When approved:

1. Backup production DB.
2. Apply `20260725_001_journey_premium_m1_up.sql` via the normal ops mysql path.
3. Run the verify SQL above.
4. Record approval / operator / timestamp in the deploy notes.
