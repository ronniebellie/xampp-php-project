# Email-dependent account flows

This site intentionally does not use a transactional email provider.

The following email-dependent flows are disabled:

- Consumer password-reset requests at `/auth/forgot-password.php`.
- CalcForAdvisors password setup by email at `/calcforadvisors/request-set-password.php`.
- CalcForAdvisors welcome and administrator notifications from the Stripe webhook.

The CalcForAdvisors Stripe webhook still records and updates subscriber billing
state. It does not attempt to send email. The free-trial registration flow can
still create a password directly in the browser.

If transactional email is reintroduced later, add a provider behind a reviewed
single send boundary, restore the account-flow links and messages, and store its
credentials only in `/etc/ronbelisle/config.php` or an equivalent secret store.
