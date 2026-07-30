# Journey Premium checkout branding

## Controllable vs account-level branding

### Controllable per Journey Checkout Session
These are set in `journey_build_checkout_session_params()`:

- Stripe **Product** name on the Journey Price (Dashboard / catalog): currently **Retirement Planning Journey Premium**
- Stripe **Product** description: ongoing retirement planning copy
- Checkout `subscription_data.description`: `Retirement Planning Journey Premium`
- Checkout `custom_text.submit.message`: Journey-specific trial wording
- Session/subscription `metadata.product` / `product_key`: `journey`

Calculator Premium checkout continues to use Calculator Prices/Products and is unchanged by Journey checkout builders.

### Apple Pay merchant / business name (account-level)
Apple Pay’s payment sheet **merchant name** comes from the Stripe account’s **business profile / public business name**, not from the Checkout Session product line item.

That means Apple Pay can still show a global name such as:

> Ron Belisle’s Financial Calculators

even when the line item / product is Journey Premium.

**This cannot be overridden per Checkout Session** with the current Stripe Checkout + Apple Pay integration on a single Stripe account.

To change the Apple Pay merchant display name globally:

1. Stripe Dashboard → Settings → Business settings / Public details
2. Update the public business name carefully — it affects **all** products on the account (Calculator Premium and Journey Premium)

Recommended approach while sharing one Stripe account:

- Keep a neutral public business name if possible (e.g. “Ron Belisle”)
- Rely on Journey Product name + Checkout line/description text for Journey-specific clarity

### Statement descriptor
Card statement descriptors are also account/product constrained and often truncated. Prefer Stripe Dashboard settings; do not invent invalid descriptors in code.

## Verification
After deploy, create a Journey Checkout Session for monthly/annual and confirm:

- Line item / product shows Retirement Planning Journey Premium
- Submit helper text mentions Journey Premium trial
- Calculator Premium checkout product text remains Calculator-specific
