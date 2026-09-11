/** Federal ordinary-income brackets and base deductions, tax year 2026.
 * Source reference: IRS Revenue Procedure 2025-32; values already encoded in
 * the audited Roth engine. No credits, itemization, senior additions or state tax.
 */
(function (root) {
  'use strict';
  const ORDINARY_BRACKETS = {
    single: [[12400,.10],[50400,.12],[105700,.22],[201775,.24],[256225,.32],[640600,.35],[Infinity,.37]],
    married: [[24800,.10],[100800,.12],[211400,.22],[403550,.24],[512450,.32],[768700,.35],[Infinity,.37]],
    married_separate: [[12400,.10],[50400,.12],[105700,.22],[201775,.24],[256225,.32],[384350,.35],[Infinity,.37]],
    head: [[17700,.10],[67450,.12],[105700,.22],[201750,.24],[256200,.32],[640600,.35],[Infinity,.37]]
  };
  const STANDARD_DEDUCTION = { single:16100, married:32200, married_separate:16100, head:24150 };
  const brackets = {};
  for (const [status, rows] of Object.entries(ORDINARY_BRACKETS)) {
    let min = 0;
    brackets[status] = rows.map(([max, rate]) => { const row = Object.freeze({ min, max, rate }); min = max; return row; });
    Object.freeze(brackets[status]);
  }
  brackets.hoh = brackets.head;
  const deductions = Object.freeze({ ...STANDARD_DEDUCTION, hoh: STANDARD_DEDUCTION.head });
  const api = Object.freeze({ year: 2026, version: '2026.1', brackets: Object.freeze(brackets), deductions,
    forYear(year) { if (year !== 2026) throw new RangeError('Only statutory tax year 2026 is supported; future values are projection assumptions.'); return api; }
  });
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  else root.RBFederalTax = api;
})(typeof window !== 'undefined' ? window : this);
