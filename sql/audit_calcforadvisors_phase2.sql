-- CalcForAdvisors Phase 2 pre-migration audit (READ ONLY).
-- This script contains SELECT statements only and is safe to review/run against
-- a production replica or production with normal read-only query precautions.

-- Existing columns and indexes.
SHOW COLUMNS FROM calcforadvisors_subscribers;
SHOW INDEX FROM calcforadvisors_subscribers;
SHOW COLUMNS FROM calcforadvisors_scenarios;
SHOW INDEX FROM calcforadvisors_scenarios;

-- Account totals by legacy plan/status.
SELECT plan, status, COUNT(*) AS accounts
  FROM calcforadvisors_subscribers
 GROUP BY plan, status
 ORDER BY plan, status;

-- Duplicate normalized emails. Do not delete either record automatically.
SELECT LOWER(TRIM(email)) AS normalized_email, COUNT(*) AS accounts,
       GROUP_CONCAT(id ORDER BY id) AS subscriber_ids
  FROM calcforadvisors_subscribers
 GROUP BY LOWER(TRIM(email))
HAVING COUNT(*) > 1;

-- Missing login credentials.
SELECT id, email, plan, status, created_at
  FROM calcforadvisors_subscribers
 WHERE password_hash IS NULL OR password_hash = ''
 ORDER BY id;

-- Missing, duplicate, malformed, or reserved legacy slugs.
SELECT id, email, trial_slug, plan, status
  FROM calcforadvisors_subscribers
 WHERE trial_slug IS NULL OR trial_slug = ''
 ORDER BY id;

SELECT LOWER(trial_slug) AS normalized_slug, COUNT(*) AS accounts,
       GROUP_CONCAT(id ORDER BY id) AS subscriber_ids
  FROM calcforadvisors_subscribers
 WHERE trial_slug IS NOT NULL AND trial_slug != ''
 GROUP BY LOWER(trial_slug)
HAVING COUNT(*) > 1;

SELECT id, email, trial_slug
  FROM calcforadvisors_subscribers
 WHERE trial_slug IS NOT NULL
   AND (
       CHAR_LENGTH(trial_slug) NOT BETWEEN 3 AND 48
       OR LOWER(trial_slug) NOT REGEXP '^[a-z0-9][a-z0-9-]*[a-z0-9]$'
       OR LOWER(trial_slug) IN (
           'account','admin','api','assets','billing','calculator','checkout',
           'login','logout','p','portal','pricing','register','robots','sitemap',
           'stripe','success','support','trial','www'
       )
   )
 ORDER BY id;

-- Stripe/local identity and state risks.
SELECT id, email, plan, status, stripe_customer_id, stripe_subscription_id
  FROM calcforadvisors_subscribers
 WHERE plan IN ('monthly', 'annual')
   AND (stripe_customer_id IS NULL OR stripe_customer_id = ''
        OR stripe_subscription_id IS NULL OR stripe_subscription_id = '')
 ORDER BY id;

SELECT id, email, plan, status, stripe_customer_id, stripe_subscription_id
  FROM calcforadvisors_subscribers
 WHERE plan = 'free'
   AND (stripe_customer_id IS NOT NULL OR stripe_subscription_id IS NOT NULL)
 ORDER BY id;

-- Legacy trials, calculated from the existing created_at behavior.
SELECT id, email, created_at, DATE_ADD(created_at, INTERVAL 30 DAY) AS legacy_trial_end,
       CASE WHEN UTC_TIMESTAMP() < DATE_ADD(created_at, INTERVAL 30 DAY)
            THEN 'legacy_trial_active' ELSE 'legacy_trial_expired' END AS audit_state
  FROM calcforadvisors_subscribers
 WHERE plan = 'free'
 ORDER BY created_at;

-- Branding URL anomalies. Only HTTP(S) URLs are valid for Phase 2.
SELECT id, email, logo_url, banner_url
  FROM calcforadvisors_subscribers
 WHERE (logo_url IS NOT NULL AND logo_url != '' AND logo_url NOT REGEXP '^https?://')
    OR (banner_url IS NOT NULL AND banner_url != '' AND banner_url NOT REGEXP '^https?://')
 ORDER BY id;

-- Scenario ownership and subscriber preservation checks.
SELECT COUNT(*) AS scenarios,
       COUNT(DISTINCT subscriber_id) AS subscribers_with_scenarios
  FROM calcforadvisors_scenarios;

SELECT s.subscriber_id, COUNT(*) AS scenarios
  FROM calcforadvisors_scenarios s
  LEFT JOIN calcforadvisors_subscribers a ON a.id = s.subscriber_id
 WHERE a.id IS NULL
 GROUP BY s.subscriber_id;

SELECT a.id, a.email, a.plan, a.status, COUNT(s.id) AS scenarios
  FROM calcforadvisors_subscribers a
  JOIN calcforadvisors_scenarios s ON s.subscriber_id = a.id
 GROUP BY a.id, a.email, a.plan, a.status
 ORDER BY scenarios DESC, a.id;

-- Production Stripe must be consulted before populating normalized subscription
-- status, paid-through dates, trial dates for paid subscriptions, or past-due
-- start dates. The legacy schema does not contain enough evidence to infer them.
