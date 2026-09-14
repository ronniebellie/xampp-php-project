# Journey audit — 2026-09-14

Baseline: b4113de (completed CalcForAdvisors Phases 1–7). Work uses an isolated local clone; no browser, HTTP, analytics, or production Stripe calls are authorized for this audit.

## Initial findings and remediation order

1. Financial/state: Phase 1 reconciliation promotes calculated drafts to completed records. Phase 3 overwrites saved planning amounts with drafts; Phases 4–6 do not reject those drafts. Earlier saves leave later completion flags current. The stress ledger marks zero-demand/zero-balance and exactly funded terminal years as unsuccessful funding. Numeric adapters accept malformed/missing data as zero. Social Security summary infers FRA from equal dollar amounts, which is not a valid age determination.
2. Security/privacy: saved Phase 3–6 strings enter innerHTML without contextual escaping. HTTP Journey origin receives credentialed CORS. Feedback includes the full request query in another URL. Journey PDF lacks CSRF, keeps the session lock while rendering, and merges cloud/browser records. Browser cache and pending writes are not associated with an account; startup can discard unsynced work. Cloud conflict detection compares client clocks rather than the version read, outside the write transaction; first import has a check/write race.
3. Premium: stored active/trial status can survive its dated access window; billing portal opens cancellation rather than general payment recovery. Journey webhook ownership prefers metadata/hints over existing association; its upsert lacks event-order protection. Latest-updated subscription selection can mask a still-active subscription.
4. Product/reporting: all six phases, including Phase 2 Social Security, exist in homepage and navigation source. No Phase 2-specific hiding rule was found. Homepage phase cards lack direct links; account transition wording implies a free account saves the plan despite browser-only storage. Reports need consistent saved-state selection and explicit model scope. Existing noindex policy is deliberate and should be preserved.
5. Operations/accessibility: sync startup has no request deadline, potentially indefinitely delaying phase initialization. Some Journey fixture scripts require a non-Node `load()` global and hard-coded local paths. Check focus/announcements and reduced motion with source/DOM fixtures; visual browser verification is deferred under the access rule.

## Model scope observed

Phase 1 computes a household spending target and other dependable income. Phase 2 records a selected-age benefit entered by the user; its FRA lookup is not a benefit calculator. Phase 3 computes initial gross savings demand with 4%/5% educational bands. Phase 4 projects fixed real annual withdrawals at the beginning of each year, with 28/33-year horizons, 2.75%/1% real returns and an initial 15% shock. Phases 5 and 6 rank qualitative review priorities; they do not compute taxes, RMD dollars, Roth conversions or survivor awards. Shared calculator statutory modules therefore must not be injected as unrequested quantitative features.

## Baseline verification

All top-level PHP regression scripts and top-level JavaScript regressions, plus Roth engine tests, passed locally. Two Journey legacy fixture scripts did not run under Node because they depend on `load()`; this is test harness failure, not passing product evidence. Independent stress-ledger examples reproduce zero-demand and exact-final-payment defects.

This is an audit work log, not a production-readiness certification. Release/test evidence and remaining findings must be reconciled before GO.

## Completed remediation and verification

The issues above were corrected in one coordinated release because saved-state, entitlement, and report changes must agree. Earlier-phase saves invalidate dependent completion; drafts do not unlock later phases. Phase 3 retains a prior saved snapshot but requires review before proceeding. Reports consume one saved cloud snapshot, validate financial agreement and dependent snapshots, and refuse stale or incomplete exports. Phase 1 annual totals are derived from the monthly amount. Saved strings are escaped before HTML insertion.

Cloud writes now use a server revision token and a per-owner transaction, rather than client clocks. Import cannot overwrite an existing plan; stale/forced writes conflict. Twenty versions are retained. Browser work is associated with its account, preserved through offline startup/conflicts, and archived locally during account changes; conflict controls allow downloading the browser copy before loading the account copy. Storage remains sensitive local browser data, not encrypted storage. Legacy unowned browser state cannot establish an earlier account identity retroactively.

Premium status is recalculated against paid/trial deadlines on access. Any valid owned subscription can grant access. General billing management supports payment recovery; expired users retain read-only access to an existing cloud plan. Existing subscription ownership wins over hints/metadata, older events cannot restore stale entitlement, and interrupted webhook workers can be retried after ten minutes. Checkout uses canonical return URLs, bounded connection/request timeouts and releases its session lock. No Stripe service or real customer action was performed.

Login and registration now require CSRF and bounded private-file throttling. Journey credentialed CORS is HTTPS-only. Feedback drops URL queries/fragments and is rate limited. PDF generation requires CSRF, uses bounded/sanitized report data and temporary files, releases its session lock, and has a separate per-user admission limit. Existing hardened shared authentication/session, AI, password-reset and API protections remain in place. Journey itself has no new AI planning feature; linked/shared calculator AI remains separately disclosed and guarded. Analytics retains an allowlisted funnel without financial fields and uses path-only locations; actual GA4 collection was not contacted.

## Six-phase findings

