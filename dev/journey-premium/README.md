# Journey Premium — development checks

## Milestone 1 — entitlement helpers

```bash
/Applications/XAMPP/xamppfiles/bin/php \
  /Applications/XAMPP/xamppfiles/htdocs/dev/journey-premium/test-milestone1.php
```

## Milestone 2 — webhook sync

```bash
/Applications/XAMPP/xamppfiles/bin/php \
  /Applications/XAMPP/xamppfiles/htdocs/dev/journey-premium/test-milestone2.php
```

Pure tests always run. Database-backed checks run when local MySQL (`ronbelisle_premium`) is reachable.

Apply the local migration first (when MySQL is running):

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root ronbelisle_premium \
  < sql/migrations/20260725_001_journey_premium_m1_up.sql
```

See `sql/migrations/README_JOURNEY_PREMIUM_M1.md`, `docs/JOURNEY_PREMIUM_MILESTONE_1.md`, and `docs/JOURNEY_PREMIUM_MILESTONE_2.md`.
