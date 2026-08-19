# Stripe test checkout

Stripe test mode is opt-in. Production continues to use the live values from
`/etc/ronbelisle/config.php` unless `RB_STRIPE_MODE=test` is explicitly set.

For a local test environment, set these variables without committing them:

```text
RB_STRIPE_MODE=test
RB_STRIPE_TEST_PUBLIC_KEY=pk_test_...
RB_STRIPE_TEST_SECRET_KEY=sk_test_...
RB_STRIPE_TEST_PRICE_MONTHLY=price_1U69L5HLmh7rIjELDY8KSPH2
RB_STRIPE_TEST_PRICE_ANNUAL=price_1U69MWHLmh7rIjELTwlqHtzT
```

The repository also includes `scripts/run-stripe-test.sh`, which prompts for
the two test keys without saving them and starts a local server on
`http://127.0.0.1:8081/`.

The test prices belong to the sandbox product `Calculator Premium (Test)` and
the annual sandbox product `Calculator Premium Annual (Test)`. Use Stripe's
documented test payment methods (for example, card `4242 4242 4242 4242`) only
in test mode. Never place test keys in the production configuration.
