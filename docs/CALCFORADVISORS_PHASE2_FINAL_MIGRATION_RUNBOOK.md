# CalcForAdvisors Phase 2 final migration runbook

Status: prepared only. Do not execute without a new explicit migration approval.
Database migration, application deployment, Stripe changes, and Phase 4 are
separate operations. The migration package is intentionally excluded from the
public release artifact.

## Fixed sequence and safety model

Run only `A → B → C → D → E`. The fixed CLI runner accepts no SQL, file path,
subscriber ID, Stripe identifier, or value from the command line. It loads the
existing application database configuration and refuses any database other than
`ronbelisle_premium`. Every stage checks its exact preconditions, affected-row
counts, and postconditions. An already-exact completed stage is a no-op; a
partial or unexpected state is a STOP.

- A creates the historically missing `calcforadvisors_scenarios` table and FK.
- B adds the 11 nullable foundation columns and three indexes.
- C marks all 11 expired legacy free trials used and preserves their original
  `created_at + 30 days` expiration.
- D preserves IDs 1–4 as separate canceled histories, writes their verified
  paid-through dates, and marks ID 13 unresolved/inactive. It never changes
  Stripe identifiers. For paid histories, `trial_used_at=created_at` is only an
  introductory-trial eligibility marker. A NULL `trial_ends_at` truthfully means
  Stripe confirmed no actual trial (IDs 1–4) or no authoritative trial evidence
  exists (ID 13).
- E preserves the valid legacy slugs for IDs 10 and 12. All other records lack a
  usable firm name at the approved snapshot, so the approved deterministic
  `advisor-{subscriber_id}` fallback is used. No slug comes from email.

## Package staging (not application deployment)

From the reviewed local commit, copy only these non-public operational files to
a root-owned directory outside every web root and immutable release:

```bash
ssh root@64.23.181.64 'umask 077; install -d -m 700 /root/cfa-phase2-package'
scp scripts/calcforadvisors-phase2-migration.php scripts/calcforadvisors-db-option-file.php root@64.23.181.64:/root/cfa-phase2-package/
scp sql/preflight_calcforadvisors_phase2_final.sql sql/verify_calcforadvisors_phase2_final.sql root@64.23.181.64:/root/cfa-phase2-package/
ssh root@64.23.181.64 'chmod 600 /root/cfa-phase2-package/*'
```

Confirm `/root/cfa-phase2-package` is not under `/var/www`, then compare local
and server SHA-256 values. STOP on any mismatch.

## Maintenance mode

This uses an Apache configuration include, not an active release edit. It makes
only the CalcForAdvisors hosts return 503.

```bash
ssh root@64.23.181.64
umask 077
cat > /etc/apache2/conf-available/calcforadvisors-phase2-maintenance.conf <<'APACHE'
RewriteEngine On
RewriteCond %{HTTP_HOST} ^(www\.)?calcforadvisors\.com$ [NC]
RewriteRule ^ - [R=503,L]
APACHE
chmod 600 /etc/apache2/conf-available/calcforadvisors-phase2-maintenance.conf
a2enconf calcforadvisors-phase2-maintenance
apache2ctl configtest
systemctl reload apache2
curl -sS -o /dev/null -w '%{http_code}\n' https://calcforadvisors.com/
```

Expected: Apache syntax `OK`, reload succeeds, and curl returns `503`. STOP if
the config test fails or the site does not return 503.

## Full private backup

Keep the backup outside `/var/www`, owned by root, directory mode 0700 and files
0600. The option-file helper reads `/etc/ronbelisle/config.php`; no password is
placed in an argument, environment command, process listing, or shell history.

```bash
export CFA_BACKUP_DIR="/var/backups/ronbelisle/cfa-phase2-$(date -u +%Y%m%dT%H%M%SZ)"
install -d -m 700 "$CFA_BACKUP_DIR"
php /root/cfa-phase2-package/calcforadvisors-db-option-file.php
test "$(stat -c '%a' /run/calcforadvisors-phase2-mysql.cnf)" = 600
set -o pipefail
mysqldump --defaults-extra-file=/run/calcforadvisors-phase2-mysql.cnf --single-transaction --routines --triggers --events --hex-blob --default-character-set=utf8mb4 --databases ronbelisle_premium | gzip -9 > "$CFA_BACKUP_DIR/ronbelisle_premium.sql.gz"
chmod 600 "$CFA_BACKUP_DIR/ronbelisle_premium.sql.gz"
gzip -t "$CFA_BACKUP_DIR/ronbelisle_premium.sql.gz"
sha256sum "$CFA_BACKUP_DIR/ronbelisle_premium.sql.gz" > "$CFA_BACKUP_DIR/ronbelisle_premium.sql.gz.sha256"
chmod 600 "$CFA_BACKUP_DIR/ronbelisle_premium.sql.gz.sha256"
cd "$CFA_BACKUP_DIR" && sha256sum -c ronbelisle_premium.sql.gz.sha256
zgrep -m1 'CREATE DATABASE.*ronbelisle_premium' "$CFA_BACKUP_DIR/ronbelisle_premium.sql.gz"
zgrep -m1 'CREATE TABLE.*calcforadvisors_subscribers' "$CFA_BACKUP_DIR/ronbelisle_premium.sql.gz"
zgrep -m1 'CREATE TABLE.*scenarios' "$CFA_BACKUP_DIR/ronbelisle_premium.sql.gz"
```

