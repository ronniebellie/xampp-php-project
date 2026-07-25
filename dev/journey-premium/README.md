# Journey Premium — development checks

## Milestone 1

```bash
/Applications/XAMPP/xamppfiles/bin/php \
  /Applications/XAMPP/xamppfiles/htdocs/dev/journey-premium/test-milestone1.php
```

Pure entitlement/Price-ID tests always run. Database insert/idempotency checks run only when local MySQL (`ronbelisle_premium`) is reachable via `includes/db_config.php`.

Apply the local migration first (when MySQL is running):

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root ronbelisle_premium \
  < sql/migrations/20260725_001_journey_premium_m1_up.sql
```

See `sql/migrations/README_JOURNEY_PREMIUM_M1.md` and `docs/JOURNEY_PREMIUM_MILESTONE_1.md`.
