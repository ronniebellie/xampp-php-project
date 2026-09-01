-- Independent post-migration verification (READ ONLY): SELECT statements only.
SELECT COUNT(*) subscriber_count FROM calcforadvisors_subscribers;
SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_scenarios';
SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_KEY,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_scenarios' ORDER BY ORDINAL_POSITION;
SELECT CONSTRAINT_NAME,DELETE_RULE,REFERENCED_TABLE_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_scenarios';
SELECT COUNT(*) phase2_columns FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_subscribers' AND COLUMN_NAME IN ('portal_slug','advisor_name','public_email','phone','website_url','disclosure_text','stripe_subscription_status','trial_ends_at','access_ends_at','trial_used_at','past_due_started_at');
SELECT INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) columns_in_index FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_subscribers' AND INDEX_NAME IN ('uk_portal_slug','idx_cfa_stripe_status','idx_cfa_access_ends') GROUP BY INDEX_NAME,NON_UNIQUE ORDER BY INDEX_NAME;
SELECT id,status,stripe_subscription_status,trial_ends_at,access_ends_at,trial_used_at,past_due_started_at FROM calcforadvisors_subscribers WHERE id IN (1,2,3,4,13) ORDER BY id;
SELECT COUNT(*) expired_trials_marked_used FROM calcforadvisors_subscribers WHERE plan='free' AND trial_used_at=created_at AND trial_ends_at=DATE_ADD(created_at,INTERVAL 30 DAY) AND trial_ends_at<UTC_TIMESTAMP();
SELECT COUNT(*) populated_slugs,COUNT(DISTINCT portal_slug) unique_slugs FROM calcforadvisors_subscribers WHERE portal_slug IS NOT NULL;
SELECT COUNT(*) invalid_slugs FROM calcforadvisors_subscribers WHERE portal_slug IS NULL OR portal_slug NOT REGEXP '^[a-z0-9][a-z0-9-]{1,46}[a-z0-9]$' OR portal_slug IN ('account','admin','api','assets','billing','calculator','checkout','login','logout','p','portal','pricing','register','robots','sitemap','stripe','success','support','trial','www');
SELECT COUNT(*) advisor_scenarios FROM calcforadvisors_scenarios;
SELECT COUNT(*) consumer_scenarios FROM scenarios;
