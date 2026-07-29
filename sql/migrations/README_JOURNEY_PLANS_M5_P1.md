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

**Status: applied** on production database `ronbelisle_premium` at **2026-07-29** (Milestone 5 / R1 P1).

Pre-migration backup (server):

`/var/backups/ronbelisle-mysql/ronbelisle_premium_pre_journey_m5_p1_20260729T154546Z.sql.gz`

Verified after apply:

- Tables `journey_plans` and `journey_plan_versions` exist
- `schema_migrations` contains `20260729_001_journey_plans_m5_p1`
- `php dev/journey-premium/test-milestone5-p1.php` → 19 passed, 0 failed
- Unauthenticated API smoke: load/save/import return HTTP 401 `not_authenticated`

Code commit: `faa38af` — *Add Journey cloud plan schema and save/load APIs (M5 P1)*
