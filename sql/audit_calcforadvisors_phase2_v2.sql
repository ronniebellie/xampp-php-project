-- CalcForAdvisors Phase 2 production audit v2 (READ ONLY).
-- Production fact accommodated here: calcforadvisors_scenarios may not exist.
-- This file contains SHOW and SELECT statements only. It never selects email
-- addresses, subscriber IDs, password hashes, tokens, or Stripe ID values.

-- 1-2. Existing subscriber schema and indexes.
SHOW COLUMNS FROM calcforadvisors_subscribers;
SHOW INDEX FROM calcforadvisors_subscribers;

-- 3-5. Scenario table discovery through information_schema. These return zero
-- rows rather than failing when calcforadvisors_scenarios is absent.
SELECT TABLE_NAME, ENGINE, TABLE_ROWS, TABLE_COLLATION
  FROM information_schema.TABLES
 WHERE TABLE_SCHEMA = DATABASE()
   AND TABLE_NAME = 'calcforadvisors_scenarios';

SELECT COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, IS_NULLABLE,
       COLUMN_DEFAULT, COLUMN_KEY, EXTRA
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE()
   AND TABLE_NAME = 'calcforadvisors_scenarios'
 ORDER BY ORDINAL_POSITION;

SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
  FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE()
   AND TABLE_NAME = 'calcforadvisors_scenarios'
 ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- 6-7. Subscriber totals and the complete legacy plan/status inventory.
SELECT COUNT(*) AS subscriber_accounts
  FROM calcforadvisors_subscribers;

SELECT plan, status, COUNT(*) AS accounts
  FROM calcforadvisors_subscribers
 GROUP BY plan, status
 ORDER BY plan, status;

-- 8. Duplicate normalized-email exposure without returning email addresses.
SELECT COUNT(*) AS duplicate_email_groups,
       COALESCE(SUM(accounts), 0) AS accounts_in_duplicate_groups,
       COALESCE(MAX(accounts), 0) AS largest_duplicate_group
  FROM (
        SELECT COUNT(*) AS accounts
          FROM calcforadvisors_subscribers
         GROUP BY LOWER(TRIM(email))
        HAVING COUNT(*) > 1
       ) AS duplicate_emails;

-- 9. Login credential coverage. Hash values are never selected.
SELECT SUM(CASE WHEN password_hash IS NULL OR password_hash = '' THEN 1 ELSE 0 END) AS missing_password_hash,
       SUM(CASE WHEN password_hash IS NOT NULL AND password_hash != '' THEN 1 ELSE 0 END) AS populated_password_hash
  FROM calcforadvisors_subscribers;

-- 10-12. Legacy slug coverage, duplicate exposure, and validation categories.
SELECT SUM(CASE WHEN trial_slug IS NULL OR trial_slug = '' THEN 1 ELSE 0 END) AS missing_trial_slug,
       SUM(CASE WHEN trial_slug IS NOT NULL AND trial_slug != '' THEN 1 ELSE 0 END) AS populated_trial_slug
  FROM calcforadvisors_subscribers;

SELECT COUNT(*) AS duplicate_slug_groups,
       COALESCE(SUM(accounts), 0) AS accounts_in_duplicate_slug_groups,
       COALESCE(MAX(accounts), 0) AS largest_duplicate_slug_group
  FROM (
        SELECT COUNT(*) AS accounts
          FROM calcforadvisors_subscribers
         WHERE trial_slug IS NOT NULL AND trial_slug != ''
         GROUP BY LOWER(trial_slug)
        HAVING COUNT(*) > 1
       ) AS duplicate_slugs;

SELECT SUM(CASE WHEN trial_slug IS NOT NULL AND trial_slug != ''
                     AND CHAR_LENGTH(trial_slug) NOT BETWEEN 3 AND 48 THEN 1 ELSE 0 END) AS invalid_slug_length,
       SUM(CASE WHEN trial_slug IS NOT NULL AND trial_slug != ''
                     AND LOWER(trial_slug) NOT REGEXP '^[a-z0-9][a-z0-9-]*[a-z0-9]$' THEN 1 ELSE 0 END) AS invalid_slug_format,
       SUM(CASE WHEN LOWER(trial_slug) IN (
                    'account','admin','api','assets','billing','calculator','checkout',
                    'login','logout','p','portal','pricing','register','robots','sitemap',
                    'stripe','success','support','trial','www'
                ) THEN 1 ELSE 0 END) AS reserved_slugs
  FROM calcforadvisors_subscribers;

