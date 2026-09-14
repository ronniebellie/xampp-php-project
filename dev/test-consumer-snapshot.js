'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm'),path=require('node:path');
const root=path.resolve(__dirname,'..'),c=vm.createContext({console});
for(const f of ['js/lib/finance-core.js','js/lib/federal-tax-2026.js','js/lib/rmd-tax-core.js','retirement-plan/plan-engine.js','retirement-plan/monte-carlo-engine.js'])vm.runInContext(fs.readFileSync(path.join(root,f),'utf8'),c);
const base={currentAge:66,birthYear:1960,birthDate:'1960-06-15',retirementAge:67,planEndAge:85,balance:1000000,annualContribution:0,returnPreRetirement:0,returnRetirement:0,baseAnnualSpending:50000,ssAlreadyReceiving:false,ssCurrentMonthly:0,ssPiaMonthly:1000,ssClaimAge:67,spouseSsAlreadyReceiving:false,spouseSsCurrentMonthly:0,spouseSsMonthly:2000,spouseSsClaimAge:67,otherGuaranteedAnnual:0,withdrawalRate:.04,inflation:0,colaRate:0,filingStatus:'married',taxDeferredPct:0,spouseIsBeneficiary:false,spouseAge:56,useStandardDeduction:true,portfolioWithdrawalStartAge:67};
const run=o=>c.RBPlanEngine.runDeterministicPlan({...base,...o}); let checks=0;
function eq(a,b){checks++;assert(Number.isFinite(a)&&Math.abs(a-b)<1e-6,`${a} != ${b}`);}
const p=run({});eq(p.years.find(r=>r.age===67).socialSecurity,12000);eq(p.summary.targetNestEgg,950000);eq(950000-350000,600000);
eq(p.summary.incomeTimeline.availableNow,0);eq(p.summary.incomeTimeline.atRetirement,12000);eq(p.summary.incomeTimeline.fullyStartedAge,77);eq(p.summary.incomeTimeline.longRunTarget,350000);
for(const r of p.years.filter(r=>r.phase==='retirement')){eq(r.socialSecurity,r.age<77?12000:36000);eq(r.balanceStart+r.socialSecurity+r.otherIncome+r.investmentReturn-r.fundedSpending-r.taxesPaid,r.balanceEnd);}
eq(p.years.find(r=>r.age===66).contribution,0);eq(p.years.find(r=>r.age===67).withdrawal,38000);
for(const ageDiff of [-5,0,5,10])for(const claim of [62,67,70]){
 const age=66-ageDiff, q=run({spouseAge:age,spouseSsClaimAge:claim});
 const birth=2026-age; const fraMonths=birth===1955?794:birth===1956?796:birth===1957?798:birth===1958?800:birth===1959?802:804;
 const diff=claim*12-fraMonths; const factor=diff>=0?1+diff/150:1-Math.min(-diff,36)/180-Math.max(0,-diff-36)/240;
 for(const r of q.years.filter(r=>r.phase==='retirement')) {
  const spouseNow=age+r.age-66; const self=r.age>=67?12000:0;
  eq(r.socialSecurity,self+(spouseNow>=claim?2000*factor*12:0));
 }
}
const delayed=run({spouseAge:66,spouseSsClaimAge:70});eq(delayed.years.find(r=>r.age===70).socialSecurity,41760);eq(delayed.years.find(r=>r.age===69).socialSecurity,12000);
const both=run({ssClaimAge:70,spouseAge:66,spouseSsClaimAge:70});eq(both.years.find(r=>r.age===70).socialSecurity,44640);eq(both.summary.targetNestEgg,1250000);
const pension=run({spouseSsMonthly:0,otherGuaranteedAnnual:24000,otherIncomeStartAge:70});eq(pension.summary.targetNestEgg,950000);eq(pension.years.find(r=>r.age===69).otherIncome,0);eq(pension.years.find(r=>r.age===70).otherIncome,24000);eq(pension.summary.incomeTimeline.longRunTarget,350000);
const lateDraw=run({portfolioWithdrawalStartAge:77});eq(lateDraw.summary.targetNestEgg,950000);assert(lateDraw.summary.incomeTimeline.bridgeShortfalls>0);
const receiving=run({ssAlreadyReceiving:true,ssCurrentMonthly:1100,spouseSsAlreadyReceiving:true,spouseSsCurrentMonthly:2200,spouseAge:null,colaRate:2});eq(receiving.summary.incomeTimeline.availableNow,39600);eq(receiving.summary.incomeTimeline.atRetirement,39600*1.02);
const outside=run({spouseAge:30});assert.equal(outside.summary.incomeTimeline.longRunTarget,null);eq(outside.summary.targetNestEgg,950000);
for(const o of [{spouseAge:null},{spouseSsClaimAge:NaN},{planEndAge:Infinity},{retirementAge:67.5},{returnRetirement:NaN},{otherIncomeStartAge:NaN}])assert.throws(()=>run(o));
const serialized=JSON.parse(JSON.stringify({...base,otherIncomeStartAge:73,otherGuaranteedAnnual:12000}));assert.deepEqual(JSON.parse(JSON.stringify(run(serialized))),JSON.parse(JSON.stringify(run({...serialized}))));
const mc=c.RBMonteCarlo.runRetirementStressTest(base,p,{volatilityPct:0,numSims:100,expectedReturnPct:0});eq(mc.p50,p.summary.endingBalance);eq(mc.successRate,p.summary.shortfallAge===null?100:0);
const urls=vm.createContext({RBUrlPrefill:{buildUrl:(route,data)=>({route,data})}});vm.runInContext(fs.readFileSync(path.join(root,'retirement-plan/deep-links.js'),'utf8'),urls);
const links=urls.RBDeepLinks.buildDeepDiveLinks(base,p);eq(links.deepLinkRmd.data.socialSecurity,0);eq(links.deepLinkSpending.data.guaranteedMonthlyIncome,0);
console.log(`Consumer snapshot: ${checks} independent numeric checks plus domain, reload, MC and deep-link boundaries passed.`);

