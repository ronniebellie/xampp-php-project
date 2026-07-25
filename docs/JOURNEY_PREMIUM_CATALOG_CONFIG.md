# Journey Premium — Stripe Catalog Configuration

**Status:** Production Product/Price IDs installed in server config (Checkout not enabled)  
**Date:** 2026-07-25  
**Depends on:** Milestone 1 helpers, Milestone 2 Price-ID routing

---

## Where values are configured

Production uses the established secure config file (not git):

| Location | Keys |
|---|---|
| `/etc/ronbelisle/config.php` → `stripe` array | `journey_product_id`, `journey_monthly_price_id`, `journey_annual_price_id` |

`includes/stripe_config.php` maps those keys (or `RB_JOURNEY_*` / `JOURNEY_*` env fallbacks) to:

- `JOURNEY_STRIPE_PRODUCT_ID`
- `JOURNEY_STRIPE_MONTHLY_PRICE_ID`
- `JOURNEY_STRIPE_ANNUAL_PRICE_ID`

No Stripe secrets or live Price IDs are committed in application source.

---

## Production identifiers (ops record)

| Item | Stripe ID | Config key |
|---|---|---|
| Product | `prod_Ux8xhhlzZ0RQyD` | `journey_product_id` |
| Monthly Price ($4/mo) | `price_1TxEfuHLmh7rIjELlLr13kvE` | `journey_monthly_price_id` |
| Annual Price ($40/yr) | `price_1TxEfuHLmh7rIjELdl6qZAf0` | `journey_annual_price_id` |

---

## Install / verify (CLI only)

```bash
# On production (root), from /var/www/html — after code deploy
php dev/journey-premium/install-catalog-config.php \
  --product=prod_Ux8xhhlzZ0RQyD \
  --monthly=price_1TxEfuHLmh7rIjELlLr13kvE \
  --annual=price_1TxEfuHLmh7rIjELdl6qZAf0

php dev/journey-premium/verify-catalog-config.php \
  --expect-product=prod_Ux8xhhlzZ0RQyD \
  --expect-monthly=price_1TxEfuHLmh7rIjELlLr13kvE \
  --expect-annual=price_1TxEfuHLmh7rIjELdl6qZAf0
```

Expected classification:

- monthly / annual Journey Prices → `product_key='journey'`
- consumer `STRIPE_PRICE_*` → `consumer`
- CFA `CALCFORADVISORS_PRICE_*` → `cfa`
- unknown → `unknown`

---

## What this step does **not** enable

- Checkout Sessions / trial invitation UX
- Stripe webhook Dashboard registration
- Public Journey page changes
- Auth, Premium gating, localStorage, or account-backed plan saving

---

## Placeholders remaining in the repo

Documentation and test fixtures may still mention example IDs such as `price_journey_monthly_test`, `price_xxx`, or older plan key names (`JOURNEY_STRIPE_PRICE_MONTHLY`). Those are intentional examples or fixture overrides, not production values.
