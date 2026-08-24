'use strict';
const assert = require('assert');
const engine = require('./engine.js');

function close(actual, expected, tolerance=1) {
  assert.ok(Math.abs(actual-expected)<=tolerance, `expected ${actual} to be within ${tolerance} of ${expected}`);
}

// 2026 MFJ: $24,800 at 10%, then $76,000 at 12%.
close(engine.progressiveTax(100800, engine.bracketsFor(2026,'married',0)), 11600, .01);

// Publication 915-style Social Security examples and caps.
close(engine.taxableSocialSecurity(40000, 0, 0, 'married'), 0, .01);
close(engine.taxableSocialSecurity(40000, 100000, 0, 'married'), 34000, .01);

// The enhanced senior deduction exists through 2028, phases out, then expires.
const d2026=engine.deductionFor(2026,'married',[69,69],200000,0);
assert.ok(d2026.enhancedSenior>0 && d2026.enhancedSenior<12000);
assert.strictEqual(engine.deductionFor(2029,'married',[72,72],100000,0).enhancedSenior,0);

const base={currentAge:69,spouseAge:69,lifeExpectancy:90,survivorLifeExpectancy:95,filingStatus:'married',
  traditionalIRA:1180000,rothIRA:185000,taxableAccount:500000,taxableCostBasis:350000,
  socialSecuritySelf:55000,socialSecuritySpouse:55000,otherOrdinaryIncome:0,
  annualOrdinaryInvestmentIncome:10000,annualLongTermGains:5000,
  targetAfterTaxSpending:110000,withdrawalMode:'target_after_tax',withdrawalOrder:'traditional_to_bracket_then_roth',
  targetMarginalRate:12,taxPaymentSource:'taxable',conversionAmount:25000,conversionYears:10,
  returnRate:7,taxableReturnRate:5,inflationRate:2.5,discountRate:5,includeIrmaa:true,includeNiit:true,medicareStartAge:65};

const result=engine.runRothAnalysis(base);
// Headline conversion cost must be identical to the first-row scenario difference.
close(result.conversionTaxCost,result.withConversion.yearlyData[0].allInTax-result.withoutConversion.yearlyData[0].allInTax,.01);
// Taxes funded from brokerage must reduce brokerage wealth relative to an otherwise identical Roth-funded run.
const rothFunded=engine.runRothAnalysis({...base,taxPaymentSource:'roth'});
assert.ok(result.withConversion.finalTaxableBalance<rothFunded.withConversion.finalTaxableBalance);
// RMD uses the prior year-end balance, without first applying the current year's return.
const rmdResult=engine.runRothAnalysis({...base,currentAge:73,spouseAge:73,lifeExpectancy:73,survivorLifeExpectancy:73,returnRate:7,conversionAmount:0,conversionYears:1,targetAfterTaxSpending:0});
close(rmdResult.withoutConversion.yearlyData[0].rmd,1180000/26.5,.01);
const youngerRmd=engine.runRothAnalysis({...base,currentAge:66,spouseAge:66,lifeExpectancy:75,survivorLifeExpectancy:75,returnRate:0,conversionAmount:0,conversionYears:1,targetAfterTaxSpending:0});
assert.strictEqual(youngerRmd.withoutConversion.yearlyData.find(r=>r.age===74).rmd,0);
assert.ok(youngerRmd.withoutConversion.yearlyData.find(r=>r.age===75).rmd>0);
// The survivor switches to single in the year after death and receives one Social Security benefit.
const widow=engine.runRothAnalysis({...base,deathAge:79});
const deathRow=widow.withConversion.yearlyData.find(r=>r.age===79);
const nextRow=widow.withConversion.yearlyData.find(r=>r.age===80);
assert.strictEqual(deathRow.filingStatus,'married');
assert.strictEqual(nextRow.filingStatus,'single');
assert.ok(nextRow.socialSecurity<deathRow.socialSecurity);
// Both alternatives deliver the same spending target by construction.
close(widow.withConversion.totalSpending,widow.withoutConversion.totalSpending,.01);

console.log('Roth engine tests passed');