-- 13-16. Stripe identity coverage and duplicate exposure. No Stripe ID values
-- are returned; authoritative subscription status still requires Stripe review.
SELECT plan, status, COUNT(*) AS paid_accounts_missing_stripe_identity
  FROM calcforadvisors_subscribers
 WHERE plan IN ('monthly', 'annual')
   AND (stripe_customer_id IS NULL OR stripe_customer_id = ''
        OR stripe_subscription_id IS NULL OR stripe_subscription_id = '')
 GROUP BY plan, status
 ORDER BY plan, status;

SELECT status, COUNT(*) AS free_accounts_with_stripe_identity
  FROM calcforadvisors_subscribers
 WHERE plan = 'free'
   AND (stripe_customer_id IS NOT NULL AND stripe_customer_id != ''
        OR stripe_subscription_id IS NOT NULL AND stripe_subscription_id != '')
 GROUP BY status
 ORDER BY status;

SELECT COUNT(*) AS duplicate_stripe_customer_groups,
       COALESCE(SUM(accounts), 0) AS accounts_in_duplicate_customer_groups
  FROM (
        SELECT COUNT(*) AS accounts
          FROM calcforadvisors_subscribers
         WHERE stripe_customer_id IS NOT NULL AND stripe_customer_id != ''
         GROUP BY stripe_customer_id
        HAVING COUNT(*) > 1
       ) AS duplicate_customers;

SELECT COUNT(*) AS duplicate_stripe_subscription_groups,
       COALESCE(SUM(accounts), 0) AS accounts_in_duplicate_subscription_groups
  FROM (
        SELECT COUNT(*) AS accounts
          FROM calcforadvisors_subscribers
         WHERE stripe_subscription_id IS NOT NULL AND stripe_subscription_id != ''
         GROUP BY stripe_subscription_id
        HAVING COUNT(*) > 1
       ) AS duplicate_subscriptions;

-- 17. Legacy free-trial inventory and date range, without account identifiers.
SELECT CASE WHEN UTC_TIMESTAMP() < DATE_ADD(created_at, INTERVAL 30 DAY)
            THEN 'legacy_trial_active' ELSE 'legacy_trial_expired' END AS audit_state,
       COUNT(*) AS accounts,
       MIN(created_at) AS earliest_created_at,
       MAX(created_at) AS latest_created_at,
       MIN(DATE_ADD(created_at, INTERVAL 30 DAY)) AS earliest_trial_end,
       MAX(DATE_ADD(created_at, INTERVAL 30 DAY)) AS latest_trial_end
  FROM calcforadvisors_subscribers
 WHERE plan = 'free'
 GROUP BY audit_state
 ORDER BY audit_state;

-- 18-19. Branding coverage and URL anomaly counts. URL values are not selected.
SELECT SUM(CASE WHEN firm_name IS NULL OR firm_name = '' THEN 1 ELSE 0 END) AS missing_firm_name,
       SUM(CASE WHEN firm_name IS NOT NULL AND firm_name != '' THEN 1 ELSE 0 END) AS populated_firm_name,
       SUM(CASE WHEN logo_url IS NOT NULL AND logo_url != '' THEN 1 ELSE 0 END) AS populated_logo_url,
       SUM(CASE WHEN banner_url IS NOT NULL AND banner_url != '' THEN 1 ELSE 0 END) AS populated_banner_url
  FROM calcforadvisors_subscribers;

SELECT SUM(CASE WHEN logo_url IS NOT NULL AND logo_url != ''
                     AND logo_url NOT REGEXP '^https?://' THEN 1 ELSE 0 END) AS invalid_logo_urls,
       SUM(CASE WHEN banner_url IS NOT NULL AND banner_url != ''
                     AND banner_url NOT REGEXP '^https?://' THEN 1 ELSE 0 END) AS invalid_banner_urls
  FROM calcforadvisors_subscribers;

-- Production Stripe must be consulted before populating normalized subscription
-- status, paid-through dates, paid-trial dates, or past-due start dates.
