# Journey Premium — Implementation Plan

**Status:** Approved; M1–M3 complete for review-only Checkout; public invitations and Dashboard webhook registration still deferred. Catalog: `docs/JOURNEY_PREMIUM_CATALOG_CONFIG.md`.
**Primary sources:** `docs/JOURNEY_PREMIUM_ARCHITECTURE_AUDIT.md`, `journey.ronbelisle.com/docs/FREE_TO_PREMIUM_TRANSITION.md`
**Date:** 2026-07-25
**Product:** Retirement Planning Journey Premium (`journey.ronbelisle.com`) extending ronbelisle.com consumer auth + shared Stripe account
**Milestone docs:** `docs/JOURNEY_PREMIUM_MILESTONE_1.md`, `docs/JOURNEY_PREMIUM_MILESTONE_2.md`, `docs/JOURNEY_PREMIUM_MILESTONE_3.md`

> Later milestones still require explicit approval before coding.
> Do **not** create Stripe Products/Prices or register live webhooks without ops/product approval.

---

## 1. Executive recommendation

Extend the existing **ronbelisle.com consumer authentication** and **shared Stripe account**. Do **not** build a parallel auth or billing platform. Do **not** use the calcforadvisors white-label subscriber model for Journey.

Journey Premium is a **continuity product**, not a phase gate:

- All **six Journey phases remain free** and useful.
- Premium unlocks an **ongoing planning workspace**: account-backed save/restore, history, alternatives/versions, calm revisit flows.
- Journey trial is **30 days**, via Journey-specific Stripe Price IDs.
- **Stripe webhooks are the authoritative entitlement source.** Checkout success may confirm and redirect; it must not be the sole long-term grant path.
- Repair the known consumer-Premium entitlement weakness (success-page activation without cancel/past_due sync) **as part of the shared webhook foundation**, without overwriting CFA subscriber rows.

**First coding milestone (after approval):** Milestone 1 — Journey Stripe catalog specification (config keys + Dashboard checklist) and database entitlement foundation (product-scoped subscriptions), with no public UX change yet.

---

## 2. Existing components to reuse

| Reuse unchanged (or nearly) | Role |
|---|---|
| `auth/login.php`, `auth/register.php`, password reset | Consumer identity |
| `includes/session_bootstrap.php` | Host-scoped PHP sessions + remember-me |
| `includes/auth_flow_helpers.php` | Safe Journey `return=` + `intent=trial` |
| Stripe PHP SDK (`vendor/`) | Checkout, Portal, webhook verify |
| `includes/stripe_config.php` / external config | Secrets outside git |
| `billing_portal.php` pattern | Customer Portal entry |
| MySQL `users` table | Account identity (`user_id`) |
| Shared Stripe account | One merchant, multiple Products/Prices |

| Adapt carefully | Role |
|---|---|
| Checkout success flow | Confirm + redirect only; entitlement via webhook |
| `has_premium_access.php` pattern | Add Journey-scoped helper; do not overload calculator Premium |
| CFA webhook style | Generalize into Price-ID routed event processing |
| Journey localStorage model | Remains free path; later import into account |

| Keep separate | Role |
|---|---|
| `calcforadvisors_subscribers` / CFA scenarios | Advisor white-label only |
| Consumer calculator `scenarios` table | Calculator Premium saves (not Journey plans) |
| Consumer 7-day calculator Price IDs | Do not reuse for Journey trial |

---

## 3. Product and Price architecture

### 3.1 Recommended Stripe structure

| Object | Recommendation |
|---|---|
| **Product** | One Stripe Product, e.g. name **“Retirement Planning Journey Premium”** (Dashboard). Config key: `JOURNEY_STRIPE_PRODUCT_ID` (optional; Price IDs are sufficient for routing). |
| **Monthly Price** | Required for launch. Config: `JOURNEY_STRIPE_PRICE_MONTHLY`. |
| **Annual Price** | Recommended for launch parity with other products, but **pricing amounts are an unresolved product decision**. Config: `JOURNEY_STRIPE_PRICE_ANNUAL`. If annual is deferred, ship monthly-only and hide annual UI. |
| **Trial** | `subscription_data.trial_period_days = 30` on Checkout Session (and/or trial configured on the Price — pick one place to avoid stacking). |
| **Checkout mode** | `mode: 'subscription'` via Stripe Checkout (hosted). No embedded card fields on Journey pages. |
| **Payment method at trial start** | **Recommended: yes, collect card** (matches proven CFA 30-day and consumer 7-day Checkout). No-card trial is a separate product decision and would change churn/fraud controls. |

