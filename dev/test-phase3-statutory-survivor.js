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
assert.deepEqual(rmd.rmdStartAgeForBirthYear('1949-06-30'),{supported:true,age:70.5});
assert.deepEqual(rmd.rmdStartAgeForBirthYear('1949-07-01'),{supported:true,age:72});
assert.deepEqual(rmd.rmdStartAgeForBirthYear(1950),{supported:true,age:72});
assert.deepEqual(rmd.rmdStartAgeForBirthYear(1951),{supported:true,age:73});
assert.deepEqual(rmd.rmdStartAgeForBirthYear(1958),{supported:true,age:73});
assert.deepEqual(rmd.rmdStartAgeForBirthYear(1959),{supported:true,age:75});
assert.deepEqual(rmd.rmdStartAgeForBirthYear(1960),{supported:true,age:75});
assert.equal(rmd.rmdStartAgeForBirthYear(1949).supported,false);
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
close(longevity.getRemainingLifeExpectancy('female',61),22.86);
close(longevity.getRemainingLifeExpectancy('male',0),73.54);
close(longevity.getRemainingLifeExpectancy('female',100),2.35);
close(longevity.getRemainingLifeExpectancy('male',119),0.60);
for(let age=0;age<=119;age++) assert.notEqual(longevity.getRemainingLifeExpectancy('male',age),null);
assert.equal(longevity.getRemainingLifeExpectancy('female',120),null);
assert.equal(longevity.getRemainingLifeExpectancy('female',-1),null);

const inherited = require('../js/lib/inherited-ira-rules.js');
assert.equal(inherited.classify({beneficiaryCategory:'noneligible_designated',ownerDiedBeforeRequiredBeginningDate:true}).supported,true);
assert.equal(inherited.classify({beneficiaryCategory:'eligible_designated_elects_10_year',ownerDiedBeforeRequiredBeginningDate:true}).supported,true);
const postRbd=inherited.classify({beneficiaryCategory:'noneligible_designated',ownerDiedBeforeRequiredBeginningDate:false});
assert.equal(postRbd.supported,true);assert.equal(postRbd.annualDistributionsRequired,true);
assert.equal(inherited.classify({beneficiaryCategory:'eligible_designated_elects_10_year',ownerDiedBeforeRequiredBeginningDate:false}).supported,false);
assert.equal(inherited.classify({beneficiaryCategory:'other',ownerDiedBeforeRequiredBeginningDate:true}).supported,false);
const initialDivisor=inherited.postRbdInitialDivisor({beneficiaryAgeFirstDistributionYear:65,ownerAgeAtDeath:90,getSingleLifeExpectancy:rmd.getSingleLifeExpectancy});
assert.equal(initialDivisor.divisor,22.9);assert.equal(initialDivisor.basis,'beneficiary');
close(inherited.postRbdRequiredDistribution(1000000,initialDivisor.divisor,1).amount,1000000/22.9);
assert.equal(inherited.postRbdDivisor(initialDivisor.divisor,2),21.9);

const ssContext = vm.createContext({});
for (const file of ['js/lib/finance-core.js','js/lib/ss-household-core.js']) vm.runInContext(fs.readFileSync(path.join(__dirname,'..',file),'utf8'),ssContext);
const earlyDeath = ssContext.RBSSHousehold.simulateHouseholdSS({colaRate:0,discountRate:0,
  higherEarner:{birthYear:1960,birthDate:'1960-07-01',pia:3000,claimAge:70,deathAge:64,survivorClaimAge:60},
  lowerEarner:{birthYear:1965,birthDate:'1965-07-01',pia:0,claimAge:67,deathAge:80,survivorClaimAge:60},lowerEarlyCompareAge:67});
