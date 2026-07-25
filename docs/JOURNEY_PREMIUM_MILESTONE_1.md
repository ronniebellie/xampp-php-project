# Journey Premium — Milestone 1 Technical Notes

**Status:** Foundation only — no public Checkout, trial invitation, or cloud save  
**Related:** `docs/JOURNEY_PREMIUM_IMPLEMENTATION_PLAN.md`, `docs/JOURNEY_PREMIUM_ARCHITECTURE_AUDIT.md`  
**Date:** 2026-07-25  

---

## Product key

```text
product_key = 'journey'
```

Constant: `JOURNEY_PRODUCT_KEY` in `includes/journey_entitlement.php`.

Journey entitlement is stored in `user_product_subscriptions` and must **not** overwrite:

- `users.subscription_status`
- `calcforadvisors_subscribers` / related CFA tables

---

## Configuration variables

Values load from `/etc/ronbelisle/config.php` (`stripe` array) or environment variables.  
**No production IDs or secrets belong in git.**

| Constant | Config array key | Env (preferred) | Env (alias) | Required for future Checkout |
|---|---|---|---|---|
| `JOURNEY_STRIPE_PRODUCT_ID` | `journey_product_id` | `RB_JOURNEY_STRIPE_PRODUCT_ID` | `JOURNEY_STRIPE_PRODUCT_ID` | Optional |
| `JOURNEY_STRIPE_MONTHLY_PRICE_ID` | `journey_monthly_price_id` | `RB_JOURNEY_STRIPE_MONTHLY_PRICE_ID` | `JOURNEY_STRIPE_MONTHLY_PRICE_ID` | **Yes** |
| `JOURNEY_STRIPE_ANNUAL_PRICE_ID` | `journey_annual_price_id` | `RB_JOURNEY_STRIPE_ANNUAL_PRICE_ID` | `JOURNEY_STRIPE_ANNUAL_PRICE_ID` | No (annual deferred) |

Loaded in `includes/stripe_config.php`.

`journey_stripe_checkout_config_ready()` returns true only when a non-placeholder monthly Price ID starting with `price_` is configured.

**Price ID is the primary routing identifier.** Metadata may be supplementary later; display names must never authorize Journey.

Dollar amounts are **not** approved yet and are not stored in code.

---

## Schema: `user_product_subscriptions`

Supports multiple products per `user_id`.

| Column | Notes |
|---|---|
| `id` | PK |
| `user_id` | Logical owner (`users.id`); indexed with `product_key` (no hard FK — matches existing `scenarios` convention) |
| `product_key` | `journey` today; other products later |
| `stripe_customer_id` | `cus_…` |
| `stripe_subscription_id` | Unique `sub_…` |
| `stripe_price_id` / `stripe_product_id` | Current catalog ids |
| `stripe_status` | Raw Stripe status |
| `entitlement_status` | Normalized app status |
| `trial_start` / `trial_end` | Nullable |
| `current_period_start` / `current_period_end` | Nullable |
| `cancel_at_period_end` | Bool |
| `canceled_at` / `ended_at` | Nullable |
| `latest_invoice_id` | Nullable |
| `last_stripe_event_created` | Ordering hint |
| `created_at` / `updated_at` | Audit |

Unique: `stripe_subscription_id`.

---

## Schema: `stripe_webhook_events`

Idempotency ledger for Milestone 2 webhook processing.

| Column | Notes |
|---|---|
| `stripe_event_id` | Unique `evt_…` |
| `event_type` | e.g. `customer.subscription.updated` |
| `stripe_created_at` | Stripe `created` unix |
| `livemode` | Nullable bool |
| `processing_status` | `received` / `processing` / `processed` / `failed` |
| `attempts` | Counter |
| `last_error` | Short diagnostic text only — **no** payment-method payloads |
| `processed_at` | When successfully applied |

Helpers: `journey_webhook_event_claim()`, `journey_webhook_event_mark()` in `includes/journey_entitlement.php`.

---

## Entitlement-state mapping

Implemented by `journey_normalize_entitlement_status()` / `journey_evaluate_subscription_entitlement()`.

| Condition | `entitlement_status` | Premium access (`accessAllowed`) |
|---|---|---|
| `trialing` (not past period end) | `trialing` or `canceled_grace` if cancel_at_period_end | **Yes** |
| `active` | `active` | **Yes** |
| `active`/`trialing` + cancel_at_period_end before period end | `canceled_grace` | **Yes** |
| After period end while canceled | `expired` / `canceled` | **No** |
| `past_due` | `past_due` | **No** (data must be preserved; no delete) |
| `unpaid` | `unpaid` | **No** |
| `incomplete` | `incomplete` | **No** |
| `incomplete_expired` | `incomplete_expired` | **No** |
| `paused` | `paused` | **No** |

Free six-phase Journey access is **not** gated by these helpers.

---

## Why `success.php` is not authoritative

Checkout success may confirm UX and redirect in a later milestone, but continuing Premium entitlement must come from **webhook-synced** `user_product_subscriptions` rows. Milestone 1 prepares the tables and mapping; Milestone 2 implements the event router.

---

## Migrations

See `sql/migrations/README_JOURNEY_PREMIUM_M1.md`.

- Up: `20260725_001_journey_premium_m1_up.sql`
- Down: `20260725_001_journey_premium_m1_down.sql`

**Production migration requires separate explicit approval** and is not implied by code deploy.

---

## How later milestones use this foundation

| Milestone | Uses |
|---|---|
| 2 | Claim webhook events; upsert Journey rows by Price ID; never write CFA/legacy consumer fields for Journey events |
| 3 | `journey_stripe_checkout_config_ready()` before Checkout Session create; metadata + Price ID |
| 4+ | UI reads entitlement via helpers/DB; phases remain free |

---

## Intentionally unimplemented in Milestone 1

- Stripe Checkout / Customer Portal / trial invitation UX
- Public Journey page changes
- Account-backed Journey plan saving
- Full webhook HTTP endpoint / product router
- Creating Stripe Products or Prices via code
- Annual billing launch
- Parent-domain SSO
- Any claim of cross-device sync

---

## Tests

```bash
/Applications/XAMPP/xamppfiles/bin/php dev/journey-premium/test-milestone1.php
```

Covers Price-ID recognition, entitlement mapping, cancel-at-period-end, past_due, inactive states, duplicate event ids (when local DB available), and multi-product / multi-user inserts.
