#!/usr/bin/env bash

# Stage and atomically publish a clean ronbelisle.com release.
#
# Required environment:
#   RONBELISLE_DEPLOY_TARGET=deploy-user@server
#
# The server must first be migrated to the release/symlink layout documented in
# docs/SECURE_DEPLOYMENT.md. This script intentionally refuses to modify a
# legacy in-place /var/www/html directory.

set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

deploy_target="${RONBELISLE_DEPLOY_TARGET:-}"
if [ -z "$deploy_target" ]; then
  echo "ERROR: set RONBELISLE_DEPLOY_TARGET (for example, deploy@server)" >&2
  exit 2
fi

if [ -n "$(git status --porcelain)" ]; then
  echo "ERROR: the working tree must be clean; commit and review changes first" >&2
  exit 1
fi

branch="$(git branch --show-current)"
if [ "$branch" != "main" ] && [ "${ALLOW_NON_MAIN_DEPLOY:-0}" != "1" ]; then
  echo "ERROR: deployments must run from main (current branch: $branch)" >&2
  exit 1
fi

commit="$(git rev-parse HEAD)"
release_id="$(date -u +%Y%m%dT%H%M%SZ)-${commit:0:12}"
archive_name="ronbelisle-${release_id}.tar.gz"
local_stage="$(mktemp -d "${TMPDIR:-/tmp}/ronbelisle-deploy.XXXXXX")"
trap 'rm -rf "$local_stage"' EXIT
archive="$local_stage/$archive_name"

"$repo_root/scripts/build-release.sh" --ref "$commit" --output "$archive"

remote_incoming="/var/www/ronbelisle/incoming/$archive_name"
ssh "$deploy_target" 'test -L /var/www/html && test -L /var/www/calcforadvisors && test -L /var/www/ronbelisle/current && mkdir -p /var/www/ronbelisle/incoming /var/www/ronbelisle/releases'
scp "$archive" "$deploy_target:$remote_incoming"
scp "$archive.sha256" "$deploy_target:$remote_incoming.sha256"

ssh "$deploy_target" bash -s -- "$release_id" "$archive_name" <<'REMOTE'
set -euo pipefail

release_id="$1"
archive_name="$2"
base="/var/www/ronbelisle"
incoming="$base/incoming/$archive_name"
release="$base/releases/$release_id"
current="$base/current"

if [ -e "$release" ]; then
  echo "ERROR: release already exists: $release" >&2
  exit 1
fi

cd "$base/incoming"
if command -v sha256sum >/dev/null 2>&1; then
  sha256sum -c "$archive_name.sha256"
elif command -v shasum >/dev/null 2>&1; then
  shasum -a 256 -c "$archive_name.sha256"
else
  echo "ERROR: no SHA-256 verification command is installed" >&2
  exit 1
fi
mkdir "$release"
tar -xzf "$incoming" -C "$release"
rm "$incoming" "$incoming.sha256"

if find "$release" -type d -name '.git' -print -quit | grep -q .; then
  echo "ERROR: staged release contains Git metadata" >&2
  exit 1
fi

find "$release" -type f -name '*.php' -exec php -l '{}' \; >/dev/null

previous="$(readlink "$current")"
next_link="$base/current.next"
rm -f "$next_link"
ln -s "$release" "$next_link"
mv -Tf "$next_link" "$current"

rollback() {
  rollback_link="$base/current.rollback"
  rm -f "$rollback_link"
  ln -s "$previous" "$rollback_link"
  mv -Tf "$rollback_link" "$current"
  echo "Rolled back to $previous" >&2
}

# The document root is a symlink.  Recycle mod_php workers immediately after
# switching it so their realpath/opcache state cannot mix files from releases.
if ! apachectl configtest; then
  echo "ERROR: Apache configuration validation failed after activation" >&2
  rollback
  exit 1
fi

if ! apachectl graceful; then
  echo "ERROR: Apache graceful reload failed after activation" >&2
  rollback
  apachectl graceful || true
  exit 1
fi

homepage_body="$(mktemp)"
directory_body="$(mktemp)"
calc_body="$(mktemp)"
trap 'rm -f "$homepage_body" "$directory_body" "$calc_body"' EXIT

if ! curl -fsSL --max-time 15 "https://ronbelisle.com/?deploy=$release_id" -o "$homepage_body" \
  || ! grep -q 'Build My Free Retirement Plan' "$homepage_body"; then
  rollback
  exit 1
fi

if ! curl -fsSL --max-time 15 "https://ronbelisle.com/calculators.php?deploy=$release_id" -o "$directory_body" \
  || ! grep -q 'Social Security Claiming Analyzer' "$directory_body"; then
  rollback
  exit 1
fi

if ! curl -fsSL --max-time 15 "https://calcforadvisors.com/?deploy=$release_id" -o "$calc_body" \
  || ! grep -qi 'calcforadvisors' "$calc_body"; then
  rollback
  exit 1
fi

headers="$(curl -fsSI --max-time 15 "https://ronbelisle.com/?deploy=$release_id")"
for required_header in \
  'strict-transport-security:' \
  'x-content-type-options:' \
  'referrer-policy:'
do
  if ! printf '%s\n' "$headers" | grep -qi "^$required_header"; then
    echo "ERROR: required response header is missing: $required_header" >&2
    rollback
    exit 1
  fi
done

for protected_url in \
  'https://ronbelisle.com/.git/HEAD' \
  'https://ronbelisle.com/dev/journey-premium/README.md' \
  'https://ronbelisle.com/sql/create_scenarios_table.sql' \
  'https://ronbelisle.com/docs/PROJECT_OVERVIEW.md' \
  'https://ronbelisle.com/index.php.backup' \
  'https://ronbelisle.com/.premium.html.swp'
do
  separator='?'
  case "$protected_url" in
    *\?*) separator='&' ;;
  esac
  status="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 12 "${protected_url}${separator}security_check=$release_id")"
  if [ "$status" != "403" ] && [ "$status" != "404" ]; then
    echo "ERROR: protected URL is exposed ($status): $protected_url" >&2
    rollback
    exit 1
  fi
done

echo "Published release $release_id"
echo "Previous release retained at $previous"
REMOTE

echo "Deploy complete: $release_id"