**Do not invent dollar amounts in this plan.** Consumer UI currently advertises calculator Premium at $3/mo or $30/yr; older docs mention other figures. Journey pricing requires explicit product approval.

### 3.2 Metadata (required)

Attach consistently:

| Object | Metadata keys |
|---|---|
| Checkout Session | `product=journey`, `user_id=<users.id>`, `plan=monthly\|annual`, `source=journey` |
| Subscription | same + `journey_checkout_session_id` when available |
| Customer | `user_id=<users.id>` when known; never store secrets |

Also set Checkout `client_reference_id` to `users.id` (string).

### 3.3 Distinguishing Journey from other products

Route exclusively by **Price ID allowlists** in config:

- Journey: `JOURNEY_STRIPE_PRICE_MONTHLY`, `JOURNEY_STRIPE_PRICE_ANNUAL`
- Consumer calculators: `STRIPE_PRICE_MONTHLY`, `STRIPE_PRICE_ANNUAL`
- CFA: `CALCFORADVISORS_PRICE_MONTHLY`, `CALCFORADVISORS_PRICE_ANNUAL`

Never infer product from email domain or success URL alone.

### 3.4 Product decisions still required

- Exact monthly/annual dollar amounts
- Whether annual ships at launch
- Confirm card-required trial (recommended) vs no-card trial

---

## 4. Entitlement data model

### 4.1 Design principle

One `users` row may hold **multiple product subscriptions**. Do **not** store Journey status in `users.subscription_status` in a way that overwrites calculator Premium.

### 4.2 Recommended tables

#### A. `stripe_webhook_events` (idempotency)

| Column | Purpose |
|---|---|
| `event_id` PK | Stripe event id (`evt_…`) |
| `type` | Event type |
| `product_key` | `journey` / `consumer` / `cfa` / `unknown` |
| `processed_at` | When applied |
| `payload_hash` | Optional integrity aid |
| `result` | `applied` / `ignored` / `error` |

#### B. `user_product_subscriptions` (authoritative entitlement rows)

| Column | Purpose |
|---|---|
| `id` | Surrogate PK |
| `user_id` | FK → `users.id` |
| `product_key` | e.g. `journey`, later `consumer_calculators` |
| `stripe_customer_id` | `cus_…` |
| `stripe_subscription_id` | Unique `sub_…` |
| `stripe_price_id` | Current Price |
| `status` | Normalized app status (below) |
| `stripe_status` | Raw Stripe status |
| `trial_ends_at` | Nullable |
| `current_period_end` | Nullable |
| `cancel_at_period_end` | Bool |
| `canceled_at` | Nullable |
| `created_at` / `updated_at` | Audit |

Unique: `(stripe_subscription_id)`; unique active-ish pair optional `(user_id, product_key)` enforced in application with clear upgrade/replace rules.

#### C. Later (Milestone 5+): `journey_plans` / `journey_plan_versions`

Account-backed Journey JSON snapshots owned by `user_id`. Out of scope for Milestone 1–3 beyond schema sketch.

### 4.3 Normalized Journey access statuses

| App status | Typical Stripe `subscription.status` | Journey Premium access? |
|---|---|---|
| `trialing` | `trialing` | **Yes** |
| `active` | `active` | **Yes** |
| `canceled_grace` | `active` or `trialing` with `cancel_at_period_end=true` | **Yes until `current_period_end`** |
| `past_due` | `past_due` | **Policy decision** — recommend limited grace (read-only saved plan) then lose Premium write features |
| `unpaid` | `unpaid` | **No** Premium write features |
| `incomplete` | `incomplete`, `incomplete_expired` | **No** |
| `canceled` / `expired` | `canceled` after period end; or no sub | **No** Premium features |
| `none` | No Journey subscription row | **No** |

