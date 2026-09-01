-- Independent final preflight (READ ONLY): SELECT statements only.
SELECT DATABASE() AS connected_database;
SELECT COUNT(*) AS subscriber_count FROM calcforadvisors_subscribers;
SELECT plan,status,COUNT(*) accounts FROM calcforadvisors_subscribers GROUP BY plan,status ORDER BY plan,status;
SELECT COUNT(*) AS required_ids FROM calcforadvisors_subscribers WHERE id BETWEEN 1 AND 16;
SELECT COUNT(*) AS scenarios_table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_scenarios';
SELECT COUNT(*) AS phase2_column_count FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_subscribers' AND COLUMN_NAME IN ('portal_slug','advisor_name','public_email','phone','website_url','disclosure_text','stripe_subscription_status','trial_ends_at','access_ends_at','trial_used_at','past_due_started_at');
SELECT COUNT(*) AS phase2_index_count FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_subscribers' AND INDEX_NAME IN ('uk_portal_slug','idx_cfa_stripe_status','idx_cfa_access_ends');
SELECT COUNT(*) AS expected_legacy_slugs FROM calcforadvisors_subscribers WHERE trial_slug IN ('c75c29de9337e761','b8d02011bda427ee');
SELECT COUNT(*) AS unexpected_legacy_slugs FROM calcforadvisors_subscribers WHERE trial_slug IS NOT NULL AND trial_slug NOT IN ('c75c29de9337e761','b8d02011bda427ee');
SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_subscribers';
SELECT COLUMN_TYPE,IS_NULLABLE,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_subscribers' AND COLUMN_NAME='id';
SELECT id,SHA2(stripe_customer_id,256) customer_hash,SHA2(stripe_subscription_id,256) subscription_hash FROM calcforadvisors_subscribers WHERE id IN (1,2,3,4,13) ORDER BY id;
