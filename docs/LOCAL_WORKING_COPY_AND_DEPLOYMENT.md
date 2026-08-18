# Local working copy and deployment notes

## Working locations

- Original XAMPP document root: `/Applications/XAMPP/xamppfiles/htdocs`
- Safe working copy: `/Users/ron1/Documents/Codex/ronbelisle-site-working-copy-20260817`
- Git remote: `https://github.com/ronniebellie/xampp-php-project.git`
- Working branch: `codex/homepage-conversion`

The XAMPP repository was returned to a clean Git state after the working copy was verified. The homepage conversion change is preserved only in the safe working copy.

## What the original `deploy.sh` did

`deploy.sh` does not upload files from the Mac. It opens an SSH connection to the production server and deploys whatever is already on GitHub's `main` branch:

1. Connect to the production server as `root`.
2. Change to `/var/www/html`, the production Apache document root.
3. Move a directory named `ronbelisle-com` aside if it exists.
4. Fetch the Git remote.
5. Run `git reset --hard origin/main`.
6. Initialize or update Git submodules.
7. Run `composer install --no-dev` when `composer.json` exists.
8. Synchronize `/var/www/html/calcforadvisors/` into `/var/www/calcforadvisors/` with `rsync --delete`.
9. Fetch three live URLs and check them for expected text markers.

Therefore, editing the local XAMPP or Codex working copy is not enough to publish a change. A change must be committed and pushed to GitHub `main` before this script can deploy it.

That original script has now been replaced in the working copy by a staged,
artifact-based deployment. See `docs/SECURE_DEPLOYMENT.md`. The replacement has
not been run against production, and production still uses the legacy layout
until a separately reviewed first-time migration is authorized.

## Safety observations

- `git reset --hard origin/main` intentionally removes tracked edits made directly on the production server.
- The script has no full-site backup or automatic rollback before changing production.
- `rsync --delete` removes files from the separate CalcForAdvisors web root when they are absent from the source directory.
- The script connects as the server's `root` account rather than a limited deployment account.
- Verification covers the homepage, one calculator, and CalcForAdvisors; it does not exercise authentication, checkout, APIs, or most calculators.
- Database configuration files are Git-tracked. They and other tracked configuration files need a separate secrets review before the repository is shared or its visibility changes.
- The root `.htaccess` does not currently block direct web access to development, SQL, documentation, or backup paths. Server-level configuration may provide separate protection, but that is not established by this repository.

## Recommended deployment workflow

1. Make changes only in the safe working copy on a feature branch.
2. Run PHP syntax checks and local functional tests.
3. Review the Git diff and confirm no credentials, local files, or test artifacts are included.
4. Commit the reviewed change.
5. Push the branch and merge it into `main` after review.
6. Complete the first-time secure release-layout migration described in
   `docs/SECURE_DEPLOYMENT.md`.
7. Run `deploy.sh` only when `main` contains the intended production version.
8. Verify the homepage, affected calculators, login, Premium behavior, protected
   paths, and server logs.

Do not run `deploy.sh` merely to test an uncommitted local change; it will ignore that change and redeploy GitHub `main`.
