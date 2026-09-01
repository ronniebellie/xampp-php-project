-- CalcForAdvisors 2.0 Phase 2 Stage C: guarded legacy trial backfill.
-- Execute only through the fixed CLI migration runner after Stages A and B.
-- This preserves subscriber IDs, Stripe IDs, passwords, and scenario ownership.

START TRANSACTION;

-- Preserve the original legacy 30-day expiration and mark it as already used.
-- This does not grant another trial and does not change the legacy plan/status.
UPDATE calcforadvisors_subscribers
   SET trial_ends_at = DATE_ADD(created_at, INTERVAL 30 DAY),
       trial_used_at = COALESCE(trial_used_at, created_at)
 WHERE plan = 'free'
   AND trial_ends_at IS NULL;

SELECT COUNT(*) AS legacy_trials_marked_used
  FROM calcforadvisors_subscribers
 WHERE plan = 'free' AND trial_used_at IS NOT NULL;

COMMIT;
