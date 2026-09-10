# Phase 1: credential and privacy containment

Scope: password-token redemption, analytics URL privacy, authentication bridge rotation/redirects, and scenario mutation CSRF/request validation. No calculator formulas, billing reconciliation, deployment configuration, or production records are changed.

## Password links

The advisor and consumer redemption endpoints accept only version-2 tokens bound to the current password hash and a product-specific purpose. Expiration is exclusive: a token is invalid at its expiry timestamp. Every successful password change invalidates outstanding links, including links invalidated by an administrator setting a password.

Redemption uses an atomic, byte-exact, NULL-safe conditional update against the password hash read before verification. Two concurrent redemptions of the same password state cannot both succeed. No token table or schema migration is needed.

Legacy links are intentionally invalidated. Email issuance remains disabled; existing administrator provisioning stays available. Any out-of-repository issuer must be updated before issuing more links:

- Consumer: `rb_password_reset_create_token($email, $currentPasswordHash, $ttlSeconds)`.
- Advisor: `rb_password_token_create($email, $currentPasswordHash, 'advisor-password', CALCFORADVISORS_AUTH_SECRET, $ttlSeconds)`.
- Read current password state server-side. Never accept it from the requester, expose it in a link, or log tokens.

Existing configured signing secrets are retained. Password forms have session CSRF protection and no analytics. The endpoints send no-store, no-referrer, and noindex headers.

## Analytics

One shared local implementation initializes each site's existing measurement ID. It loads no third-party analytics and queues no events when the initial page URL or referrer has any query string or fragment, or when the page is a credential/setup endpoint. It also rejects custom events if the URL later becomes parameterized. Clean-page custom events override page_location and page_referrer with origin/path values.

This deliberately sacrifices analytics for parameterized visits, including UTM-tagged visits, shared financial scenarios, and success callbacks. It avoids relying solely on pageview redaction while automatic GA4 collection can still read the original URL. Calculator prefills and sharing continue to work; the browser URL is not rewritten.

Before production release, verify GA4 network requests/DebugView on clean and sensitive URLs and inspect property-level Enhanced Measurement settings. The local tests exercise the gate and queued arguments, not Google's remote implementation or previously retained analytics data. Historical analytics, browser history, and server log retention are not altered by this phase.

The PHP includes read the shared script from `calcforadvisors/assets/js/analytics-privacy.js` in the release tree; static advisor pages load it from the advisor document root. Ensure both copies supplied by the established deployment workflow are present when eventually releasing.

## Bridge and scenarios

Bridge authentication regenerates the session ID, deletes the prior session, rotates the root CSRF token, and preserves other session state. Destinations are restricted to plain local paths; absolute/protocol-relative URLs, query strings, percent escapes, backslashes, dot segments, and control characters fall back to `/rmd-impact/`. Cross-host logout and entitlement revocation remain outside Phase 1.

Scenario save/delete requests must be POST JSON with the current session's X-CSRF-Token. The shared calculator footer supplies the token and explicit request wrapper. The wrapper never sends the token to another origin and does not follow redirects. APIs reject invalid request types, tokens, malformed JSON, invalid fields, and bodies above 1 MiB before database access. Existing authenticated ownership constraints are unchanged.

Already-open calculator tabs must reload after release or a bridge authentication/CSRF rotation. Names are limited to 100 Unicode characters for consumer scenarios and 255 for advisor scenarios. Calculator types are limited to 50/64 characters respectively. Both tables have a 65,535-byte TEXT limit, checked against the encoded JSON, including escaping and structure. Oversized input receives an explicit error before insertion rather than truncation or a database failure.

## Verification

Run with available PHP/Node executables:

- `php dev/test-phase1-containment.php`
- `node dev/test-phase1-browser-security.js`
- `PHP_BIN=/path/to/php python3 dev/test-phase1-http.py`
- `php dev/journey-premium/test-journey-analytics.php`
- Existing `dev/test-*.php`, Roth engine, and managed portfolio regression suites.
- PHP syntax checks and `git diff --check`.

The PHP unit tests use isolated sessions and a stateful password-store double for concurrent redemption. HTTP tests bind only localhost, use temporary copies of real API guards, and deliberately forbid application database access. An approved staging pass should exercise actual MySQL redemption, advisor/consumer save-delete workflows, and browser headers before deployment. No production testing that changes records is required or authorized here.

## Changed files

- `api/delete_scenario.php`
- `api/save_scenario.php`
- `auth/reset-password.php`
- `calcforadvisors-bridge.php`
- `calcforadvisors/assets/js/analytics-privacy.js`
- `calcforadvisors/demos/coastal-wealth.html`
- `calcforadvisors/demos/northgate.html`
- `calcforadvisors/demos/riverfront.html`
- `calcforadvisors/includes/analytics.php`
- `calcforadvisors/index.html`
- `calcforadvisors/set-password.php`
- `dev/journey-premium/test-journey-analytics.php`
- `dev/test-phase1-browser-security.js`
- `dev/test-phase1-containment.php`
- `dev/test-phase1-http.py`
- `docs/PHASE1_CREDENTIAL_PRIVACY_CONTAINMENT.md`
- `future-value-app/calculator.js`
- `includes/analytics.php`
- `includes/bridge_security.php`
- `includes/calculator-footer.php`
- `includes/password_reset.php`
- `includes/password_tokens.php`
- `includes/scenario_request.php`
- `journey.ronbelisle.com/includes/analytics.php`
- `js/scenario-api.js`
- `js/share-results.js`
- `managed-vs-vanguard/calculator.js`
- `plan-success/calculator.js`
- `required-vs-desired/index.php`
- `retirement-plan/calculator.js`
- `rmd-impact/calculator.js`
- `roth-conv/calculator.js`
- `social-security-claiming-analyzer/calculator.js`
- `ss-early-exit/calculator.js`
- `ss-gap/calculator.js`
- `ss-survivor-impact/calculator.js`
- `survivor-gap/calculator.js`
- `vanguard-pas-vs-target-date/calculator.js`

## Pre-deployment verification correction

Read-only production schema inspection confirmed that consumer scenarios use VARCHAR(100) names, VARCHAR(50) calculator types, and TEXT data; advisor scenarios use VARCHAR(255), VARCHAR(64), and TEXT. The initial Phase 1 guard was wider than the consumer columns and both TEXT limits. The follow-up correction applies owner-specific storage validation after authentication, before insertion. It changes no schema or financial calculations. Unicode names are counted in characters; JSON storage is counted in encoded bytes.