const survivorRow = earlyDeath.yearly.find(row=>row.phase==='survivor_lower');
assert(survivorRow.householdMonthly>0,'death before selected worker claim age must not produce a zero survivor benefit');
close(survivorRow.householdMonthly,ssContext.RBFinance.calculateMonthlyBenefit(3000,1960,70)*0.715);
close(ssContext.RBSSHousehold.survivorBenefitPercentage('1958-07-01',720),0.715);
close(ssContext.RBSSHousehold.survivorBenefitPercentage('1958-07-01',756),0.85);
close(ssContext.RBSSHousehold.survivorBenefitPercentage('1958-07-01',796),1);
close(ssContext.RBSSHousehold.survivorBenefitPercentage('1962-01-02',780),1-24*(19/56)*0.01);
close(ssContext.RBSSHousehold.survivorBenefitPercentage('1944-06-01',720),0.715);
for(const [dob,fraMonths] of [['1940-01-01',780],['1940-01-02',782],['1941-01-02',784],['1945-01-02',792],['1957-01-02',794],['1959-01-02',798],['1961-01-02',802],['1962-01-02',804]]) {
  close(ssContext.RBSSHousehold.survivorBenefitPercentage(dob,720),0.715);
  close(ssContext.RBSSHousehold.survivorBenefitPercentage(dob,fraMonths),1);
}
assert.throws(()=>ssContext.RBSSHousehold.survivorBenefitPercentage('',720),error=>error&&error.name==='RangeError');

// Reject coercion to valid statutory ages and impossible Gregorian dates.
for(const value of [null,undefined,'','  ',NaN,Infinity,-1,119.5,120,true,false,[],{}]) {
  assert.equal(longevity.getRemainingLifeExpectancy('male',value),null);
  assert.equal(longevity.getActuarialDeathAge('female',value),null);
}
for(let age=0;age<=119;age++) for(const sex of ['male','female']) {
  assert(Number.isFinite(longevity.getRemainingLifeExpectancy(sex,age)));
  close(longevity.getRemainingLifeExpectancy(sex,String(age)),longevity.getRemainingLifeExpectancy(sex,age));
}
for(const value of ['1949-99-99','1949-02-29','1900-02-29','1959-04-31','1959-00-01','1960-01-00','1949-7-1','1949-07-01junk',null,undefined,'',true,[],{year:1949,month:2,day:30}]) {
  assert.equal(rmd.rmdStartAgeForBirthYear(value).supported,false,JSON.stringify(value));
}
for(const value of ['1948-02-29','2000-02-29','1949-06-30','1949-07-01','1950-12-31','1951-01-01']) assert.equal(rmd.rmdStartAgeForBirthYear(value).supported,true);
assert.equal(rmd.rmdStartAgeForBirthYear(1949,6,30).age,70.5);
assert.equal(rmd.rmdStartAgeForBirthYear(1949,7,1).age,72);
assert.equal(rmd.rmdStartAgeForBirthYear(1959,2,29).supported,false);
assert.equal(rmd.rmdStartAgeForBirthYear({year:1949,month:true,day:1}).supported,false);
assert.equal(rmd.rmdStartAgeForBirthYear(1949,1,true).supported,false);
for(const birthDate of ['1959','1959-1-1',false,0,'1949-99-99']) assert.equal(rmd.resolveRMD({ownerAge:73,birthDate,birthYear:1959,priorYearEndBalance:1000,isSpouseSoleBeneficiary:false}).supported,false);
assert.equal(rmd.resolveRMD({ownerAge:73,birthDate:'1959-01-01',birthYear:1953,priorYearEndBalance:1000,isSpouseSoleBeneficiary:false}).supported,false);
for(const value of [null,undefined,'',true,[60],{}]) assert.equal(rmd.getSingleLifeExpectancy(value),null);
for(const value of [null,undefined,'',NaN,true,73.5]) {
  assert.equal(rmd.getRMDDivisorResult(73,true,value).supported,false);
  assert.equal(rmd.resolveRMD({ownerAge:value,birthYear:1953,priorYearEndBalance:1000000,isSpouseSoleBeneficiary:false}).supported,false);
}
for(const status of [undefined,null,'','false','no',0,1]) assert.equal(rmd.getRMDDivisorResult(73,status,62).supported,false);
assert.match(rmd.getRMDDivisorResult(74,true,63).reason,/special joint-life case is not supported/);
assert.equal(rmd.getRMDDivisorResult(73,true,63).table,'III','exactly ten years younger is not the special Table II case');