| Phase | Navigation/display evidence | Calculations and handoff | Scope/remaining limitation |
|---|---|---|---|
| 1 — Spending & Goals | PHP-rendered homepage/navigation and linked phase checked | Current annual spending / 12; target and other income × 12; only completed/saved handoff accepted | User supplies a consistent household and dollar basis; no detailed inflation forecast |
| 2 — Social Security | Present in all seven rendered navigation surfaces; homepage now links directly to it; no phase-specific hiding rule found | Selected-age benefit remains user supplied; shared finance-core FRA calculation replaces duplicate logic; changing saved benefit invalidates downstream work | Not an SSA benefit/eligibility calculator; no future-income bridge or dual-benefit award calculation |
| 3 — Build Your Plan | Page, navigation, next-step and save flow checked locally | $4,000 spending − $2,000 SS − $500 other = $1,500/month, $18,000/year; $450,000 savings gives 4%; stale/draft plans blocked downstream | Gross initial cash flow; 4%/5% bands are educational, not a sustainability guarantee or after-tax recommendation |
| 4 — Stress Test | Page and saved-summary state checked; chart has accompanying textual outcomes | Start-year withdrawals then real growth; exactly funded terminal year succeeds; first unfunded withdrawal marks depletion; zero need is funded | Deterministic 28/33-year educational scenarios, not Monte Carlo or a forecast |
| 5 — Tax Strategy | Page, inputs, saved priority and handoff checked | Unknown account mix plus near RMD yields both review priorities; Roth-heavy/no-withdrawal/later-RMD fixture yields no dominant priority; only valid saved Phase 3 is accepted | Qualitative tax/RMD/Roth review, no tax liability, RMD amount or conversion optimization |
| 6 — Survivor Planning | Page, saved priority, completion/report gates checked | Existing 131-case survivor fixture suite passes; revised gate rejects stale/draft financial source | Qualitative survivor risk review; no survivor benefit dollar award or estate/legal advice |

The Phase 2 concern was not reproduced in PHP rendering or found in CSS/source. All six are present and linked. A real viewport/browser rendering problem cannot be ruled out without the expressly deferred visual check.

## Independent model evidence

Known-answer ledgers cover $100 funding ten $10 withdrawals exactly, failure on the eleventh, $0/$0 for 33 years, and beginning-year cash conservation. A $1,000 balance withdrawing $100 and earning 10% becomes $990 then $979. A 15% initial shock leaves $850 before a $100 withdrawal leaves $750. A $1 trillion balance withdrawing $10 billion for 28 years at zero growth leaves $720 billion. Saved/reloaded fixtures reproduce results. Null, blank, negative, boolean, object, NaN, Infinity and inconsistent annual amounts are rejected at the saved-plan boundary. Shared statutory/date-only and Roth regression suites pass; no statutory constants were invented or updated without local authority.

Stress assumptions remain 2.75% real baseline returns, 1% weak returns, a 15% initial loss, 28-year default and 33-year longevity horizon. Page and report wording now identify real dollars, beginning-year withdrawals, gross amounts, income availability and exclusions. Taxes, differential inflation, income interruptions, exact Social Security entitlement and future start dates require separate analysis. No serious unresolved arithmetic defect was found within this stated model scope.

## Positioning, accessibility, metadata and performance

Homepage distinguishes ronbelisle.com's quick snapshot/individual calculators from Journey's guided six-phase process. Free-account wording accurately describes browser progress; Premium describes cloud continuity and a summary without promising implemented alternative-comparison tools. The interface retains one decision at a time.

Added skip navigation and focusable main targets, reduced-motion behavior and a JavaScript-required explanation. Existing labels, result announcements, modal keyboard handling and textual chart companions were reviewed without a browser; shared keyboard regressions passed. Dynamic saved text is escaped. Actual screen-reader behavior, viewport reflow and rendered contrast remain manual follow-ups.

Existing deliberate noindex strategy is preserved; missing phase robots/canonical metadata is aligned. No new sitemap/indexing policy or speculative structured data was introduced. Updated CSS/JS cache versions accompany changed assets. Sync has a bounded request deadline in browsers supporting AbortSignal.timeout; older browser fallbacks still depend on native fetch behavior. PDF concurrency/temp-file/session controls reduce server contention. No schema work or dependency upgrade was needed.

## Regression evidence

- Every top-level dev/test-*.php suite passed, including shared Phases 1–7, authentication/security, platform, numerical, subscription, report and operations checks.
- Every top-level dev/test-*.js suite and roth-conv/engine.test.js passed. New Journey model/state, sync and legacy harnesses passed; archived Phase 4 classifier has 29 checks and Phase 6 has 131.
- New PHP Journey security/report suite: 43 checks. Six-phase rendering: 42 phase link/title checks across seven pages, plus main/heading/positioning checks.
- Private server CLI fixture: 56 checks against connection-local TEMPORARY tables copied from actual schema. Includes owner isolation, deleted-user sessions, active/trial/expiry/cancellation/recovery transitions, revision conflicts, import, 20-version retention and webhook retry. Persistent production rows were not changed by testing.
- Journey PDF regression: 58 checks; generated five-page synthetic report rendered locally and every page inspected. Final changed next-steps page re-rendered and inspected. Values, units, date, version 2, assumptions and educational limitations present.
- Legacy Journey non-database portions pass. Three older scripts requiring a local MySQL instance cannot complete locally (account helpers, milestone5-p1, milestone5-p2); other legacy scripts explicitly skip their database sections. They are not represented as full database passes. Relevant new SQL behavior is tested by the isolated server fixture instead.
- Full PHP lint and changed JavaScript syntax checks pass. Final release must additionally pass server PHP/platform/hash and Apache activation checks before production GO.

## Release method and recovery

Use the established committed Git archive + locked Composer build, excluding source-control, documentation, dev fixtures, secrets/configuration and deployment files from public artifacts. Verify archive/file hashes, server PHP lint, actual production PHP extension requirements, dependencies and synthetic SQL behavior before atomic current-symlink activation. Verify Apache configuration/service, symlink targets and hashes afterward. Retain the prior release for immediate rollback. No HTTP probes, browser, analytics or Stripe verification is part of this release. No database migration is required.

Production release identifiers, commit, final cleanliness and activation results are recorded in the delivered final audit report and private server deployment record.
