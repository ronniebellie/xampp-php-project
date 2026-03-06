# Stripe Webhook Setup for calcforadvisors

The webhook handler records new subscribers and subscription changes in the database.

## 1. Create the database table

Run the SQL once in your database (phpMyAdmin or mysql CLI):

```bash
mysql -u root -p ronbelisle_premium < sql/create_calcforadvisors_subscribers_table.sql
```

Or copy the contents of `sql/create_calcforadvisors_subscribers_table.sql` and run it in phpMyAdmin.

## 2. Add the webhook secret to stripe_config.php

1. In Stripe Dashboard, go to **Developers** → **Webhooks**.
2. Click **Add endpoint**.
3. **Endpoint URL:** `https://calcforadvisors.com/stripe-webhook.php`
4. **Events to listen for:**
   - `checkout.session.completed`
   - `customer.subscription.deleted`
   - `invoice.payment_failed`
5. Click **Add endpoint**.
6. Copy the **Signing secret** (starts with `whsec_`).
7. In `includes/stripe_config.php`, set:
   ```php
   define('STRIPE_WEBHOOK_SECRET', 'whsec_your_actual_secret');
   ```

## 3. Test mode vs live mode

- **Test mode:** Create a webhook endpoint while in the sandbox and use the test signing secret.
- **Live mode:** Create a separate webhook endpoint (or switch to live) and use the live signing secret.

You need different `STRIPE_WEBHOOK_SECRET` values for test and live. Update `stripe_config.php` when switching.

## 4. Local testing with Stripe CLI

```bash
stripe listen --forward-to localhost/calcforadvisors/stripe-webhook.php
```

Stripe will show a temporary signing secret (e.g. `whsec_...`). Use that in `stripe_config.php` for local testing.

## Events handled

| Event                      | Action                                                      |
|----------------------------|-------------------------------------------------------------|
| `checkout.session.completed` | Insert or update subscriber (email, plan, status)           |
| `customer.subscription.deleted` | Set status to `canceled`                                   |
| `invoice.payment_failed`   | Set status to `past_due` for follow-up                      |