if (process.env.RB_SNAPSHOT_QA_INPUT) fs.writeFileSync(process.env.RB_SNAPSHOT_QA_INPUT, JSON.stringify({inputs:base,summary:{...p.summary,statusHeadline:p.summary.status.headline,statusDetail:p.summary.status.detail,guaranteedIncomeAtRetirement:12000,incomeTimingText:'Available now: $0/year. At retirement start: $12,000/year. Spouse begins at primary age 77. Bridge-period income gap: $38,000/year before tax. Retirement-start target: $950,000; later target: $350,000. These are different-date targets, not additive.'},projections:p.years}));

// Actual form save/load adapters: retired does not imply receiving either benefit.
const formNodes={}, checkboxIds=['alreadyRetired','ssAlreadyReceiving','spouseSsAlreadyReceiving'];
function node(id){return formNodes[id] ||= {value:'0',type:checkboxIds.includes(id)?'checkbox':'number',tagName:'INPUT',checked:false,dataset:{},style:{},classList:{add(){},remove(){},toggle(){}},closest(){return null;}};}
const radio={value:'direct',checked:true};
const formContext={window:null,Date,Number,console,document:{getElementById:node,querySelector(selector){return selector.includes('spendingMethod')?radio:null;},addEventListener(){},body:{classList:{toggle(){}}}}};
formContext.window=formContext;formContext.location={pathname:'/retirement-plan/',origin:'https://offline.invalid'};
for(const name of ['RBFinance','RBPlanEngine','RBMonteCarlo'])formContext[name]=c[name];
vm.createContext(formContext);
vm.runInContext(fs.readFileSync(path.join(root,'retirement-plan/calculator.js'),'utf8').replace("  document.addEventListener('DOMContentLoaded'", "  window.formTest={collect:collectInputs,save:getFormDataForSave,load:applyFormData};\n  document.addEventListener('DOMContentLoaded'"),formContext);
const data={birthYear:new Date().getFullYear()-66,birthDate:(new Date().getFullYear()-66)+'-06-15',retirementAge:66,alreadyRetired:true,balance:1000000,portfolioWithdrawalStartAge:66,annualContribution:0,returnPreRetirement:0,returnRetirement:0,spendingMethod:'direct',annualSpendingDirect:50000,currentMonthlySpending:0,retirementSpendingPct:100,ssAlreadyReceiving:false,ssCurrentMonthly:0,ssPiaMonthly:1000,ssClaimAge:70,spouseSsAlreadyReceiving:false,spouseSsCurrentMonthly:0,spouseSsMonthly:2000,spouseSsClaimAge:70,spouseAge:56,spouseBeneficiary:'no',otherGuaranteedAnnual:24000,otherIncomeStartAge:73,planEndAge:85,withdrawalRate:4,inflation:0,colaRate:0,filingStatus:'married',taxDeferredPct:0,volatility:0,simulations:100};
for(const id of ['ssClaimAge','spouseSsClaimAge','filingStatus','spouseBeneficiary'])node(id).tagName='SELECT';
formContext.formTest.load(data);let collected=formContext.formTest.collect();
assert.equal(collected.ssAlreadyReceiving,false);assert.equal(collected.spouseSsAlreadyReceiving,false);assert.equal(collected.otherIncomeStartAge,73);
const first=c.RBPlanEngine.runDeterministicPlan(collected);eq(first.years[0].socialSecurity,0);eq(first.summary.targetNestEgg,1250000);
const saved=JSON.parse(JSON.stringify(formContext.formTest.save()));node('spouseAge').value=90;node('spouseSsAlreadyReceiving').checked=true;formContext.formTest.load(saved);collected=formContext.formTest.collect();
assert.deepEqual(JSON.parse(JSON.stringify(c.RBPlanEngine.runDeterministicPlan(collected))),JSON.parse(JSON.stringify(first)));
node('withdrawalRate').value=0;assert.equal(formContext.formTest.collect().withdrawalRate,0);assert.throws(()=>c.RBPlanEngine.runDeterministicPlan(formContext.formTest.collect()));
node('withdrawalRate').value=4;node('spouseSsClaimAge').value=67.5;assert.throws(()=>c.RBPlanEngine.runDeterministicPlan(formContext.formTest.collect()));
console.log('Actual snapshot form retirement, save/reload, zero-rate and fractional-claim checks passed.');
