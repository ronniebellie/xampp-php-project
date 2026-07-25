# Journey Premium — Milestone 3 Technical Notes

**Status:** Review-only Checkout implemented (not linked from public Journey)  
**Date:** 2026-07-25  
**Depends on:** Milestone 1 entitlement helpers, Milestone 2 webhook sync, production catalog config

---

## What Milestone 3 provides

- Review-only plan page: `/premium/journey.php` (`noindex,nofollow`)
- Authenticated Checkout Session creator: `POST /premium/journey-checkout.php`
- Non-authoritative success page: `/premium/journey-success.php`
- Card-required **30-day** trial on monthly and annual Journey Prices
- CSRF-protected plan form
- `intent=journey_trial` auth return flow (separate from calculator `intent=trial`)

## What Milestone 3 does **not** provide

- Public trial invitations (Phase 6 / Journey homepage)
- Phase gating or Premium-only Journey tools
- localStorage or account-backed plan saving changes
- Automatic Stripe Dashboard webhook registration
- calcforadvisors changes

---

## Routes

| Route | Method | Role |
|---|---|---|
| `/premium/journey.php` | GET | Plan selection (auth required) |
| `/premium/journey-checkout.php` | POST | Create Stripe Checkout Session |
| `/premium/journey-success.php` | GET | Confirmation + entitlement poll only |

---

## Authentication

Unauthenticated visitors to the plan page are redirected via:

`rb_auth_redirect_to_login('/premium/journey.php…', 'journey_trial')`

→ `/auth/register.php?intent=journey_trial` (or login companion links)

After auth, users return to `/premium/journey.php` (optional `?plan=monthly|annual`).

Uses existing ronbelisle.com consumer auth — no second identity system.

---

## Checkout parameters

Built by `journey_build_checkout_session_params()`:

| Parameter | Value |
|---|---|
| `mode` | `subscription` |
| `line_items[0].price` | Configured Journey monthly or annual Price ID only |
| `line_items[0].quantity` | `1` |
| `subscription_data.trial_period_days` | `30` |
| `payment_method_types` | `['card']` |
| `payment_method_collection` | `always` |
| `client_reference_id` | authenticated `user_id` |
| `customer` or `customer_email` | Existing Journey `stripe_customer_id` when available; else email |
| `allow_promotion_codes` | omitted (not used on consumer Checkout) |

Browser-supplied Price IDs are ignored. Only internal `plan=monthly|annual` is accepted.

### Metadata (Session + Subscription)

- `product=journey`
- `product_key=journey`
- `user_id=<id>`
- `plan=monthly|annual`
- `source=journey`

Price-ID routing remains required for entitlement (Milestone 2).

---

## Billing language

- 30-day free trial  
- A payment method is required  
- You will not be charged today  
- Monthly: After the trial, $4 per month until canceled  
- Annual: After the trial, $40 per year until canceled (saves $8 vs 12× monthly)  
- Cancel before the trial ends to avoid being charged  

---

## Success / cancel

**Success:** Confirms Checkout Session is a Journey subscription for the logged-in user. Shows “finishing setup” until `has_journey_premium_access()` becomes true via webhook rows. **Never** writes `users.subscription_status` or inserts entitlement.

**Cancel:** `/premium/journey.php?canceled=1&plan=…` — no subscription started.

---

## Webhook dependency

Authoritative entitlement remains `POST /stripe/webhook.php` → `user_product_subscriptions`.

Until the Journey endpoint is registered in Stripe Dashboard with a signing secret, success may remain in “finishing setup” after a real Checkout.

---

## Testing

```bash
php dev/journey-premium/test-milestone1.php
php dev/journey-premium/test-milestone2.php
php dev/journey-premium/test-milestone3.php

# Optional HTTP checks (no live Checkout create):
JOURNEY_M3_HTTP_BASE=https://ronbelisle.com php dev/journey-premium/test-milestone3.php
```

---

## Manual Stripe Dashboard steps still required

1. Register webhook endpoint `https://ronbelisle.com/stripe/webhook.php` for Journey-relevant events.  
2. Install signing secret as `JOURNEY_STRIPE_WEBHOOK_SECRET` (preferred) in `/etc/ronbelisle/config.php`.  
3. Controlled end-to-end subscription test (card test/live as approved).  
4. Only then expose public trial invitations (later milestone).

---

## Files

- `premium/journey.php`
- `premium/journey-checkout.php`
- `premium/journey-success.php`
- `includes/journey_checkout.php`
- `includes/csrf.php`
- `includes/auth_flow_helpers.php` (`journey_trial` intent)
- `dev/journey-premium/test-milestone3.php`