Helper: `has_journey_premium_access(user_id): bool` derived only from `user_product_subscriptions` where `product_key='journey'` and status ∈ access set.

Calculator Premium continues to use existing logic until optionally migrated to the same table under `product_key='consumer_calculators'`.

### 4.4 What must not happen

- Writing Journey trial into `users.subscription_status='premium'` as the only signal
- CFA webhook inserting Journey checkouts into `calcforadvisors_subscribers`
- Deleting or mutating browser `localStorage` Journey keys during entitlement sync

---

## 5. Stripe webhook event map

### 5.1 Endpoint recommendation

Prefer a **shared router** (new), e.g. `stripe-webhook.php` at ronbelisle.com root or `includes`-backed endpoint, that:

1. Verifies signature with `STRIPE_WEBHOOK_SECRET` (or a dedicated Journey endpoint secret if Stripe Dashboard uses a separate endpoint — product/ops choice)
2. Inserts `event_id` into `stripe_webhook_events` (unique); on duplicate → return 200 and stop
3. Resolves Price ID → product key
4. Dispatches to product handler (`journey`, `consumer`, `cfa`)

CFA’s existing `calcforadvisors/stripe-webhook.php` may remain temporarily if Dashboard already points there; long-term, consolidate routing so Journey/consumer events are not skipped.

### 5.2 Events and handling (Journey)

| Event | Locate | Update | User experience |
|---|---|---|---|
| `checkout.session.completed` | `client_reference_id` / metadata `user_id`; subscription id; Price from line items | Upsert Journey subscription row; set `trialing`/`active` from Subscription object | Success page already shown; Premium workspace unlocks after webhook (usually seconds) |
| `customer.subscription.created` | `sub.id` / metadata | Upsert row; sync trial/period fields | Silent |
| `customer.subscription.updated` | `sub.id` | Sync `stripe_status`, period end, cancel_at_period_end, price changes | Access may change (e.g. past_due) |
| `customer.subscription.deleted` | `sub.id` | Mark `canceled`/`expired`; clear access | Premium features disable; free Journey remains |
| `invoice.paid` | subscription on invoice | Confirm `active`/`trialing`; clear past_due when appropriate | Silent / optional email later |
| `invoice.payment_failed` | subscription on invoice | Set `past_due` | Soft banner on Journey/account: update payment method |
| `customer.subscription.trial_will_end` | subscription | Optional notification flag / email | Calm reminder email (optional Milestone 6) |

### 5.3 Idempotency, ordering, verification, logging

- **Signature:** `\Stripe\Webhook::constructEvent` only; reject invalid signatures with 400.
- **Idempotency:** Unique `event_id`. Re-delivery is a no-op success.
- **Ordering:** Apply by reading the **latest Subscription object from Stripe API** when an update arrives out of order, rather than trusting event payload alone for final status.
- **Logging:** Event id, type, product_key, user_id, subscription id, result. **Never** log full PAN, raw secrets, or full customer PII payloads.
- **Success page role:** Verify Checkout Session completed for UX + set short-lived “checkout pending sync” UI; poll or soft-refresh entitlement from DB; **do not** permanently grant Journey Premium solely in `success.php`.

---

## 6. Account and login flows

Auth remains on **ronbelisle.com**. Journey uses safe `return=` URLs already supported by `auth_flow_helpers.php`. No parent-domain SSO in v1.

### Flow matrix

