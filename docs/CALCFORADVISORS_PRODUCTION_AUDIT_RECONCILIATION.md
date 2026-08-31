# CalcForAdvisors production audit reconciliation

This document records repository findings against the read-only production
investigation completed on 2026-08-31. It does not authorize deployment,
migration, production writes, Stripe changes, or Phase 4.

## Missing scenarios table

Commit `35a5d727418f896f76d129c7bec00d9c93a05734` added the advisor bridge,
advisor ownership branches in the three shared scenario APIs, and
`sql/create_calcforadvisors_scenarios_table.sql` on 2026-03-07. That commit is
an ancestor of deployed commit `0f1c9fe99bcd47ccca851fa5f8199ce3cf74cd68`.
The schema file says `Run once`; no application bootstrap or deploy hook runs
it. Production has the feature code but not its required table, so the evidence
supports an incomplete historical database deployment.

For bridged monthly/annual advisors, `get_scenario_owner()` selects the `cfa`
branch. `api/save_scenario.php`, `api/load_scenarios.php`, and
`api/delete_scenario.php` then issue INSERT, SELECT, and DELETE respectively
against the missing table. The prepare/execute path fails. Save may return its
generic shutdown JSON error, while load/delete can terminate without their
intended success payload. Ordinary ronbelisle.com Premium users use the separate
`scenarios` table and are not affected by this missing advisor table.

Thirteen deployed calculator clients call the shared save endpoint: Future
Value, Managed Portfolio vs Vanguard, Plan Success, Required vs Desired,
Retirement Plan Builder, RMD Impact, Roth Conversion, Social Security Claiming,
Early Exit Social Security, Social Security Spending Gap, Social Security
Survivor Impact, Survivor Gap, and Vanguard PAS vs Target Date. Paid advisor UI
exposes Save/Load controls, and the deployed CalcForAdvisors marketing and
account page explicitly promise save scenarios or save/export access. Those
claims overstate paid-advisor functionality while the table is absent.

## Original audit review

`sql/audit_calcforadvisors_phase2.sql` contains **17**, not 16, statements:
four SHOW and thirteen SELECT. Against current production, statements 1-2 and
5-14 succeed. Statements 3-4 (`SHOW` scenario schema/indexes) and 15-17 (three
scenario data checks) fail because `calcforadvisors_scenarios` is absent.

A sequential PHP runner that throws on query errors stops at statement 3. A
MySQL client or multi-query method may stop or continue depending on its error
mode, so later output cannot be assumed complete. The previously drafted runner
also expected 16 statements and would safely stop before executing this
17-statement file.

`sql/audit_calcforadvisors_phase2_v2.sql` replaces direct scenario-table reads
with `information_schema` queries that return zero rows when the table is
absent. Its subscriber checks return aggregates rather than emails, subscriber
IDs, URLs, hashes, tokens, or Stripe identifier values. A targeted, separately
approved read-only follow-up can identify individual records only when an
aggregate proves remediation is necessary.

## Exact production execution model

Use a fixed CLI-only PHP runner, not the mysql CLI. The application already
loads credentials from `/etc/ronbelisle/config.php`; PHP can reuse that config
without placing a password in process arguments, environment commands, or
shell history. The runner must:

1. Be created as `/tmp/cfa-production-audit.php` after `umask 077`.
2. Hard-code `/tmp/audit_calcforadvisors_phase2_v2.sql` as its only SQL input.
3. Strip full-line comments, require exactly 19 statements, and reject any
   statement whose first keyword is not `SELECT` or `SHOW` before connecting.
4. Load `/var/www/html/includes/db_config.php` and verify `DATABASE()` equals
   the configured `ronbelisle_premium` database.
5. execute `START TRANSACTION READ ONLY`, run each statement sequentially,
   write `/tmp/calcforadvisors-production-audit-20260831.txt` with mode `0600`,
   and finish with `ROLLBACK`.
6. Stop and remove a partial output if any statement fails.

Copy only the approved SQL from the current local repository:

