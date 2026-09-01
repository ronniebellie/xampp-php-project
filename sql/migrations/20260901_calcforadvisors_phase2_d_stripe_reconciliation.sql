-- Stage D reference SQL. Execute only through the fixed CLI migration runner.
-- IDs 1-4 are the four separately preserved, Stripe-verified historical
-- canceled records. Their access_ends_at values are verified Stripe period
-- ends and are already past; no current entitlement is granted.
-- ID 13 is preserved as unresolved/inactive because its stored Stripe
-- subscription was not retrievable. Stored Stripe identifiers are untouched.
-- trial_used_at is an introductory-trial eligibility marker based on the
-- original account timestamp; NULL trial_ends_at means no Stripe trial existed.

START TRANSACTION;
UPDATE calcforadvisors_subscribers SET stripe_subscription_status='canceled', access_ends_at='2026-05-06 21:19:56', trial_used_at=created_at WHERE id=1 AND status='canceled' AND stripe_subscription_status IS NULL AND access_ends_at IS NULL AND trial_used_at IS NULL;
UPDATE calcforadvisors_subscribers SET stripe_subscription_status='canceled', access_ends_at='2026-05-06 20:47:42', trial_used_at=created_at WHERE id=2 AND status='canceled' AND stripe_subscription_status IS NULL AND access_ends_at IS NULL AND trial_used_at IS NULL;
UPDATE calcforadvisors_subscribers SET stripe_subscription_status='canceled', access_ends_at='2026-05-06 20:55:30', trial_used_at=created_at WHERE id=3 AND status='canceled' AND stripe_subscription_status IS NULL AND access_ends_at IS NULL AND trial_used_at IS NULL;
UPDATE calcforadvisors_subscribers SET stripe_subscription_status='canceled', access_ends_at='2026-05-07 12:35:23', trial_used_at=created_at WHERE id=4 AND status='canceled' AND stripe_subscription_status IS NULL AND access_ends_at IS NULL AND trial_used_at IS NULL;
UPDATE calcforadvisors_subscribers SET status='inactive', stripe_subscription_status='unresolved', trial_used_at=created_at WHERE id=13 AND plan='monthly' AND status='active' AND stripe_subscription_status IS NULL AND trial_ends_at IS NULL AND access_ends_at IS NULL AND trial_used_at IS NULL AND past_due_started_at IS NULL;
COMMIT;
