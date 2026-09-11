'use strict';
const assert = require('node:assert/strict');
const gap = require('../js/lib/survivor-gap-core.js');
const tax = require('../js/lib/federal-tax-2026.js');
const close = (a,b) => assert(Math.abs(a-b)<1e-6, `${a} != ${b}`);
const base = {singleLifeMonthly:3000,jointLifeMonthly:2700,survivorYears:20,survivorPercent:100,discountRate:0};
close(gap.calculate(base).presentValue,648000);
close(gap.calculate(base).optionCostMonthly,300);
close(gap.calculate({...base,survivorPercent:50}).presentValue,324000);
close(gap.calculate({...base,survivorPercent:0}).presentValue,0);
// Independent discounted payment ledger, not the closed-form annuity expression.
let ledger = 0;
for (let month=1;month<=240;month++) ledger += 2700 / (1.04 ** (month/12));
close(gap.calculate({...base,discountRate:4}).presentValue,ledger);
for (const overrides of [{survivorYears:0},{survivorYears:1.5},{survivorPercent:101},{discountRate:-1},{jointLifeMonthly:NaN}]) {
  assert.throws(()=>gap.calculate({...base,...overrides}),RangeError);
}
assert.equal(tax.year,2026);
assert.equal(tax.deductions.single,16100);
assert.equal(tax.deductions.married,32200);
assert.equal(tax.deductions.head,24150);
assert.equal(tax.deductions.married_separate,16100);
assert.throws(()=>tax.forYear(2025),RangeError);
assert.throws(()=>tax.forYear(2027),RangeError);
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const context = vm.createContext({});
for (const file of ['js/lib/federal-tax-2026.js','js/lib/rmd-tax-core.js']) vm.runInContext(fs.readFileSync(path.join(__dirname,'..',file),'utf8'),context);
const calc = context.RBTaxRmd.calculateFederalTax;
close(calc(12400,'single'),1240);
close(calc(12401,'single'),1240.12);
close(calc(50400,'single'),5800);
close(calc(50401,'single'),5800.22);
close(calc(24800,'married'),2480);
close(calc(17700,'hoh'),1770);

const rmd = require('../js/lib/rmd-tax-core.js');
assert.deepEqual(rmd.rmdStartAgeForBirthYear(1959),{supported:true,age:73});
assert.deepEqual(rmd.rmdStartAgeForBirthYear(1960),{supported:true,age:75});
assert.equal(rmd.rmdStartAgeForBirthYear(1950).supported,false);
const tableII = rmd.resolveRMD({ownerAge:73,spouseAge:62,birthYear:1953,
  priorYearEndBalance:1000000,isSpouseSoleBeneficiary:true});
assert.equal(tableII.table,'II');
assert.equal(tableII.divisor,27.2);
close(tableII.amount,36764.70588235294);
assert.equal(tableII.amount.toFixed(2),'36764.71');
assert.equal(rmd.getRMDDivisorResult(74,true,63).supported,false);
assert.equal(rmd.getRMDDivisor(74,true,63),null);
assert.equal(rmd.getRMDDivisorResult(73,false,62).divisor,26.5);
assert.equal(rmd.getRMDDivisorResult(121,false,null).supported,false);
assert.equal(rmd.getSingleLifeExpectancy(60),27.1);
assert.equal(rmd.getSingleLifeExpectancy(120),1.0);
assert.equal(rmd.getSingleLifeExpectancy(121),1.0);
assert.equal(rmd.getSingleLifeExpectancy(59),null);

const longevity = require('../js/lib/actuarial-longevity.js');
close(longevity.getRemainingLifeExpectancy('male',90),3.90);
close(longevity.getRemainingLifeExpectancy('female',90),4.65);
close(longevity.getRemainingLifeExpectancy('male',22),52.55);
assert.notEqual(longevity.getRemainingLifeExpectancy('male',22),longevity.getRemainingLifeExpectancy('male',60));
assert.equal(longevity.getRemainingLifeExpectancy('female',61),null);
assert.equal(longevity.getActuarialDeathAge('female',61),null);

const inherited = require('../js/lib/inherited-ira-rules.js');
assert.equal(inherited.classify({beneficiaryCategory:'noneligible_designated',ownerDiedBeforeRequiredBeginningDate:true}).supported,true);
assert.equal(inherited.classify({beneficiaryCategory:'eligible_designated_elects_10_year',ownerDiedBeforeRequiredBeginningDate:true}).supported,true);
assert.equal(inherited.classify({beneficiaryCategory:'noneligible_designated',ownerDiedBeforeRequiredBeginningDate:false}).supported,false);
assert.equal(inherited.classify({beneficiaryCategory:'other',ownerDiedBeforeRequiredBeginningDate:true}).supported,false);

const ssContext = vm.createContext({});
for (const file of ['js/lib/finance-core.js','js/lib/ss-household-core.js']) vm.runInContext(fs.readFileSync(path.join(__dirname,'..',file),'utf8'),ssContext);
const earlyDeath = ssContext.RBSSHousehold.simulateHouseholdSS({colaRate:0,discountRate:0,
  higherEarner:{birthYear:1960,pia:3000,claimAge:70,deathAge:65},
  lowerEarner:{birthYear:1965,pia:0,claimAge:67,deathAge:80},lowerEarlyCompareAge:67});
const survivorRow = earlyDeath.yearly.find(row=>row.phase==='survivor_lower');
assert(survivorRow.householdMonthly>0,'death before selected worker claim age must not produce a zero survivor benefit');
close(survivorRow.householdMonthly,ssContext.RBFinance.calculateMonthlyBenefit(3000,1960,70)*0.715);
assert.throws(()=>ssContext.RBSSHousehold.simulateHouseholdSS({colaRate:0,discountRate:0,
  higherEarner:{birthYear:1950,pia:3000,claimAge:70,deathAge:75},
  lowerEarner:{birthYear:1950,pia:1000,claimAge:67,deathAge:90},lowerEarlyCompareAge:67}),error=>error&&error.name==='RangeError');

console.log('PASS Phase 3 statutory, longevity, survivor, tax, and Survivor Gap fixtures.');