| Case | Behavior |
|---|---|
| **A. Not signed in** | Journey CTA → `register.php?intent=journey_trial&return=https://journey.ronbelisle.com/...` (or login link). After auth → Journey Checkout → Stripe → success → return to Journey workspace/invitation target. |
| **B. Free ronbelisle.com account** | Login if needed → Journey Checkout with `user_id` metadata → webhook entitles Journey only. Calculator Premium unchanged. |
| **C. Already Journey Premium** | CTA becomes “Open planning workspace” / “Manage billing”; no second trial Checkout. |
| **D. Previously used trial** | Stripe will typically reject a second trial on same Customer/Price rules; show “Subscribe to continue” without promising another free trial. |
| **E. Trial expired** | Free phases still work; Premium workspace locked for writes; CTA “Restart Premium” / Checkout without trial (or with trial only if Stripe allows — do not over-promise). |
| **F. Past due** | Banner + Billing Portal link; recommended: read-only last saved plan (after save API exists). |
| **G. Has calculator Premium, not Journey** | Distinct product. Offer Journey trial Checkout; do not auto-entitle Journey from calculator Premium. |

### Return URL rules

- Allow only `https://journey.ronbelisle.com/...` (existing helper) or same-site relative paths on ronbelisle.com.
- Store Journey return in `redirect_after_premium` for trial intent; consume once after success.
- Reject open redirects.

### Session fixation

- Regenerate session id on login (existing pattern should be verified/enforced in Milestone 3).

---

## 7. Trial-start flow

1. User accepts calm invitation on Journey (no card fields on that page).
2. Redirect to ronbelisle.com auth if needed (`intent` distinguishing Journey trial from calculator 7-day trial).
3. Journey-specific Checkout creator (new endpoint, e.g. `journey/checkout.php` or `checkout-journey.php`) creates Session:
   - Journey Price ID
   - `trial_period_days: 30`
   - `payment_method_types: ['card']` (if card-required decision confirmed)
   - metadata as in §3.2
4. Stripe hosted Checkout.
5. `success` page confirms completion, shows “Returning to your Journey…”, redirects to Journey.
6. Webhook writes entitlement; Journey page reads `has_journey_premium_access`.

**Calculator `checkout.php` must not be reused unchanged** if it hardcodes 7-day trial and calculator Prices.

---

## 8. Premium transition placement

### 8.1 Reconciling `FREE_TO_PREMIUM_TRANSITION.md`

That document assumed Phases 4–6 were Premium and invited trial **after Phase 3**.  
**Current product reality:** all six phases are public and free.

Therefore the Phase-3-gated “continue into Stress Test via Premium” narrative is **obsolete as the primary launch invitation**.

Preserve from that doc:

- Celebrate accomplishment
- Premium as natural continuity, not a teaser reveal
- 30-day trial, calm CTA, respectful not-now
- No pricing/checkout fields on the coaching page
- Remove conflicting Phase 1→2 7-day Journey trial invitations when this work ships

### 8.2 Recommended production invitation placement

| Location | Role |
|---|---|
| **Primary:** After Phase 6 successful save (Journey-completion section) | Celebrate full initial Journey; introduce Premium as **ongoing planning workspace** (save across devices, revise, compare, keep current). |
| **Secondary:** Homepage completed-Journey state | Softer reminder when CTA is “Review Your Plan”; link to the same coaching page or workspace. |
| **Not primary:** After Phase 3 only | Optional later “workspace tip,” not the main trial pitch, so free Phases 4–6 never feel paywalled. |

**Recommended route:** `journey.ronbelisle.com/phases/continue-to-premium.php` (or `/premium-continuity.php`) shown when Phase 6 is saved (or Journey marked complete), with narrative rewritten for **continuity after six free phases**, not access to Phase 4.

**Not-now path:** Return to Journey homepage / review Phase 3–6; localStorage progress preserved.

---

## 9. Browser-local to account-backed continuity plan

### Release split

| Release | Continuity behavior |
|---|---|
| **R0 (Milestones 1–4)** | localStorage remains source of truth for plan data. Auth/trial may ship. **Do not claim cross-device sync.** |
| **R1 (Milestone 5)** | Account-backed save API + explicit import consent. |
| **R2 (Milestone 6)** | Versions, alternatives compare, richer workspace. |

### Rules

