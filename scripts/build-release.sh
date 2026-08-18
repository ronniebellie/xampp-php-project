#!/usr/bin/env bash

# Build a clean, versioned production artifact from a committed Git revision.
# The artifact contains runtime files only and never contains Git metadata.

set -euo pipefail

usage() {
  cat <<'USAGE'
Usage: scripts/build-release.sh --output PATH [--ref GIT_REF]

Builds a gzipped tar archive from GIT_REF (default: HEAD). The revision must
contain composer.lock so dependency versions are reproducible.
USAGE
}

output=""
source_ref="HEAD"

while [ "$#" -gt 0 ]; do
  case "$1" in
    --output)
      output="${2:-}"
      shift 2
      ;;
    --ref)
      source_ref="${2:-}"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "ERROR: unknown argument: $1" >&2
      usage >&2
      exit 2
      ;;
  esac
done

if [ -z "$output" ]; then
  echo "ERROR: --output is required" >&2
  exit 2
fi

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

git rev-parse --verify "${source_ref}^{commit}" >/dev/null

if ! git cat-file -e "${source_ref}:composer.lock" 2>/dev/null; then
  echo "ERROR: composer.lock must be committed before building a release" >&2
  exit 1
fi

if [ -e "$output" ]; then
  echo "ERROR: output already exists: $output" >&2
  exit 1
fi

output_dir="$(dirname "$output")"
mkdir -p "$output_dir"
output_dir="$(cd "$output_dir" && pwd -P)"
output="$output_dir/$(basename "$output")"

build_root="$(mktemp -d "${TMPDIR:-/tmp}/ronbelisle-release.XXXXXX")"
trap 'rm -rf "$build_root"' EXIT
source_tree="$build_root/source"
release_tree="$build_root/release"
mkdir -p "$source_tree" "$release_tree"

git archive --format=tar "$source_ref" | tar -xf - -C "$source_tree"
rsync -a --exclude-from="$repo_root/.deployignore" "$source_tree/" "$release_tree/"

# Composer files are used only while resolving the locked dependencies. They
# are removed from the public artifact after installation.
git show "${source_ref}:composer.json" > "$release_tree/composer.json"
git show "${source_ref}:composer.lock" > "$release_tree/composer.lock"

composer_bin="${COMPOSER_BIN:-composer}"
"$composer_bin" install \
  --working-dir="$release_tree" \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --classmap-authoritative

rm "$release_tree/composer.json" "$release_tree/composer.lock"

# Composer packages can contain their own documentation. It is not needed by
# the application and should not be web-accessible.
find "$release_tree" -type f \( \
  -name '*.md' -o -name '*.sql' -o -name '*.bak' -o -name '*.backup' -o \
  -name '*.old' -o -name '*.orig' -o -name '*.save' -o -name '*.swp' -o \
  -name '*.swo' -o -name '*~' \
\) -delete

php_bin="${PHP_BIN:-php}"
find "$release_tree" -type f -name '*.php' -exec "$php_bin" -l '{}' \; >/dev/null

forbidden="$build_root/forbidden.txt"
: > "$forbidden"

for protected_path in \
  '.git' 'dev' 'docs' 'sql' 'Do-not-upload-to-server' 'server' 'scripts' \
  'journey.ronbelisle.com/dev' 'journey.ronbelisle.com/docs' \
  'db_config.php' 'deploy.sh' 'composer.json' 'composer.lock'
do
  if [ -e "$release_tree/$protected_path" ]; then
    echo "$release_tree/$protected_path" >> "$forbidden"
  fi
done

find "$release_tree" -type d -name '.git' -print >> "$forbidden"
find "$release_tree" -type f \( -name '.env' -o -name '.env.*' \) -print >> "$forbidden"

if [ -s "$forbidden" ]; then
  echo "ERROR: forbidden paths found in release artifact:" >&2
  sed "s#^$release_tree/##" "$forbidden" >&2
  exit 1
fi

tar -czf "$output" -C "$release_tree" .
(
  cd "$(dirname "$output")"
  shasum -a 256 "$(basename "$output")" > "$(basename "$output").sha256"
)

echo "Release artifact: $output"
echo "Checksum: $output.sha256"