Expected: nonempty dump, gzip integrity succeeds, SHA-256 reports `OK`, and all
three content markers are found. STOP otherwise. Retain the option file only
through migration/verification, then remove it.

## Immediate preflight

```bash
php /root/cfa-phase2-package/calcforadvisors-phase2-migration.php --preflight
```

Expected: `PREFLIGHT PASS`. It verifies the database, exact 16-row aggregate,
IDs 1–16, missing scenario table, absent Phase 2 foundation, exact legacy slugs,
hashed Stripe-identity continuity for IDs 1–4/13, and engine/key compatibility.
STOP on any other output. The independent SQL preflight may also be run with a
fixed read-only runner; every statement is SELECT-only.

## Stage A — scenarios table

```bash
php /root/cfa-phase2-package/calcforadvisors-phase2-migration.php --stage=A
```

Expected: `A COMPLETE`. Verify with:

```sql
SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_scenarios';
SELECT CONSTRAINT_NAME,DELETE_RULE,REFERENCED_TABLE_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='calcforadvisors_scenarios';
```

Expected InnoDB/utf8mb4 and one CASCADE FK to `calcforadvisors_subscribers`.
STOP on any mismatch; DDL auto-committed, so restore the full backup.

## Stage B — nullable foundation

```bash
php /root/cfa-phase2-package/calcforadvisors-phase2-migration.php --stage=B
```

Expected: `B COMPLETE`. Verify exactly 11 expected columns and these indexes:
`uk_portal_slug`, `idx_cfa_stripe_status`, `idx_cfa_access_ends`. STOP on a
partial definition; DDL auto-committed, so restore the full backup.

## Stage C — guarded legacy trials

```bash
php /root/cfa-phase2-package/calcforadvisors-phase2-migration.php --stage=C
```

Expected: `C COMPLETE` and exactly 11 rows affected. Read-only verification:

```sql
SELECT COUNT(*) FROM calcforadvisors_subscribers WHERE plan='free' AND trial_used_at=created_at AND trial_ends_at=DATE_ADD(created_at,INTERVAL 30 DAY) AND trial_ends_at<UTC_TIMESTAMP();
```

Expected `11`. STOP otherwise. The stage transaction rolls back on a row-count
mismatch; after any committed discrepancy, use the full backup.

## Stage D — evidence reconciliation

```bash
php /root/cfa-phase2-package/calcforadvisors-phase2-migration.php --stage=D
```

Expected: `D COMPLETE`. Verify IDs 1–4 are canceled with past access end and no
trial end; ID 13 is `status=inactive`, normalized status `unresolved`, all
unsupported normalized dates NULL, and all five have `trial_used_at=created_at`.
STOP otherwise. Never improvise or modify their Stripe identifiers.

## Stage E — portal slugs

```bash
php /root/cfa-phase2-package/calcforadvisors-phase2-migration.php --stage=E
```

Expected: `E COMPLETE`. Verify 16 populated, 16 distinct, valid nonreserved
slugs; IDs 10 and 12 retain their legacy slugs. STOP otherwise.

## Final database verification

```bash
php /root/cfa-phase2-package/calcforadvisors-phase2-migration.php --verify
```

Expected: `POST-MIGRATION VERIFY PASS`. Also run the independent read-only
`verify_calcforadvisors_phase2_final.sql` and the entitlement test against the
migrated rows. Confirm `scenarios` (consumer) row count and checksum/aggregate
are unchanged from preflight, the new advisor scenario table is empty, and no
non-CalcForAdvisors table changed unexpectedly.

## Disable maintenance and clean credentials

Only after every verification passes:

```bash
rm /run/calcforadvisors-phase2-mysql.cnf
a2disconf calcforadvisors-phase2-maintenance
apache2ctl configtest
systemctl reload apache2
curl -sS -o /dev/null -w '%{http_code}\n' https://calcforadvisors.com/
```

Expected live HTTP response, not 503. Retain the verified backup and package
until application deployment and acceptance checks finish.

## Rollback

Stages C–E use transactions and roll back automatically before commit on failed
row counts. Ordinary reverse SQL is not authoritative after any stage commits,
because A/B DDL auto-commits and later data may depend on the schema. The full
verified database backup is the authoritative rollback from Stage A onward.

With maintenance still enabled, verify the backup checksum again. Restoration
must remove the newly created table too, so restore into a freshly recreated
database rather than importing over the migrated database:

```bash
cd "$CFA_BACKUP_DIR" && sha256sum -c ronbelisle_premium.sql.gz.sha256
mysql --defaults-extra-file=/run/calcforadvisors-phase2-mysql.cnf -e "DROP DATABASE ronbelisle_premium; CREATE DATABASE ronbelisle_premium CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
set -o pipefail
gzip -cd "$CFA_BACKUP_DIR/ronbelisle_premium.sql.gz" | mysql --defaults-extra-file=/run/calcforadvisors-phase2-mysql.cnf
php /root/cfa-phase2-package/calcforadvisors-phase2-migration.php --preflight
```

Expected: the original preflight passes again. STOP and keep maintenance active
if restore or verification fails.

## Application deployment remains separate

After a separately approved, successful database migration, deploy the already
completed Phase 1–3 application commit using immutable release construction,
checksum verification, a new `/var/www/ronbelisle/releases/<release>` directory,
an atomic `/var/www/ronbelisle/current` symlink switch, PHP lint, health/security
checks, and symlink rollback on failure. Do not run `git pull` in `/var/www/html`;
it is a symlink, not a Git checkout. Do not deploy during this package task.