1. **Never delete or invalidate** `rbJourneyProgressV1` automatically.
2. On login/trial start (R0): leave localStorage intact; optional non-blocking note that cloud save is coming / available.
3. **Import consent (R1):** modal/page: “Save the plan in this browser to your account?”  
   - If server has no plan → import local → server becomes SoT for that user.  
   - If server newer → offer Keep server / Replace with this browser / Cancel.  
   - If local newer → offer Upload browser plan / Keep server.
4. Conflict clock: use record `updatedAt` timestamps already present in Journey records; if missing, treat as equal and ask.
5. Browser/device id: optional `journey_client_id` in localStorage for support diagnostics; **not required** for v1 merge if user explicitly consents.
6. **Free account without Journey Premium (product decision):**  
   - Recommended: allow free ronbelisle.com accounts, but **cloud Journey save is Premium**.  
   - Free users keep localStorage-only Journey.  
   - Mark for approval if free cloud save is desired.

### Premium vs free capabilities (planned)

| Capability | Free | Journey Premium |
|---|---|---|
| All six phases | Yes | Yes |
| localStorage progress | Yes | Yes |
| Account-backed save/restore (cross-device) | No (until/unless product changes) | Yes (R1+) |
| Saved review history / versions | No | Yes (R2) |
| Compare alternatives | No / limited local only | Yes (R2) |
| Billing portal / status | N/A | Yes |

---

## 10. Access-enforcement strategy

- **Server-side PHP is authoritative** for Premium-only APIs (save, list versions, import, portal session creation).
- **Client-side UI** shows/hides Premium CTAs and banners; never trust UI alone.
- **Phases 1–6 stay ungated** in the first release entitlement system.
- Entitlement unlocks **workspace capabilities**, not phase routes.

Immediate checks:

```text
has_journey_premium_access(user) → allow journey_plans write APIs
!access → 402/403 JSON with calm upgrade URL; phases still load
```

---

## 11. Billing-management flow

| Action | Mechanism |
|---|---|
| View status | Journey account strip + ronbelisle.com `account.php` Journey section reading `user_product_subscriptions` |
| Update payment method | Stripe Customer Portal |
| Change monthly/annual | Portal (if configured) or support Checkout for change; confirm Portal products include Journey Prices |
| Cancel | Portal; access through period end when `cancel_at_period_end` |
| Renew/reactivate | Portal or new Checkout |
| Portal entry | Server-created Portal Session for the Journey Stripe Customer; return URL back to Journey or account |

### Recommended post-cancel / post-trial policy (needs approval)

| Surface | After trial/cancel |
|---|---|
| Six free phases | **Remain available** |
| Last saved cloud plan | **Read-only** (once save API exists) |
| Edit cloud plan, new versions, compare, cross-device sync | **Requires active/trialing Journey Premium** |
| localStorage copy | Remains on device; user may continue free locally |

---

## 12. Security controls

| Control | Requirement |
|---|---|
| CSRF | Tokens on state-changing Journey/account POSTs; Stripe Checkout itself is external |
| Webhook signatures | Mandatory; fail closed |
| Return URL validation | Existing Journey allowlist only |
| Session fixation | Regenerate on login |
| Ownership | All plan APIs scoped by `user_id` from session, never from client-supplied owner id alone |
| Secrets | Config/env only; never commit keys |
| Logs | No secrets, no full webhook PII dumps |
| Event retention | Keep `stripe_webhook_events` for ops/audit; define retention window (e.g. 90 days) as ops decision |
| DB constraints | Unique subscription id; FK user_id |
| Rate limiting | Auth + checkout session creation + import API |
| Duplicate checkout | If active/trialing Journey sub exists, refuse new trial Checkout and send to Portal/workspace |

---

## 13. Database migration plan

1. Add `stripe_webhook_events`.
2. Add `user_product_subscriptions`.
3. **Do not** drop or redefine `users.subscription_status` in Milestone 1 (backward compatible for calculators).
4. Optional later migration: backfill calculator Premium into `user_product_subscriptions` (`product_key='consumer_calculators'`) — separate approval.
5. Milestone 5: add `journey_plans` / `journey_plan_versions` with JSON payload + timestamps + schema version.

Rollback: feature-flag Journey Checkout/webhook handler off; tables can remain empty; free Journey unaffected.