```bash
shasum -a 256 sql/audit_calcforadvisors_phase2_v2.sql
scp sql/audit_calcforadvisors_phase2_v2.sql SERVER:/tmp/audit_calcforadvisors_phase2_v2.sql
```

On production, verify and protect it before use:

```bash
umask 077
chmod 600 /tmp/audit_calcforadvisors_phase2_v2.sql
sha256sum /tmp/audit_calcforadvisors_phase2_v2.sql
grep -nEi '^[[:space:]]*(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|REPLACE|TRUNCATE|GRANT|REVOKE|CALL|LOAD|LOCK|UNLOCK)[[:space:]]' /tmp/audit_calcforadvisors_phase2_v2.sql
```

The grep command must produce no output. Compare the SHA-256 value with a local
`sha256sum`/`shasum -a 256` result. Use the already approved CLI runner pattern,
updated so `$auditFile` is the fixed
`/tmp/audit_calcforadvisors_phase2_v2.sql` path and the statement guard is
`count($statements) !== 19`. Its preflight must require
`calcforadvisors_subscribers` but must not require
`calcforadvisors_scenarios`; v2 audits that table through `information_schema`.
The fixed runner remains
`/tmp/cfa-production-audit.php`; it accepts `--verify` or `--run OUTPUT_FILE`
only and never accepts an SQL path or SQL text from its arguments.

From production, verify first and stop on any mismatch:

```bash
cd /var/www/html
php /tmp/cfa-production-audit.php --verify
```

The runner must report 19 SELECT/SHOW statements, configured and connected
database `ronbelisle_premium`, and both subscriber-table discovery and scenario
metadata discovery without requiring the scenario table to exist. Then run:

```bash
test ! -e /tmp/calcforadvisors-production-audit-20260831.txt
php /tmp/cfa-production-audit.php --run /tmp/calcforadvisors-production-audit-20260831.txt
chmod 600 /tmp/calcforadvisors-production-audit-20260831.txt
less /tmp/calcforadvisors-production-audit-20260831.txt
```

Keep the original and any redacted output in `/tmp`, never in `/var/www/html`,
`/var/www/ronbelisle/current`, or a release directory. After approved review:

```bash
rm /tmp/cfa-production-audit.php
rm /tmp/audit_calcforadvisors_phase2_v2.sql
rm /tmp/calcforadvisors-production-audit-20260831.txt
rm /tmp/calcforadvisors-production-audit-20260831-redacted.txt
```

Only remove files that were created by this audit and only after their review
or transfer is complete.

## Deployment risks

Authoritative production inspection found:

- `/var/www/html -> /var/www/ronbelisle/current`;
- `current -> /var/www/ronbelisle/releases/20260827T014539Z-0f1c9fe99bcd`;
- release artifacts intentionally omit `sql/`; and
- the operational deployment has apparently been Mac rsync/manual SSH.

Do not use the operationally identified stale deploy script that assumes the
web root is a Git checkout: `/var/www/html` is a symlink to an immutable release
and has no deployable Git working tree. The repository's current `deploy.sh`
describes a release/symlink workflow, which conflicts with the reported active
manual process. That provenance and server prerequisites must be reconciled and
tested before either path is trusted.

Before deploying Phases 1-3, one reviewed tool must become authoritative. It
must build from a named commit, exclude Git/dev/docs/sql/secrets, verify a
checksum, create a new versioned release, lint it, switch `current` atomically,
run both-site health/security checks, and roll back the symlink on failure. It
must not mutate the active release in place or assume `/var/www/html` is Git.

AppleDouble `._*` files are primarily deployment hygiene but can leak macOS
metadata, create unexpected servable files, and confuse integrity or cleanup
checks. Artifact construction should disable/exclude them and fail inspection
if any remain. They are not evidence of application execution by themselves.

The application database account being MySQL `root` belongs on a separate
least-privilege hardening list. Changing it during this migration project would
combine credential/privilege risk with schema and product changes and should
not be bundled into the Phase 2 migration or Phase 4.
