# Journey Premium — Milestone 2 Technical Notes

**Status:** Webhook sync foundation deployed — **Stripe Dashboard endpoint registration still manual**  
**Date:** 2026-07-25  
**Depends on:** Milestone 1 tables (`user_product_subscriptions`, `stripe_webhook_events`)

---

## What Milestone 2 provides

- Hardened idempotent `journey_webhook_event_claim()` (safe under `MYSQLI_REPORT_STRICT`)
- Central Journey subscription upsert: `journey_sync_subscription_row()`
- Verified-event processor: `journey_process_verified_stripe_event()`
- HTTP endpoint: **`/stripe/webhook.php`** on ronbelisle.com
- Price-ID routing for Journey vs consumer vs CFA vs unknown
- Authoritative entitlement sync into `user_product_subscriptions` (`product_key = journey`)

## What Milestone 2 does **not** provide

- Journey Stripe Product/Price creation
- Real Journey Price IDs in config
- Checkout / 30-day trial invitation UX
- Public Journey page changes
- Account-backed Journey plan saving
- Changes to `users.subscription_status` or calcforadvisors webhook behavior
- Automatic Stripe Dashboard webhook registration

---

## Endpoint

| Item | Value |
|---|---|
| URL | `https://ronbelisle.com/stripe/webhook.php` |
| Method | `POST` only (`405` otherwise) |
| Body limit | 1 MiB |
| Auth | Stripe signature header (`Stripe-Signature`) |
| Session/CSRF | Not used (server-to-server) |

CFA remains on `https://calcforadvisors.com/stripe-webhook.php` until a future consolidation.

---

## Environment / config variables

| Variable | Purpose |
|---|---|
| `STRIPE_SECRET_KEY` / config `stripe.secret_key` | Retrieve Subscription/Checkout objects |
| `STRIPE_WEBHOOK_SECRET` / `RB_STRIPE_WEBHOOK_SECRET` | Default signing secret |
| `JOURNEY_STRIPE_WEBHOOK_SECRET` / `RB_JOURNEY_STRIPE_WEBHOOK_SECRET` | Optional dedicated secret for this endpoint (preferred when registered separately) |
| `JOURNEY_STRIPE_MONTHLY_PRICE_ID` / `RB_JOURNEY_STRIPE_MONTHLY_PRICE_ID` | Primary Journey Price routing |
| `JOURNEY_STRIPE_ANNUAL_PRICE_ID` / `RB_JOURNEY_STRIPE_ANNUAL_PRICE_ID` | Optional annual routing |
| `JOURNEY_STRIPE_PRODUCT_ID` | Optional product id storage |

No secrets or live Price IDs are committed in git.

Until Journey Price IDs are configured, the endpoint verifies signatures (when a signing secret exists) and **ignores non-Journey Prices** without writing Journey entitlement rows.

---

## Supported events

| Event | Behavior |
|---|---|
| `checkout.session.completed` | Subscription mode only; retrieve Subscription; sync |
| `customer.subscription.created` | Sync (prefer live retrieve) |
| `customer.subscription.updated` | Sync full current state |
| `customer.subscription.deleted` | Sync canceled/expired; keep history row |
| `invoice.paid` | Retrieve related Subscription; sync |
| `invoice.payment_failed` | Retrieve related Subscription; sync from object (not event-name assumptions) |
| `invoice.payment_action_required` | Same retrieve/sync path; access from subscription status |
| `customer.subscription.paused` / `resumed` | Sync from subscription object |

### Deferred / acknowledged without Journey grant logic

| Event | Why deferred |
|---|---|
| `checkout.session.async_payment_succeeded` / `failed` | Card Checkout is synchronous for planned Journey flow; acknowledged as ignored |

---

## Price-ID routing

Primary key = configured Price ID via `journey_classify_price_id()`:

- Journey monthly/annual fixture or config → `journey`
- Consumer calculator Prices → `consumer` (ignored by Journey writer)
- CFA Prices → `cfa` (ignored by Journey writer)
- Anything else → `unknown` (ignored; no Journey grant)

Metadata `product=journey` is **never** sufficient alone.

---

## Entitlement mapping

Uses Milestone 1 helpers:

- Grant: `trialing`, `active`, `canceled_grace` (cancel_at_period_end before `current_period_end`)
- Deny: `past_due`, `unpaid`, `incomplete`, `incomplete_expired`, `paused`, expired/canceled after period end

Free Journey phases remain ungated.

---

## Idempotency and retry

`stripe_webhook_events.processing_status`:

`received` → `processing` → `processed`  
or `received` → `processing` → `failed`

| Claim result | HTTP | Action |
|---|---|---|
| `claimed` / `reclaimed` | process then 200/500 | Run sync |
| `already_processed` / `in_progress` | 200 | No re-apply |
| claim `error` | 500 | Stripe retry |

Failed events may be **reclaimed** on a later delivery (`failed` → `processing`).  
`last_error` stores a short non-sensitive code.  
`processed_at` set only on success.

### Preferred sequence

1. Verify signature (raw body)  
2. Idempotent claim  
3. Retrieve Stripe objects (network **outside** DB transaction)  
4. Begin transaction  
5. Upsert entitlement  
6. Mark processed  
7. Commit  

---

## Why `success.php` is not authoritative

Checkout success may later confirm UX and redirect. Continuing Journey Premium access is determined only by webhook-synced `user_product_subscriptions` rows.

---

## Local testing

```bash
# Unit + DB fixtures (requires local MySQL + Milestone 1 migration)
/Applications/XAMPP/xamppfiles/bin/php dev/journey-premium/test-milestone1.php
/Applications/XAMPP/xamppfiles/bin/php dev/journey-premium/test-milestone2.php

# HTTP method check
curl -sS -o /dev/null -w '%{http_code}\n' -X GET https://ronbelisle.com/stripe/webhook.php
# Expect 405
```

### Stripe CLI (after a signing secret exists)

```bash
stripe listen --forward-to https://ronbelisle.com/stripe/webhook.php
stripe trigger customer.subscription.updated
```

Do not forward live Journey catalog events until Price IDs are configured and reviewed.

---

## Production webhook setup (manual — not automated)

1. Ensure Milestone 1 tables exist (done).  
2. Create Journey Product/Prices (later milestone / product approval).  
3. Set `JOURNEY_STRIPE_MONTHLY_PRICE_ID` (and optional annual) in server config.  
4. In Stripe Dashboard → Developers → Webhooks → Add endpoint:  
   `https://ronbelisle.com/stripe/webhook.php`  
5. Select the supported events listed above.  
6. Install signing secret as `JOURNEY_STRIPE_WEBHOOK_SECRET` (or shared `STRIPE_WEBHOOK_SECRET` if intentionally shared).  
7. Send a test event; confirm `stripe_webhook_events` rows and no CFA/legacy writes for Journey fixtures.

**Milestone 2 deploy does not register this endpoint automatically.**

---

## Reuse vs isolation

| Reuse | Isolate |
|---|---|
| Stripe PHP SDK, `stripe_config.php`, `db_config.php` | Journey entitlement table writes |
| Signature verification pattern from CFA webhook | CFA subscriber inserts/emails |
| Shared Stripe account | `users.subscription_status` consumer Premium field |

---

## Files

- `stripe/webhook.php`
- `includes/journey_stripe_sync.php`
- `includes/journey_entitlement.php` (claim hardening + test overrides)
- `includes/stripe_config.php` (optional Journey webhook secret)
- `dev/journey-premium/test-milestone2.php`