---

## 14. Testing strategy

| Layer | Coverage |
|---|---|
| Unit | Price-ID → product routing; status mapping; idempotent event apply |
| Integration | Stripe test-mode fixtures / CLI webhook trigger for each event type |
| Auth | Return URL allow/deny; trial intent; already-subscribed guard |
| Continuity | Import merge matrix (empty server, newer server, newer local) |
| Regression | CFA webhook still only touches CFA Prices; calculator Premium path unchanged |
| Manual | Full A–G login matrix; cancel + period-end; past_due banner |

Automate webhook idempotency tests before enabling live Journey Checkout.

---

## 15. Rollout milestones

### Milestone 1 — Stripe catalog spec + entitlement foundation

**In scope**

- Document Dashboard steps for Journey Product/Prices (30-day trial config)
- Add config key names (values set in ops config, not git)
- SQL migrations for `stripe_webhook_events` + `user_product_subscriptions`
- PHP read helpers: `has_journey_premium_access()` (returns false until data exists)

**Likely files**

- `sql/create_user_product_subscriptions.sql` (new)
- `sql/create_stripe_webhook_events.sql` (new)
- `includes/stripe_config.php` (new constants only)
- `includes/journey_entitlement.php` (new)
- this plan / short ops checklist

**Out of scope:** Checkout UI, Journey page changes, live Prices creation without ops approval.

**Verify:** Migrations apply cleanly on staging DB; helpers load; no UX change.

**Rollback:** Drop new tables if needed; no production traffic depends on them yet.

---

### Milestone 2 — Reliable webhook synchronization + tests

**In scope**

- Shared/routed webhook processor with Price-ID discrimination
- Journey handler for §5 events; idempotency
- Optionally begin writing consumer calculator cancel/past_due into the new table or dual-write — **repair entitlement weakness** without breaking CFA
- Automated tests for duplicate events and status transitions

**Likely files**

- New `stripe-webhook-router.php` or extension of existing webhook
- `includes/stripe_webhook_journey.php` (new)
- Test scripts under `dev/` or PHPUnit if present
- Ops: Stripe Dashboard endpoint + events list

**Out of scope:** Public trial CTA on Journey.

**Verify:** Test-mode events update rows; duplicate delivery safe; CFA prices still update CFA tables only.

**Rollback:** Point Dashboard back; feature-flag Journey handler off.

---

### Milestone 3 — Account/login/return + Journey trial Checkout

**In scope**

- Journey Checkout endpoint (30-day, Journey Prices, metadata)
- Distinct auth intent from calculator 7-day trial
- Success page confirmation + return to Journey (non-authoritative grant)
- Guards for already-subscribed / prior trial

**Likely files**

- `checkout-journey.php` (new) or similar
- `success.php` (Journey branch UX only)
- `includes/auth_flow_helpers.php` (journey trial intent)
- Journey CTA targets (minimal)

**Out of scope:** Cloud save API; full workspace.

**Verify:** Test-mode trial start; webhook entitles; return URL lands on Journey; localStorage intact.

---

### Milestone 4 — Premium transition page + completed-Journey invitation

**In scope**

- Rewrite/implement continuity invitation after Phase 6 (and homepage completed state)
- Align/remove Phase 1→2 7-day Journey trial conflict
- Update obsolete Phase-3-only Premium-gate narrative in transition UX
- No embedded checkout fields

**Likely files**

- `journey.ronbelisle.com/phases/continue-to-premium.php` (new)
- `survivor-planning-phase.js` / Phase 6 completion UI
- `journey-progress.js` homepage completed CTA
- `continue-to-phase-2.php` cleanup as needed
- Update `FREE_TO_PREMIUM_TRANSITION.md` status note (docs only in this milestone if approved)

**Out of scope:** Alternatives compare engine.

**Verify:** Complete Journey → invitation → not-now preserves plan; trial CTA enters Milestone 3 flow.

---

### Milestone 5 — Account-backed save API + localStorage import

**In scope**

