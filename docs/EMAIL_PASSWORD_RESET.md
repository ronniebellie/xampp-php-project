# Email delivery for password reset (production)

## Current failure (observed 2026-07-29, still true until credits restored)

Forgot Password fails with a customer-facing message:

> We’re unable to send password-reset email right now. Please try again later or contact support.

Server logs retain the diagnostic code (for example `credits_exceeded`) and the SendGrid response snippet.

**Root cause:** SendGrid HTTP API rejects the configured API key with:

```text
HTTP 401 — Maximum credits exceeded
```

Production probe of `GET https://api.sendgrid.com/v3/user/credits` returned:

- `remain`: 0
- `total`: 0
- `is_hard_limit`: true

Password-reset application code (token creation + SendGrid call) is working. Delivery fails until SendGrid has available credits / a working key.

## Required production configuration

Secrets live in `/etc/ronbelisle/config.php` (not git), under the `email` key.  
`includes/email_config.php` loads them.

```php
'email' => [
    'smtp_pass'  => 'SG.xxxx',                 // SendGrid API key with Mail Send permission
    'from_email' => 'noreply@ronbelisle.com',  // must be a verified sender/domain in SendGrid
    'from_name'  => 'Ron Belisle',
    // Optional: also used as an administrator allowlist email for /admin/.
    'admin_email' => 'you@example.com',
],
```

Journey Premium trial administrator notices no longer send email. Confirmed trials appear in
the private admin dashboard at `/admin/journey-premium-trials.php`.

Also ensure password-reset HMAC secret is set (used by `includes/password_reset.php`):

```php
'auth' => [
    'password_reset_secret' => 'long-random-secret-at-least-32-chars',
],
```

If `auth.password_reset_secret` is empty, the code may fall back to `CALCFORADVISORS_AUTH_SECRET`. Prefer an explicit `password_reset_secret`.

## Restore delivery checklist

1. In SendGrid: upgrade / add credits, or create a new API key on an account that can send.
2. Verify a sender identity for `ronbelisle.com` (or the chosen `from_email`).
3. Update `/etc/ronbelisle/config.php` `email.smtp_pass` / `from_email` / `from_name`.
4. Confirm:

```bash
php -r '
$c=require "/var/www/html/includes/email_config.php";
$ch=curl_init("https://api.sendgrid.com/v3/user/credits");
curl_setopt_array($ch,[
  CURLOPT_HTTPHEADER=>["Authorization: Bearer ".$c["smtp_pass"]],
  CURLOPT_RETURNTRANSFER=>true
]);
echo curl_exec($ch), "\n";
'
```

5. Submit Forgot Password for a real account and confirm inbox delivery.

## Notes

- `send_email_smtp()` uses the **SendGrid HTTP API** (`api.sendgrid.com`), not SMTP port 587.
- Shared key currently sends as `noreply@calcforadvisors.com` — prefer a ronbelisle.com verified sender for consumer auth mail.
- Do not commit API keys to git.
