#!/usr/bin/env bash
# Deploy to production: update the canonical Apache docroot (/var/www/html), then sync calcforadvisors.
# Run from your Mac (e.g. in Cursor): ./deploy.sh   or   bash deploy.sh

set -e
SERVER="root@64.23.181.64"

ssh "$SERVER" 'set -euo pipefail
# Canonical production web root for ronbelisle.com (Apache DocumentRoot)
APP_DIR="/var/www/html"

verify_url () {
  local url="$1"
  local must_contain="${2:-}"

  # 60-second budget per deploy verification: 3 quick attempts.
  for i in 1 2 3; do
    if html="$(curl -fsSL --max-time 10 "$url")"; then
      if [ -z "$must_contain" ] || echo "$html" | grep -q "$must_contain"; then
        echo "OK: $url"
        return 0
      fi
    fi
    sleep 1
  done

  echo "ERROR: verification failed for $url" >&2
  if [ -n "$must_contain" ]; then
    echo "ERROR: expected to find marker: $must_contain" >&2
  fi
  return 1
}

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

echo "--- 60-second deploy verification ---"
verify_url "https://ronbelisle.com/" "tier-title"
verify_url "https://ronbelisle.com/" "Social Security Claiming Analyzer"
verify_url "https://ronbelisle.com/social-security-claiming-analyzer/" "Social Security Claiming Analyzer"
verify_url "https://calcforadvisors.com/" "calcforadvisors"
'
echo "Deploy done. Check https://ronbelisle.com and https://calcforadvisors.com"
