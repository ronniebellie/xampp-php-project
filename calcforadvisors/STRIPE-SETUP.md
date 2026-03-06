# Stripe setup for calcforadvisors

## Configure price IDs

1. Open `includes/stripe_config.php`
2. Replace the placeholder values for calcforadvisors:

```php
define('CALCFORADVISORS_PRICE_MONTHLY', 'price_xxxx');  // From Stripe Product catalog
define('CALCFORADVISORS_PRICE_ANNUAL', 'price_xxxx');   // From Stripe Product catalog
```

Get the Price IDs from Stripe Dashboard → Product catalog → your calcforadvisors product → copy the Price ID for each plan.

## Sandbox (test) vs live

- **Sandbox testing:** Use `pk_test_...` and `sk_test_...` in `stripe_config.php`. Create your calcforadvisors product in the sandbox and use those price IDs. Test with card `4242 4242 4242 4242`.
- **Live:** Use `pk_live_...` and `sk_live_...` and your live product’s price IDs.

## Flow

1. User clicks **Subscribe** on the pricing page → `create-checkout-session.php?plan=monthly|annual`
2. PHP creates a Stripe Checkout Session and redirects to Stripe’s payment page
3. After payment, Stripe redirects to `success.php?session_id=...`
4. `success.php` verifies the session and shows a thank-you message
