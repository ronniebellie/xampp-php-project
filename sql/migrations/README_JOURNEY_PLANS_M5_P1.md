# Journey Premium Milestone 5 / R1 — P1: journey_plans schema

## What this adds

- `journey_plans` — one current JSON plan snapshot per `users.id`
- `journey_plan_versions` — append-only history (pruned by application code)

## What this does **not** change

- `user_product_subscriptions` / Stripe entitlement
- `users.subscription_status`
- Journey public pages or localStorage behavior (client sync is later phases)

## Local apply

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root ronbelisle_premium \
  < sql/migrations/20260729_001_journey_plans_m5_p1_up.sql
```

## Local rollback

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root ronbelisle_premium \
  < sql/migrations/20260729_001_journey_plans_m5_p1_down.sql
```

## APIs (ronbelisle.com)

| Method | Path | Write access required |
|--------|------|------------------------|
| GET | `/api/journey_plan_load.php` | No — Premium write **or** existing cloud plan (read-only) |
| PUT | `/api/journey_plan_save.php` | Yes — Journey Premium write access |
| POST | `/api/journey_plan_import.php` | Yes — first cloud plan only |

Mutating methods require session CSRF token (`csrf_token` in JSON body or `X-CSRF-Token` header).

## Production

Backup before apply, then run the up migration against `ronbelisle_premium`.
Record backup path and migration time in the P1 deployment report.
