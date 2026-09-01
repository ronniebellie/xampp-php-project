-- Stage E reference SQL. Execute only through the fixed CLI migration runner.
-- IDs 10 and 12 preserve their valid legacy slugs. All other verified records
-- currently lack a usable firm_name, so the approved advisor-{subscriber_id}
-- fallback is deterministic, non-PII, unique, and stable after assignment.
START TRANSACTION;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-1' WHERE id=1 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-2' WHERE id=2 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-3' WHERE id=3 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-4' WHERE id=4 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-5' WHERE id=5 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-6' WHERE id=6 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-7' WHERE id=7 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-8' WHERE id=8 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-9' WHERE id=9 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='c75c29de9337e761' WHERE id=10 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-11' WHERE id=11 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='b8d02011bda427ee' WHERE id=12 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-13' WHERE id=13 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-14' WHERE id=14 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-15' WHERE id=15 AND portal_slug IS NULL;
UPDATE calcforadvisors_subscribers SET portal_slug='advisor-16' WHERE id=16 AND portal_slug IS NULL;
COMMIT;
