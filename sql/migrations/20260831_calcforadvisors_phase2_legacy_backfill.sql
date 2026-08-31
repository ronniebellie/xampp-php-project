-- CalcForAdvisors 2.0 Phase 2: guarded legacy branding/trial backfill.
-- Run only after the foundation migration and the read-only audit.
-- This preserves subscriber IDs, Stripe IDs, passwords, and scenario ownership.

START TRANSACTION;

-- Seed only already-valid legacy slugs. trial_slug already has a unique index,
-- so copying valid non-null values cannot create duplicate portal_slug values.
UPDATE calcforadvisors_subscribers
   SET portal_slug = LOWER(trial_slug)
 WHERE portal_slug IS NULL
   AND trial_slug IS NOT NULL
   AND CHAR_LENGTH(trial_slug) BETWEEN 3 AND 48
   AND LOWER(trial_slug) REGEXP '^[a-z0-9][a-z0-9-]*[a-z0-9]$'
   AND LOWER(trial_slug) NOT IN (
       'account','admin','api','assets','billing','calculator','checkout',
       'login','logout','p','portal','pricing','register','robots','sitemap',
       'stripe','success','support','trial','www'
   );

-- Preserve the original legacy 30-day expiration and mark it as already used.
-- This does not grant another trial and does not change the legacy plan/status.
UPDATE calcforadvisors_subscribers
   SET trial_ends_at = DATE_ADD(created_at, INTERVAL 30 DAY),
       trial_used_at = COALESCE(trial_used_at, created_at)
 WHERE plan = 'free'
   AND trial_ends_at IS NULL;

-- Review these counts before COMMIT when running interactively.
SELECT COUNT(*) AS seeded_portal_slugs
  FROM calcforadvisors_subscribers
 WHERE portal_slug IS NOT NULL;

SELECT COUNT(*) AS legacy_trials_marked_used
  FROM calcforadvisors_subscribers
 WHERE plan = 'free' AND trial_used_at IS NOT NULL;

COMMIT;
