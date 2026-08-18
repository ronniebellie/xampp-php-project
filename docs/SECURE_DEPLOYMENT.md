# Secure release deployment

The production site must no longer be a Git checkout inside `/var/www/html`.
The replacement deployment builds a clean artifact locally, stages it in a
versioned private release directory, atomically changes one symlink, verifies
the live site, and automatically restores the previous symlink if verification
fails.

Nothing in this document has been run against production. The first migration
must be reviewed and explicitly authorized because it moves the current web
directories, changes Apache's filesystem layout, and may require a brief
maintenance window.

## 1. Preserve evidence before containment

Before changing production, copy the current Apache access and error logs to a
restricted directory. Search for requests to:

- `/.git/`
- `/dev/`
- `/sql/`
- `/docs/`
- `/includes/`
- files ending in `.backup`, `.bak`, `.swp`, `.sql`, or `.md`

Do not put copied logs in a web-accessible directory. If the logs show requests
to a mutating test script or repository-object download, pause ordinary
deployment and perform incident-response review.

## 2. Review and install the Apache boundary

Use `server/apache/ronbelisle-security.conf.example` as the starting point for
each relevant TLS virtual host. Confirm that `mod_rewrite` and `mod_headers` are
enabled and that Apache permits the root `.htaccess` rules (`AllowOverride
FileInfo`) if `.htaccess` is retained as defense in depth.

Validate before reload:

```bash
apachectl configtest
```

The server-level configuration is the primary boundary. The repository's
`.htaccess` duplicates the critical restrictions so an accidentally included
artifact is still denied when overrides are enabled.

## 3. Create a least-privilege deployment account

Create a dedicated account rather than deploying as `root`. It needs write
access only to `/var/www/ronbelisle/incoming`, `/var/www/ronbelisle/releases`,
and the `/var/www/ronbelisle/current` symlink. It does not need permission to
read `/etc/ronbelisle/config.php`; the Apache/PHP runtime account needs that
access.

The intended server structure is:

```text
/var/www/ronbelisle/
  current -> /var/www/ronbelisle/releases/<release-id>
  incoming/
  releases/
  legacy/               # private copy of the pre-migration web roots
/var/www/html -> /var/www/ronbelisle/current
/var/www/calcforadvisors -> /var/www/ronbelisle/current/calcforadvisors
/etc/ronbelisle/config.php
```

Confirm that Apache follows these symlinks and that the external configuration
file remains outside every release and web root.

## 4. Build and inspect the first release

The working tree must be committed. `composer.lock` must be tracked so the same
dependency versions are installed every time.

```bash
scripts/build-release.sh \
  --ref HEAD \
  --output /private/tmp/ronbelisle-initial.tar.gz
```

The builder:

- starts from `git archive`, so untracked files and `.git` are absent;
- excludes development, SQL, documentation, backup, editor, and local-config
  artifacts;
- installs production dependencies from `composer.lock`;
- lints every PHP file;
- fails if a forbidden path remains; and
- writes a SHA-256 checksum beside the archive.

Inspect the archive before uploading it:

```bash
tar -tzf /private/tmp/ronbelisle-initial.tar.gz | less
```

## 5. First-time server migration

This is a planned, recoverable production operation—not an ordinary deploy.
Resolve and record the exact existing paths first. Create the base directories,
upload the reviewed initial archive, verify its checksum, and extract it into a
new versioned directory under `/var/www/ronbelisle/releases`.

Then, during the approved migration window:

1. Put the site in maintenance mode or stop writes.
2. Move the existing `/var/www/html` directory to a timestamped path under
   `/var/www/ronbelisle/legacy/`.
3. Move the existing `/var/www/calcforadvisors` directory there as well if it is
   a separate real directory.
4. Create `current` pointing to the inspected release.
5. Create `/var/www/html` pointing to `current`.
6. Create `/var/www/calcforadvisors` pointing to `current/calcforadvisors`.
7. Reload Apache only if its configuration changed.
8. Run the verification list below before ending maintenance mode.

Do not delete the legacy directories during this migration. They are the
first rollback path and contain the old Git checkout, so they must remain
outside the public document root with restrictive permissions.

## 6. Verification

Confirm the homepage, calculators, authentication, Journey, and
calcforadvisors. The following known exposures must return 403 or 404:

```text
https://ronbelisle.com/.git/HEAD
https://ronbelisle.com/dev/journey-premium/README.md
https://ronbelisle.com/sql/create_scenarios_table.sql
https://ronbelisle.com/docs/PROJECT_OVERVIEW.md
https://ronbelisle.com/index.php.backup
https://ronbelisle.com/.premium.html.swp
https://ronbelisle.com/includes/config_bootstrap.php
```

Also verify that HTTPS responses include the expected cookie attributes and
security headers. Session-cookie remediation is a separate follow-up patch;
the deployment must not be considered fully hardened until that work passes.

## 7. Ordinary deployments after migration

After changes are reviewed, committed, merged to `main`, and the working tree
is clean:

```bash
export RONBELISLE_DEPLOY_TARGET='deploy-user@server'
./deploy.sh
```

The script refuses a dirty working tree, refuses non-`main` branches by
default, verifies the archive checksum, stages rather than editing the live
tree, changes the `current` symlink atomically, and rolls the symlink back when
health or exposure checks fail. Previous releases are retained; removal is a
separate deliberate maintenance operation.

## 8. Secrets and history

Before resuming feature deployment, scan the entire Git history with a secret
scanner. Rotate every active secret found in current or historical files.
Deleting a secret in a new commit is not sufficient because the prior Git
objects may already have been downloaded from the public `.git` directory.