- `journey_plans` schema + save/load APIs
- Explicit import consent + conflict rules
- Premium enforcement on write APIs
- Clear UI copy: cross-device save only when cloud save succeeds

**Likely files**

- `api/journey_plan_save.php`, `api/journey_plan_load.php` (new)
- Journey JS sync module
- SQL migrations

**Out of scope:** Full multi-alternative comparison UX.

**Verify:** Two browsers with same account; free user denied cloud write; import consent paths.

---

### Milestone 6 — Premium workspace features + billing management

**In scope**

- Status/billing entry points; Portal for Journey customer
- Read-only vs edit policy after cancel (per approved policy)
- Optional: plan versions, alternatives compare, trial_will_end email
- Past-due banners

**Out of scope:** Parent-domain SSO; CFA merge; gating Phases 4–6.

---

## 16. Rollback strategy

| Layer | Rollback |
|---|---|
| Feature flags | Disable Journey Checkout CTAs and cloud save APIs |
| Webhook | Ignore Journey Price IDs (log + 200) |
| Stripe | Archive Journey Prices; existing subs managed in Stripe Dashboard |
| DB | Keep tables; stop writes; free Journey unaffected |
| UX | Remove invitation sections; keep six free phases |

Always preserve localStorage Journey data.

---

## 17. Unresolved product decisions

1. **Journey monthly/annual dollar prices** (do not invent).
2. **Ship annual at launch?** Yes / monthly-only.
3. **Card required at trial start?** Plan recommends **yes**.
4. **Past_due access:** full block vs read-only grace period length.
5. **Post-cancel read-only cloud plan:** approve recommended policy?
6. **Free registered users:** cloud save Premium-only (recommended) vs free cloud save.
7. **Unified “one Premium” vs separate Journey product entitlement** — plan assumes **separate Journey product entitlement** (recommended by audit).
8. **Webhook endpoint consolidation** vs temporary second Stripe endpoint.
9. **Whether calculator Premium cancel sync is repaired in Milestone 2** (recommended yes for shared foundation).

---

## 18. Explicit implementation approval checkpoints

| Checkpoint | Required before |
|---|---|
| **CP0 — Approve this plan** | Any coding |
| **CP1 — Approve Stripe amounts + card-required trial** | Creating live Journey Prices |
| **CP2 — Approve entitlement schema** | Running production migrations |
| **CP3 — Approve webhook router approach** | Pointing Stripe Dashboard at new handler |
| **CP4 — Approve invitation placement (post–Phase 6 primary)** | Milestone 4 UI |
| **CP5 — Approve free vs Premium capability matrix** | Milestone 5 save API |
| **CP6 — Approve post-cancel/read-only policy** | Milestone 6 billing UX |

No milestone starts without the prior checkpoint for that milestone’s irreversible ops actions (Prices, webhooks, migrations).

---

## Appendix A — Recommended config keys (names only)

```text
JOURNEY_STRIPE_PRICE_MONTHLY
JOURNEY_STRIPE_PRICE_ANNUAL          # optional at launch
JOURNEY_STRIPE_PRODUCT_ID            # optional
STRIPE_WEBHOOK_SECRET                # existing or dedicated
```

---

## Appendix B — What can ship before account-backed saving

Safe early ship (after Milestones 1–4):

- Auth + 30-day Journey trial Checkout
- Webhook-authoritative entitlement
- Calm post–Phase 6 invitation
- Billing Portal access for Journey subscribers
- Free six-phase Journey unchanged; localStorage continuity unchanged

Must **not** claim before Milestone 5:

- Cross-device restore
- Cloud backup of the Journey
- Multi-device sync

---

## Appendix C — Mapping to audit conclusions

| Audit conclusion | Plan response |
|---|---|
| Reuse consumer auth + Stripe account | §2, §6, §7 |
| Own Journey Product/Prices, 30-day trial | §3 |
| Do not use success.php as sole authority | §5, §7 |
| Multi-product entitlement | §4 |
| CFA remains separate | §2, §5 |
| Invitation narrative must match free six phases | §8 |
| localStorage preserved | §9 |

---

**End of plan.** Stop here until CP0 approval.
