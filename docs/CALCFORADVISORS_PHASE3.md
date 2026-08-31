# CalcForAdvisors 2.0 — Phase 3 portal foundation

Phase 3 is a non-production UI/routing foundation. Production subscriber data
has **not** been audited because the development environment cannot access the
production MySQL server. No Phase 2 migration has run, and migration/cutover
remain blocked until the read-only audit is completed in an appropriate server
environment.

## Routes

- `/p/{portal-slug}` — public advisor portal, noindex by default.
- `/p/{portal-slug}/calculator/{calculator-id}` — branded iframe wrapper.

Both routes validate identifiers server-side. The portal reads the authoritative
shared calculator catalog and never carries a second calculator list. Database
loading inspects available columns and supports the legacy `trial_slug` until
`portal_slug` is migrated. Missing connections, rows, or columns fail closed.

For local visual review only, `http://localhost:<port>/p/demo-advisor` provides a
non-persistent fixture. It is gated by the HTTP host and cannot activate on a
public hostname.

## Framing cutover requirement

Current production responses send `X-Frame-Options: SAMEORIGIN`, so the future
cross-origin wrapper is intentionally still blocked. At cutover, scope the
calculator response headers (preferably only requests with the validated
`embed=1` mode) to:

`Content-Security-Policy: frame-ancestors 'self' https://calcforadvisors.ronbelisle.com`

For those same calculator responses, remove `X-Frame-Options: SAMEORIGIN`; modern
browsers do not support an equivalent multi-origin X-Frame-Options directive.
Do not use `*`, arbitrary origins, or reflect an unvalidated request origin.
Retain SAMEORIGIN for other pages unless separately reviewed.

Embed mode only suppresses shared promotional/navigation chrome. It does not
change calculator math, Premium entitlement, saving, comparisons, exports, or
AI Explain behavior. Direct calculator visits remain unchanged.

## Deferred

The authenticated dashboard and branding editor are deferred to Phase 4 because
safe persistence depends on the unaudited production data, Phase 2 migration,
and later authentication/session work. No temporary write path was introduced.
