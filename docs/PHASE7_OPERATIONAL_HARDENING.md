# Phase 7 — operational hardening

## Changes and boundaries

- AI Explain: POST/JSON only, 64 KiB body, bounded text/conversation, session CSRF on all 19 calculator integrations and follow-ups; same-origin token/redirect boundary. Release the PHP session lock before network calls. Generic public provider/runtime errors, 5-second connection timeouts (existing overall deadlines retained).
- Per authenticated owner, one in-flight AI request and six admitted attempts per 60 seconds, using a private local filesystem lock/ledger. Locks close on shutdown. Storage failure denies the request. This is a single-server burst limit, not a distributed quota or a monthly total-spend guarantee.
- Optional advisor AI tier reserves its existing monthly usage counter with an atomic conditional UPDATE before the call. Attempts, including provider failures, consume reservations. Missing/unavailable ledger selects the existing baseline-provider fallback; it never creates schema at request time. Existing usage rows are preserved.
- All ten calculator PDF endpoints: 8 MiB JSON maximum, depth/row/node/text bounds, finite numbers, plain-text fields (client HTML removed before aliases/rendering), PNG-only charts capped at 4 MiB encoded/16 megapixels/8192 per dimension. Real tempnam paths have shutdown cleanup, including failure paths. TCPDF errors throw into a generic API handler rather than exiting with public internal diagnostics. Session locks are released before rendering. No financial formulas changed.
- Journey plan JSON reader: bounded 2 MiB stream and JSON depth. Admin signup-review ledger is checked, never created, during requests. An absent ledger fails safely and logs the existing migration prerequisite.
- Shared advisor Premium/scenario ownership now rechecks centralized database entitlement, not cached session plan. Consumer entitlement is unchanged. Public portal lookup includes cancellation/event fields used by the same evaluator. Existing seven-day past-due grace and paid-through cancellation rules are preserved.
- CSP adds `base-uri 'self'; object-src 'none'; frame-ancestors 'self'` to both document roots, consistent with SAMEORIGIN and Phase 6 tab navigation. Script/style restrictions are not guessed.
- Sitemap includes active authoritative-catalog routes once, excludes inactive catalog routes, and no longer invents a daily last-modified date. Calculator JSON-LD uses catalog names/descriptions and script-safe JSON encoding.

## Migration and deployment

No new migration. Read-only production preflight confirmed existing `admin_signup_reviews` and `ai_explain_usage`. SQL fixtures clone definitions into connection-local TEMPORARY tables and exercise the actual entitlement/usage helpers without persistent row writes or Stripe requests. The fixture initially used a provider field name; corrected to the existing application `access_ends_at` column before verification.

Both releases use committed-file artifacts, cached locked Composer dependencies, SHA-256 archive/all-file verification, forbidden-artifact checks, full PHP lint, CLI and Apache-configuration platform checks, SQL-backed synthetic fixtures, atomic activation, Apache configtest/graceful/active checks, and retained previous releases. No HTTP/browser/Stripe verification is performed. Tests and deployment metadata stay in private incoming directories, not public artifacts.

## Regression coverage

- `dev/test-phase7-operations.php`: request status/size/type, JSON/AI/PDF limits, zeros/nonfinite values, image format/dimensions, actual chart-file shutdown cleanup, real concurrent file locks and burst reset/account isolation, safe TCPDF errors, all PDF integrations, catalog sitemap, CSP.
- `dev/test-phase7-client.js`: actual fetch wrapper in a VM, token origin/redirect boundaries and all 19 calculator integrations (no browser/network).
- `dev/phase7-database-fixture.php`: private server verifier exercises actual migrated-schema queries, active/revoked/expired/recovered session entitlement, account isolation, atomic usage reservation/cap/unique ledger. TEMPORARY tables only; production records are not modified.
- Full Phase 1–6 PHP/JavaScript regression set, Roth actual PDF generation, Roth engine, Managed-vs-Vanguard, all top-level PHP tests, PHP/affected-JavaScript syntax, whitespace/diff review.

## Deliberate limitations / later verification

Composer dependencies remain pinned by the committed lockfile and validated against installed metadata; no speculative dependency upgrades. Existing Chart.js CDN URLs remain unpinned: no trusted offline browser asset was available to vendor and verify, and no website fetch was authorized. A reviewed offline asset plus visual regression can close that remaining supply-chain/reproducibility limitation later. Do not claim full frontend dependency reproducibility or strict script-source CSP.

Visual print/mobile and assistive-technology checks, CDN availability, actual AI-provider responses, and real billing delivery are not verified here. No production Stripe/customer actions occurred. Large custom reports can now receive explicit size/validation errors; ordinary regression fixtures remain supported. Browser clients opened before activation may need a reload for the new CSRF wrapper/token. The existing staged-deployment connection uses its configured SSH identity; least-privilege deployment-account rotation and any historical secret-rotation/incident-response work are separate operator concerns, not asserted complete by these code changes.
