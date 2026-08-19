#!/usr/bin/env bash

# Start a local Calculator Premium checkout using Stripe sandbox credentials.
# Credentials are read interactively and are not written to disk.
set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"
php_bin="${PHP_BIN:-$(command -v php || true)}"
if [[ -z "$php_bin" && -x /Applications/XAMPP/bin/php ]]; then
    php_bin="/Applications/XAMPP/bin/php"
fi
if [[ -z "$php_bin" ]]; then
    echo "ERROR: PHP was not found. Set PHP_BIN to your PHP executable." >&2
    exit 1
fi

read -r -p "Stripe test publishable key (pk_test_...): " test_public_key
read -r -s -p "Stripe test secret key (sk_test_...): " test_secret_key
echo

if [[ "$test_public_key" != pk_test_* || "$test_secret_key" != sk_test_* ]]; then
    echo "ERROR: expected Stripe test-mode keys (pk_test_... and sk_test_...)." >&2
    exit 1
fi

export RB_STRIPE_MODE=test
export RB_STRIPE_TEST_PUBLIC_KEY="$test_public_key"
export RB_STRIPE_TEST_SECRET_KEY="$test_secret_key"
export RB_STRIPE_TEST_PRICE_MONTHLY="price_1U69L5HLmh7rIjELDY8KSPH2"
export RB_STRIPE_TEST_PRICE_ANNUAL="price_1U69MWHLmh7rIjELTwlqHtzT"

echo "Starting local Stripe test checkout at http://127.0.0.1:8081/"
echo "Press Ctrl-C to stop."
exec "$php_bin" -S 127.0.0.1:8081 -t "$repo_root"