for(const status of [undefined,null,'','unknown','yes','no','false',0,1,{},[]]) {
  const result=inherited.classify({beneficiaryCategory:'noneligible_designated',ownerDiedBeforeRequiredBeginningDate:status});
  assert.equal(result.supported,false);assert.match(result.reason,/RBD status/);
}
// False explicitly includes death ON the RBD; no inference from age alone.
assert.equal(inherited.classify({beneficiaryCategory:'noneligible_designated',ownerDiedBeforeRequiredBeginningDate:false}).rule,'10-year-post-rbd');
assert.equal(inherited.classify({beneficiaryCategory:'noneligible_designated',ownerDiedBeforeRequiredBeginningDate:true}).rule,'10-year-pre-rbd');
assert.equal(inherited.classify({beneficiaryCategory:'noneligible_designated',ownerAgeAtDeath:90}).supported,false);
for(const value of [null,undefined,'',NaN,-1]) assert.equal(inherited.postRbdRequiredDistribution(value,22.9,1).supported,false);
assert.equal(inherited.postRbdRequiredDistribution(0,22.9,1).amount,0);
for(const value of [true,null,undefined,'',[],{}]) {
  assert.equal(inherited.postRbdDivisor(22.9,value),null);
  assert.equal(inherited.postRbdDivisor(value,1),null);
}

const SS=ssContext.RBSSHousehold;
const ssOptions={colaRate:0,discountRate:0,lowerEarlyCompareAge:67,
  higherEarner:{birthYear:1962,birthDate:'1962-07-01',pia:3000,claimAge:62,deathAge:59,survivorClaimAge:60},
  lowerEarner:{birthYear:1962,birthDate:'1962-07-01',pia:0,claimAge:67,deathAge:80,survivorClaimAge:60}};
