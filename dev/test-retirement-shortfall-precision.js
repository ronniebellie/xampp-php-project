'use strict';
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),assert=require('node:assert/strict');
const root=process.argv[2]||path.resolve(__dirname,'..'),engines=vm.createContext({console});
for(const f of ['js/lib/finance-core.js','js/lib/federal-tax-2026.js','js/lib/rmd-tax-core.js','retirement-plan/plan-engine.js','retirement-plan/monte-carlo-engine.js'])vm.runInContext(fs.readFileSync(path.join(root,f),'utf8'),engines);
// Fictional defaults; a private JSON override exercises exact user inputs
// without committing personal financial circumstances to the public repository.
const inputs={currentAge:65,birthYear:1961,birthDate:'1961-01-01',retirementAge:65,planEndAge:91,balance:1200000,annualContribution:0,returnPreRetirement:5,returnRetirement:5,baseAnnualSpending:100000,ssAlreadyReceiving:true,ssCurrentMonthly:49999.60/12,ssPiaMonthly:0,ssClaimAge:66,spouseSsAlreadyReceiving:true,spouseSsCurrentMonthly:0,spouseSsMonthly:0,spouseSsClaimAge:67,otherGuaranteedAnnual:0,withdrawalRate:.04,inflation:2.5,colaRate:2.5,filingStatus:'married',taxDeferredPct:90,spouseIsBeneficiary:false,spouseAge:null,useStandardDeduction:true,portfolioWithdrawalStartAge:66,temporaryCash:50000,volatilityPct:10,numSims:1000,...(process.env.RB_RETIREMENT_PRECISION_INPUT?JSON.parse(fs.readFileSync(process.env.RB_RETIREMENT_PRECISION_INPUT,'utf8')):{})};
const run=x=>engines.RBPlanEngine.runDeterministicPlan(x);
const bad=run(inputs),missing=bad.years[0].spendingShortfall;
assert(Math.abs(missing-.40)<1e-6);assert.equal(bad.summary.shortfallAge,inputs.currentAge);
const actualShortfalls=bad.years.filter(y=>y.spendingShortfall>1e-6||y.taxShortfall>1e-6);
assert.equal(actualShortfalls.length,1,'No hidden later annual shortfalls');assert.equal(bad.summary.totalTaxShortfall,0);
const fullyFunded={...inputs,temporaryCash:Math.round((inputs.baseAnnualSpending-inputs.ssCurrentMonthly*12)*100)/100};
const good=run(fullyFunded);assert.equal(good.summary.shortfallAge,null);assert.equal(good.summary.endingBalance,bad.summary.endingBalance);
assert.equal(bad.years[0].withdrawal,0);assert(bad.years[1].withdrawal>0);
if(inputs.expectedFirstWithdrawal!=null)assert(Math.abs(bad.years[1].withdrawal-inputs.expectedFirstWithdrawal)<1e-5);
if(inputs.expectedEndingBalance!=null)assert(Math.abs(good.summary.endingBalance-inputs.expectedEndingBalance)<.00001);
const nodes={},pdfPayloads=[];let csv='';
const node=id=>nodes[id]||=( {id,style:{},textContent:'',innerHTML:'',scrollIntoView(){}} );
const ui={console,isPremiumUser:true,location:{pathname:'/retirement-plan/',origin:'https://synthetic.invalid'},document:{getElementById:node,addEventListener(){},createElement(){return {click(){}};}},
 RBFinance:engines.RBFinance,RBPlanEngine:engines.RBPlanEngine,RBMonteCarlo:engines.RBMonteCarlo,
 Blob:function(parts){csv=parts.join('');},URL:{createObjectURL(){return 'synthetic';}},fetch(url,opts){pdfPayloads.push(JSON.parse(opts.body));return {then(){return this;},catch(){return this;},finally(){return this;}};}};
ui.window=ui;
let source=fs.readFileSync(path.join(root,'retirement-plan/calculator.js'),'utf8');
source=source.replace(/\}\)\(\);\s*$/, 'window.precisionTest={display:displayResults,row:rowHtml,csv:exportCsv,pdf:downloadPdf};})();');
vm.runInNewContext(source,ui);
ui.precisionTest.display(bad,inputs);
assert(nodes.fullTableBody.innerHTML.includes('$0.40'),'Timeline must show the real shortfall instead of $0');
assert(nodes.fullTableBody.innerHTML.includes('$111,599.60')||!process.env.RB_RETIREMENT_PRECISION_INPUT);
assert(nodes.statusDetail.textContent.includes('$0.40'),'Summary must explain the precise first shortfall');
assert.equal(nodes.mcSuccessRate.textContent,'0%','A real forty-cent shortfall must still fail');
assert(ui.lastRetirementPlanResult.summary.includes('$0.40'),'Copyable summary must preserve the amount');
ui.precisionTest.csv();ui.precisionTest.pdf();
const lines=csv.trim().split('\n'),headerIndex=lines.findIndex(x=>x.startsWith('Age,Portfolio,')),headers=lines[headerIndex].split(','),first=lines[headerIndex+1].split(',');
assert.equal(Number(first[headers.indexOf('Spending Shortfall')]),.40,'CSV must not round a real shortfall to zero');
assert(pdfPayloads.at(-1).summary.statusDetail.includes('$0.40'),'PDF summary must preserve exact shortfall');
ui.precisionTest.display(good,fullyFunded);
assert(!nodes.statusHeadline.textContent.includes('Unfunded spending or taxes'));
assert(!nodes.statusDetail.textContent.includes('First unfunded'));
const success=parseFloat(nodes.mcSuccessRate.textContent);assert(success>0&&success<100);
if(inputs.expectedFundedSuccessRate!=null)assert.equal(success,inputs.expectedFundedSuccessRate);
ui.precisionTest.display(bad,inputs);assert.equal(nodes.mcSuccessRate.textContent,'0%');assert(nodes.statusDetail.textContent.includes('$0.40'),'Bad-good-bad transitions must not reuse stale summaries');
assert(ui.precisionTest.row({...bad.years[0],spendingShortfall:.0001,taxShortfall:0}).includes('Under $0.01'),'Sub-cent genuine shortfalls must remain visible');
assert(!ui.precisionTest.row({...bad.years[0],spendingShortfall:5e-8,taxShortfall:0}).includes('Under $0.01'),'Existing numerical tolerance remains unchanged');
assert(ui.precisionTest.row({...bad.years[0],spendingShortfall:0,taxShortfall:.4}).includes('$0.40'),'Tax shortfalls must retain cents');
const zero=engines.RBMonteCarlo.runRetirementStressTest(fullyFunded,good,{expectedReturnPct:5,volatilityPct:0,numSims:1000});assert.equal(zero.successRate,100);assert(Math.abs(zero.p50-good.summary.endingBalance)<1e-5);
console.log(JSON.stringify({firstShortfall:missing,onlyShortfallAge:bad.summary.shortfallAge,endingPortfolio:bad.summary.endingBalance,withOriginalCashSuccess:0,fullyFundedCash:fullyFunded.temporaryCash,fullyFundedSuccess:success,zeroVolatilitySuccess:100,timeline_summary_csv_pdf_and_state_checks:'passed'},null,2));
