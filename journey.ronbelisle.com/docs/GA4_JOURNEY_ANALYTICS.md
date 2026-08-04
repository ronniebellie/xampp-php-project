# Journey Google Analytics 4

**Measurement ID:** `G-8PMXKZ60L4`  
**Property:** Journey Retirement Planning (dedicated GA4 property)  
**Host:** `journey.ronbelisle.com`

The main calculators site (`ronbelisle.com`) continues to use `G-3NB2DLYQFZ` and is not mixed into this property.

## Where the tag is installed

| Surface | Install location |
|---------|------------------|
| Journey pages | `journey.ronbelisle.com/includes/analytics.php`, loaded from `journey.ronbelisle.com/includes/site-header.php` |

The Journey tag does **not** include the shared `includes/analytics.php` file. A page-level guard (`window.__journeyGtagConfigured`) prevents duplicate `gtag('config')` calls.

Journey funnel helpers live in `journey.ronbelisle.com/assets/js/journey-analytics.js`.

## Privacy rules

Do **not** send to GA4:

- names, emails, account IDs
- Social Security amounts, spending values, balances, notes
- other financial inputs or PII

Safe event parameters only:

- `phase_number`, `phase_name`
- `account_state`: `anonymous` | `free` | `premium_trial` | `premium`
- `storage_mode`: `browser` | `account`
- `journey_status`: `started` | `incomplete` | `completed`
- `source_page` (path only)
- `placement` (for promotion clicks)

## Journey events

| Event | When it fires | Deduping |
|-------|---------------|----------|
| `journey_begin` | First Begin/Continue CTA click, or first open of the Phase 1 planner | Once per browser (localStorage) |
| `phase_1_complete` … `phase_6_complete` | After successful phase save/completion | Once per phase per browser session |
| `journey_complete` | Immediately after phase 6 completes when all six phases are done | Once per browser (localStorage) |
| `free_account_start` | Click “Create Free Account and Continue” on the Phase 2 interstitial | Once per session |
| `free_account_complete` | Return to Journey authenticated with `?from=account` | Once per browser session key |
| `journey_premium_trial_start` | Premium checkout success page when entitlement is active | Once per browser session |
| `journey_pdf_download` | Successful Premium PDF download | At most once per minute |
| `journey_sign_in` | Auth chrome reports authenticated | Once per session |
| `journey_return_visit` | Returning visitor with prior Journey visit marker | Once per session |
| `journey_promotion_click` | Click Journey promo on ronbelisle.com | Via `data-rb-event` on main site (main-site property) |

Automatic `page_view` events come from the Journey gtag config (`send_page_view: true`).

## Isolating Journey traffic in GA4

This property is Journey-only. Use Realtime / standard reports on the **Journey Retirement Planning** property. Hostname should be `journey.ronbelisle.com`.

## Journey funnel exploration

Suggested funnel steps (Exploration → Funnel exploration):

1. `journey_begin`
2. `phase_1_complete`
3. `phase_2_complete`
4. `phase_3_complete`
5. `phase_4_complete`
6. `phase_5_complete`
7. `phase_6_complete` or `journey_complete`
8. Optional branch: `free_account_start` → `free_account_complete`
9. Optional branch: `journey_premium_trial_start`
10. Optional: `journey_pdf_download`

## Verification checklist

- [ ] Tag Assistant / page source shows `G-8PMXKZ60L4` once on a Journey page
- [ ] Page source does **not** contain `G-3NB2DLYQFZ`
- [ ] Realtime / DebugView shows `page_view` with hostname `journey.ronbelisle.com`
- [ ] Completing a phase fires `phase_N_complete` once (refresh does not duplicate)
- [ ] No event parameters contain financial amounts or emails