const workerBasis=ssContext.RBFinance.calculateMonthlyBenefit(3000,1962,62);
// Required age 60, 60y6m, 61y6m, month before FRA, and exact FRA fixtures.
for(const claimMonths of [720,726,738,803,804]) {
  const expectedPercent=claimMonths>=804?1:1-(804-claimMonths)*(19/56)*.01;
  close(SS.survivorBenefitPercentage('1962-07-01',claimMonths),expectedPercent);
  const result=SS.simulateHouseholdSS({...ssOptions,lowerEarner:{...ssOptions.lowerEarner,survivorClaimAge:claimMonths/12}});
  const claimYear=Math.floor(claimMonths/12),monthsPaid=12-claimMonths%12;
  const first=result.yearly.find(row=>row.lowerAge===claimYear);
  close(first.annualHousehold,workerBasis*expectedPercent*monthsPaid);
  close(first.householdMonthly*12,first.annualHousehold);
  close(result.yearly.find(row=>row.lowerAge===claimYear+1).annualHousehold,workerBasis*expectedPercent*12);
  for(const row of result.yearly.filter(row=>row.lowerAge<claimYear)) close(row.annualHousehold,0);
  close(result.totalHousehold,result.yearly.reduce((sum,row)=>sum+row.annualHousehold,0));
  close(result.beforeFirstDeath+result.afterFirstDeath,result.totalHousehold);
  close(result.survivorFloor,workerBasis*expectedPercent);
}
// Every supported claim month in multiple FRA bands must produce actual dollars.
for(const [dob,fra] of [['1940-01-01',780],['1940-01-02',782],['1958-07-01',796],['1961-07-01',802],['1962-07-01',804]]) {
  for(let claim=720;claim<=fra;claim++) {
    const year=Number(dob.slice(0,4));
    const result=SS.simulateHouseholdSS({...ssOptions,higherEarner:{...ssOptions.higherEarner,birthYear:year,birthDate:dob},lowerEarner:{...ssOptions.lowerEarner,birthYear:year,birthDate:dob,survivorClaimAge:claim/12}});
    const basis=ssContext.RBFinance.calculateMonthlyBenefit(3000,year,62);
    close(result.yearly.find(row=>row.lowerAge===Math.floor(claim/12)).annualHousehold,basis*SS.survivorBenefitPercentage(dob,claim)*(12-claim%12));
  }
}
const withCola=SS.simulateHouseholdSS({...ssOptions,colaRate:2,discountRate:3,lowerEarner:{...ssOptions.lowerEarner,survivorClaimAge:61.5}});
const neverClaimed=SS.simulateHouseholdSS({...ssOptions,lowerEarner:{...ssOptions.lowerEarner,deathAge:61,survivorClaimAge:62}});
close(neverClaimed.totalHousehold,0);close(neverClaimed.survivorFloor,0);
const ownWins=SS.simulateHouseholdSS({...ssOptions,lowerEarner:{...ssOptions.lowerEarner,pia:5000,claimAge:62,survivorClaimAge:61.5}});
close(ownWins.yearly.find(row=>row.lowerAge===62).annualHousehold,ssContext.RBFinance.calculateMonthlyBenefit(5000,1962,62)*12);
const pct615=1-(804-738)*(19/56)*.01;
close(withCola.yearly.find(row=>row.lowerAge===61).annualHousehold,workerBasis*pct615*6);
close(withCola.yearly.find(row=>row.lowerAge===62).annualHousehold,workerBasis*pct615*(6+6*1.02));
close(withCola.totalHousehold,withCola.yearly.reduce((sum,row,i)=>sum+row.annualHousehold/1.03**i,0));
const reverse=SS.simulateHouseholdSS({...ssOptions,higherEarner:{...ssOptions.lowerEarner,survivorClaimAge:61.5},lowerEarner:{...ssOptions.higherEarner}});
close(reverse.yearly.find(row=>row.higherAge===61).annualHousehold,workerBasis*pct615*6);
close(reverse.survivorFloor,workerBasis*pct615);
for(const dob of ['1949-99-99','1962-02-29','1900-02-29','1962-7-1','']) assert.throws(()=>SS.survivorBenefitPercentage(dob,720),/birth date/);
for(const value of [null,undefined,'',NaN,60.01,59.5,true]) assert.throws(()=>SS.simulateHouseholdSS({...ssOptions,lowerEarner:{...ssOptions.lowerEarner,survivorClaimAge:value}}),/claim age/);
assert.throws(()=>SS.simulateHouseholdSS({...ssOptions,lowerEarner:{...ssOptions.lowerEarner,birthYear:1961}}),/must match/);

