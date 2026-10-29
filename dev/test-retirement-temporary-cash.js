'use strict';
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),path=require('node:path');
const root=process.argv[2]||path.resolve(__dirname,'..'),c=vm.createContext({console});
for(const file of ['js/lib/finance-core.js','js/lib/federal-tax-2026.js','js/lib/rmd-tax-core.js','retirement-plan/plan-engine.js','retirement-plan/monte-carlo-engine.js'])vm.runInContext(fs.readFileSync(path.join(root,file),'utf8'),c,{filename:file});
// Fictional fixture; the separate private example verification uses user inputs.
const base={currentAge:65,birthYear:1961,birthDate:'1961-01-01',retirementAge:65,planEndAge:91,balance:1200000,annualContribution:0,returnPreRetirement:7,returnRetirement:7,baseAnnualSpending:100000,ssAlreadyReceiving:true,ssCurrentMonthly:50000/12,ssPiaMonthly:0,ssClaimAge:66,spouseSsAlreadyReceiving:true,spouseSsCurrentMonthly:0,spouseSsMonthly:0,spouseSsClaimAge:67,otherGuaranteedAnnual:0,withdrawalRate:.04,inflation:2.5,colaRate:2.5,filingStatus:'married',taxDeferredPct:90,spouseIsBeneficiary:false,spouseAge:null,useStandardDeduction:true,portfolioWithdrawalStartAge:66};
let checks=0;
function near(a,b){checks++;assert(Math.abs(a-b)<1e-5,`${a} != ${b}`);}
function run(changes={}){return c.RBPlanEngine.runDeterministicPlan({...base,...changes});}
function mc(changes={},volatility=12){const inputs={...base,...changes};return c.RBMonteCarlo.runRetirementStressTest(inputs,c.RBPlanEngine.runDeterministicPlan(inputs),{expectedReturnPct:inputs.returnRetirement,volatilityPct:volatility,numSims:1000});}
const original=run();assert.equal(original.summary.shortfallAge,65);near(original.years[0].spendingShortfall,50000);near(mc().successRate,0);
assert(original.summary.endingBalance>base.balance,'A positive terminal portfolio does not erase a real early shortfall');
const frozen=Object.freeze({...base,temporaryCash:50000}),serialized=JSON.stringify(frozen);
const funded=c.RBPlanEngine.runDeterministicPlan(frozen);
assert.equal(JSON.stringify(frozen),serialized,'Calculation must not mutate saved inputs');
assert.equal(funded.summary.shortfallAge,null);near(funded.years[0].temporaryCashUsed,50000);
near(funded.years[0].spendingShortfall,0);near(funded.years[0].taxShortfall,0);near(funded.years[0].withdrawal,0);
near(funded.years[0].balanceEnd,1200000*1.07);near(funded.years[0].totalIncome,100000);
assert(funded.years[1].withdrawal>0);assert.equal(funded.years[1].age,66);
near(funded.summary.temporaryCashRemaining,0);near(funded.summary.temporaryCashUsed,50000);
near(funded.summary.endingBalance,original.summary.endingBalance);
near(funded.years[0].otherIncome,0);near(funded.years[0].federalTax,original.years[0].federalTax);
for(const row of funded.years){
 near(row.balanceStart+row.socialSecurity+row.otherIncome+(row.temporaryCashUsed||0)+row.investmentReturn-row.fundedSpending-row.taxesPaid,row.balanceEnd);
 if(row.age>=66)near(row.temporaryCashUsed,0);
}
const insufficient=run({temporaryCash:20000});near(insufficient.years[0].spendingShortfall,30000);assert.equal(insufficient.summary.shortfallAge,65);near(mc({temporaryCash:20000}).successRate,0);
const excess=run({temporaryCash:1000000});near(excess.summary.temporaryCashUsed,50000);near(excess.summary.temporaryCashRemaining,950000);near(excess.summary.endingBalance,funded.summary.endingBalance);
const extended=run({portfolioWithdrawalStartAge:68,temporaryCash:50000*(1+1.025)});assert.equal(extended.summary.shortfallAge,67);near(extended.summary.temporaryCashUsed,50000*(1+1.025));near(extended.years[2].temporaryCashRemaining,0);
const depleted=run({balance:1000,returnRetirement:0,taxDeferredPct:0,ssCurrentMonthly:0,baseAnnualSpending:10000,temporaryCash:10000,planEndAge:68});
assert.equal(depleted.summary.shortfallAge,66);assert.equal(depleted.summary.depletedAge,66);near(depleted.years[0].spendingShortfall,0);near(depleted.years[1].spendingShortfall,9250);
for(const changes of [{temporaryCash:50000},{temporaryCash:0},{temporaryCash:20000},{temporaryCash:1000000},{balance:1000,returnRetirement:0,taxDeferredPct:0,ssCurrentMonthly:0,baseAnnualSpending:10000,temporaryCash:10000,planEndAge:68}]){
 const d=run(changes),s=mc(changes,0);near(s.p50,d.summary.endingBalance);near(s.successRate,d.summary.shortfallAge===null?100:0);
}
assert.deepEqual(JSON.parse(JSON.stringify(run())),JSON.parse(JSON.stringify(run({temporaryCash:0}))),'Missing legacy field must mean zero cash');
for(const value of [-1,NaN,Infinity,'1000',1e12+1])assert.throws(()=>run({temporaryCash:value}),/Temporary cash/);
const stress=mc({temporaryCash:50000});assert(stress.successRate>0&&stress.successRate<100,'Funding a bridge must not hide later market risk');
const result={assumptions:'Age 65 in 2026; married jointly; combined Social Security already receiving; uniform-lifetime RMD; synthetic January birth date; fixed 2026 simplified federal tax model',temporaryCashRequired:50000,withoutCash:{successRate:0,shortfallAge:original.summary.shortfallAge,endingBalance:original.summary.endingBalance},withCash:{successRate:stress.successRate,shortfallAge:funded.summary.shortfallAge,endingBalance:funded.summary.endingBalance,medianMonteCarloEnding:stress.p50},checks};
console.log(JSON.stringify(result,null,2));
