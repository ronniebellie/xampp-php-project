-- One-time login token for post-registration redirect (Safari Private mode workaround).
-- Token is used in URL; no session cookie needed for initial trial-setup load.
ALTER TABLE calcforadvisors_subscribers
  ADD COLUMN trial_login_token VARCHAR(64) NULL,
  ADD COLUMN trial_login_token_expires DATETIME NULL;
