# Phase 6 advisor workflow and reporting

The authoritative advisor catalog drives the free trial list (16 current tools),
account count and public marketing catalog. Static marketing has a truthful
count-free fallback. Feature flags describe capability, not entitlement: shared
client links do not grant paid advisor access. Custom domains and removal of
source branding are not advertised as included self-service features.

Trial calculators open native links in a new tab with explicit return guidance;
this preserves SAMEORIGIN framing protection instead of weakening it. Trial
expiration uses the central entitlement evaluator. Shared Compare and AI
dialogs support focus containment, Escape, focus restoration and labels.

Print styles retain table headers and expose an educational context note.
Calculator PDFs append a context/assumptions page; per-calculator inputs and
model results are unchanged. Exports represent supplied scenario results and
are not represented as independently verified financial statements.

Verification: dev/test-phase6-workflow.php and dev/test-phase6-keyboard.js,
plus all existing regression suites. Manual screen-reader, browser print,
mobile layout and cross-tab interaction review remains a later check; no live
website or browser verification was performed.
