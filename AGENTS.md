# Repository guide

This repository deploys three related production PHP applications. Preserve their current product behavior and make narrow, tested changes.

## Production applications

- `https://ronbelisle.com/`: root PHP application and calculators. Shared backend code is in `includes/`; JSON and export endpoints are in `api/`; authentication is in `auth/`.
- `https://journey.ronbelisle.com/`: guided retirement-planning frontend in `journey.ronbelisle.com/`. It uses root account, entitlement, feedback, and Journey plan APIs.
- `https://calcforadvisors.com/`: advisor product in `calcforadvisors/`, served from a separate document root synchronized from that directory.

`users` is the consumer account source of truth. `calcforadvisors_subscribers` is the advisor account source of truth. Scenario ownership must always be constrained by the authenticated user/subscriber id. See `sql/migrations/` for the current schema history; never infer production schema changes from old top-level SQL files.

## Security conventions

- Start sessions only through `includes/session_bootstrap.php` or `calcforadvisors/includes/session_bootstrap.php`.
- Regenerate the session id after authentication. Use host-only, Secure, HttpOnly, SameSite=Lax cookies.
- Use prepared statements for every value derived from a request or session.
- Validate CSRF tokens on every authenticated state-changing request. Use `includes/csrf.php` or `calcforadvisors/includes/csrf.php`.
- Authorize admin routes through `includes/admin_auth.php`; never rely on a hidden URL.
- Escape output for its context with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- Do not display exception, database, path, or configuration details to public users. Log server-side details when needed.
- Secrets belong in `/etc/ronbelisle/config.php` or environment variables loaded by `includes/config_bootstrap.php`. Do not commit credentials.
- Keep repository metadata, `dev/`, `docs/`, `sql/`, config, backups, and Composer internals outside release artifacts and blocked by the relevant `.htaccess` defense-in-depth rules.

## Verification

Run before committing:

```bash
find . -path './vendor' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
for test in dev/test-*.php; do php "$test"; done
node roth-conv/engine.test.js
node dev/test-managed-vs-vanguard-projection.js
```

Some Journey tests require the local XAMPP MySQL service and writable XAMPP session directory; note that dependency rather than weakening the test. After deployment, verify the three homepages, auth redirects, changed workflows, security headers, robots files, and protected paths without creating or changing production records.

## Deployment

Read `docs/LOCAL_WORKING_COPY_AND_DEPLOYMENT.md` and `docs/SECURE_DEPLOYMENT.md` before deploying. `deploy.sh` builds a tracked-file release artifact, requires a clean `main`, publishes through the configured SSH target, switches the release symlink, runs smoke checks, and rolls back on failure. Do not run it unless the server has completed the documented release-layout migration. CalcForAdvisors is copied to its separate document root as part of the established server workflow.

Preserve unrelated working-tree changes. In particular, do not stage or modify user-authored untracked files unless they are in scope.