// Exercise the actual inherited calculator, not just the classification helper.
const fields={inheritedIRAForm:{addEventListener(){}}};
const iraContext=vm.createContext({RBFederalTax:tax,RBTaxRmd:rmd,RBInheritedIraRules:inherited,document:{addEventListener(){},getElementById(id){return fields[id]||null;}},window:{}});
vm.runInContext(fs.readFileSync(path.join(__dirname,'../estate-planning/inherited-ira-impact/calculator.js'),'utf8'),iraContext);
for(const status of ['yes','no','',undefined,'unknown','toString']) {
  fields.diedBeforeRbd={value:status};
  const result=inherited.classify({...iraContext.getFormData(),beneficiaryCategory:'noneligible_designated'});
  assert.equal(result.supported,status==='yes'||status==='no');
}
fields.returnRate={value:'0'};fields.inheritedReturnRate={value:'0'};fields.heirShare1={value:'100'};fields.heirAge1={value:''};
assert.equal(iraContext.getFormData().returnRate,0);assert.equal(iraContext.getFormData().inheritedReturnRate,0);
assert(Number.isNaN(iraContext.getFormData().heirs[0].age));
const heirParams={balance:1000000,otherIncome:0,filingStatus:'single',strategy:'year10',returnRate:0};
for(const status of [true,false]) {
  const rule=inherited.classify({beneficiaryCategory:'noneligible_designated',ownerDiedBeforeRequiredBeginningDate:status});
  const sim=iraContext.simulateHeirInheritedIRA({...heirParams,inheritedRule:rule,initialPostRbdDivisor:22.9});
  if(status) for(const row of sim.yearlyData.slice(0,9)) close(row.distribution,0);
  else close(sim.yearlyData[0].distribution,1000000/22.9);
  close(sim.yearlyData[9].requiredDistribution,sim.yearlyData[9].distribution);
  close(sim.yearlyData[9].balance,0);
  close(sim.yearlyData.reduce((sum,row)=>sum+row.distribution,0),1000000);
}
for(const rule of [undefined,{supported:false},{supported:true,rule:'unknown'}]) assert.throws(()=>iraContext.simulateHeirInheritedIRA({...heirParams,inheritedRule:rule}),/classification/);

const ssFields={overrideLongevity:{checked:false},higherSex:{value:'male'},lowerSex:{value:'female'}};
const ssUI=vm.createContext({RBFinance:ssContext.RBFinance,RBActuarial:longevity,RBSSHousehold:SS,
  window:{location:{pathname:'/ss-survivor-impact/',origin:'offline'}},
  document:{addEventListener(){},getElementById(id){return ssFields[id]||(ssFields[id]={value:'',style:{}});}},
  Chart:class {constructor(element,config){this.config=config;}}});
vm.runInContext(fs.readFileSync(path.join(__dirname,'../ss-survivor-impact/calculator.js'),'utf8'),ssUI);
for(const age of [null,undefined,'',' ',NaN,'60.5','60junk',-1,120]) {
  assert.equal(ssUI.resolveDeathAgesFromData({higherCurrentAge:age,lowerCurrentAge:age}).higherDeathAge,null);
  ssFields.higherCurrentAge={value:age};ssFields.lowerCurrentAge={value:age};
  assert.equal(ssUI.resolveDeathAges().higherDeathAge,null);
}
for(let age=0;age<=119;age++) assert.equal(ssUI.resolveDeathAgesFromData({higherCurrentAge:age,lowerCurrentAge:age}).higherDeathAge,longevity.getActuarialDeathAge('male',age));
for(const value of [null,undefined,'','61.5junk']) assert(Number.isNaN(ssUI.exactInputNumber(value)));
ssUI.createHouseholdChart(withCola.yearly);
assert.deepEqual(Array.from(ssUI.window.householdChart.config.data.datasets[0].data),Array.from(withCola.yearly,row=>row.annualHousehold/12));
ssFields.higherBirthDate={value:'1958-07-01'};ssUI.resetSavedStatutoryInputs({});
assert.equal(ssFields.higherBirthDate.value,'');assert.equal(ssFields.lowerSurvivorClaimAge.value,'');
for(const value of [null,undefined,'','1960junk',1960.5,true]) assert.equal(longevity.ageFromBirthYear(value,2026),null);
assert.equal(longevity.getRemainingLifeExpectancy('unknown',60),null);
// Legacy-load paths must clear missing dates rather than retain another case's date.
assert.match(fs.readFileSync(path.join(__dirname,'../retirement-plan/calculator.js'),'utf8'),/el\('birthDate'\)\.value = data\.birthDate == null \? '' : data\.birthDate/);
assert.match(fs.readFileSync(path.join(__dirname,'../rmd-impact/calculator.js'),'utf8'),/if \(!params\.has\('birthDate'\)\) document\.getElementById\('birthDate'\)\.value = ''/);

console.log('PASS Phase 3 statutory, longevity, survivor monthly-dollar, input-validation, inherited calculator, tax, and Survivor Gap fixtures.');
