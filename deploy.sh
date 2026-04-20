#!/usr/bin/env bash
# Deploy to production: update the canonical Apache docroot (/var/www/html), then sync calcforadvisors.
# Run from your Mac (e.g. in Cursor): ./deploy.sh   or   bash deploy.sh

set -e
SERVER="root@64.23.181.64"

ssh "$SERVER" 'set -euo pipefail
# Canonical production web root for ronbelisle.com (Apache DocumentRoot)
APP_DIR="/var/www/html"

cd "$APP_DIR"

# Keep any local/untracked experiments out of the way during deploy
if [ -d ronbelisle-com ]; then
  ts="$(date -u +%Y%m%dT%H%M%SZ)"
  mv ronbelisle-com "ronbelisle-com.predeploy.$ts" || true
fi

git fetch origin
# Fast-forward tracked files to match GitHub main (server-only secrets remain via .gitignore)
git reset --hard origin/main
git submodule update --init --recursive

if [ -f composer.json ]; then
  composer install --no-dev --no-interaction --prefer-dist
fi

# White-label calcforadvisors bundle lives alongside the main repo in this project layout
mkdir -p /var/www/calcforadvisors
rsync -av --delete --exclude=.git --exclude="*.swp" "$APP_DIR/calcforadvisors/" /var/www/calcforadvisors/

# Optional: keep the legacy checkout in sync too (not the Apache docroot, but avoids drift)
if [ -d /var/www/xampp-php-project/.git ]; then
  cd /var/www/xampp-php-project
  git fetch origin
  git reset --hard origin/main
  git submodule update --init --recursive
  if [ -f composer.json ]; then
    composer install --no-dev --no-interaction --prefer-dist
  fi
fi
'
echo "Deploy done. Check https://ronbelisle.com and https://calcforadvisors.com"
