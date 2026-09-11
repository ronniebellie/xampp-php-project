'use strict';
// Independent cash-ledger identities and hand-calculated fixtures. No network/browser.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const roth = require('../roth-conv/engine.js');
let checks = 0;
function close(a,b,label,tolerance=1e-5) {checks++;assert(Number.isFinite(a)&&Math.abs(a-b)<=tolerance,`${label}: ${a} != ${b}`);}
function test(name,fn) {fn();console.log('PASS '+name);}
const rBase = {currentAge:60,lifeExpectancy:60,filingStatus:'single',traditionalIRA:200000,rothIRA:0,
 taxableAccount:100000,taxableCostBasis:100000,otherOrdinaryIncome:0,socialSecuritySelf:0,
 conversionAmount:100000,conversionYears:1,targetAfterTaxSpending:0,withdrawalMode:'target_after_tax',
 returnRate:0,taxableReturnRate:0,inflationRate:0,includeIrmaa:false,includeNiit:false,taxPaymentSource:'taxable'};
function rRow(overrides={}) {return roth.runRothAnalysis({...rBase,...overrides}).withConversion.yearlyData[0];}
function rothLedger(result) {
 for(const scenario of [result.withConversion,result.withoutConversion]) {
  for(const row of scenario.yearlyData) {
   close(row.beginningAssets+row.externalIncome+row.investmentReturn-row.fundedSpending-row.taxesPaid,
    row.traditionalBalance+row.rothBalance+row.taxableBalance,'Roth cash conservation');
   close(row.requestedSpending,row.fundedSpending+row.spendingShortfall,'requested != funded+gap');
   close(Object.values(row.taxFunding).reduce((s,v)=>s+v,0),row.taxesPaid,'tax sources');
   close(row.allInTax,row.taxesPaid+row.taxShortfall,'tax liability funding');
   for(const key of ['traditionalBalance','rothBalance','taxableBalance','fundedSpending']) assert(row[key]>=-1e-7);
  }
  close(scenario.totalSpending,scenario.yearlyData.reduce((s,r)=>s+r.fundedSpending,0),'spending total');
  close(scenario.totalTaxesPaid,scenario.yearlyData.reduce((s,r)=>s+r.taxesPaid,0),'paid tax total');
  close(scenario.finalTraditionalBalance,scenario.yearlyData.at(-1).traditionalBalance,'Roth endpoint');
 }
}
test('Roth $100k conversion: brokerage pays $13,170, no assets created',()=>{
 const r=rRow();close(r.federalTax,13170,'1240 + 4560 + 7370');close(r.taxableBalance,86830,'brokerage');
 close(r.traditionalBalance,100000,'traditional transfer');close(r.rothBalance,100000,'Roth transfer');close(r.taxFunding.taxable,13170,'funding');
});
test('Roth traditional tax funding includes tax on the tax withdrawal',()=>{
 const r=rRow({taxPaymentSource:'traditional'});const tax=13170/0.78;
 close(r.federalTax,tax,'22% bracket gross-up');close(r.traditionalWithdrawal,tax,'identified tax withdrawal');
 close(r.traditionalBalance,100000-tax,'traditional ending');close(r.rothBalance,100000,'Roth transfer');close(r.taxableBalance,100000,'brokerage untouched');
});
test('Roth taxable gains also gross up taxes',()=>{
 // Conversion ordinary income fills the zero-gain band; brokerage sale taxed at 15%.
 const r=rRow({taxableCostBasis:0});close(r.taxesPaid,13170/0.85,'capital gains gross-up');
 close(r.taxFunding.taxable,r.taxesPaid,'brokerage source');
});
test('Roth exhausted brokerage uses identified fallback, never free tax',()=>{
 const r=rRow({taxableAccount:0,taxableCostBasis:0,traditionalIRA:100000});
 close(r.rothBalance,86830,'tax taken from converted Roth');close(r.taxFunding.roth,13170,'fallback');
});
test('Roth shortfall, unpaid tax, income surplus and zero spending',()=>{
 const r=rRow({traditionalIRA:1000,taxableAccount:0,conversionAmount:0,targetAfterTaxSpending:10000});
 close(r.fundedSpending,1000,'funded');close(r.spendingShortfall,9000,'gap');
 const tax=rRow({traditionalIRA:0,taxableAccount:0,conversionAmount:0,otherOrdinaryIncome:200000,targetAfterTaxSpending:300000});
 assert(tax.spendingShortfall>100000);close(tax.taxesPaid,tax.federalTax,'income pays tax first');
 const surplus=rRow({traditionalIRA:0,taxableAccount:0,conversionAmount:0,otherOrdinaryIncome:20000,targetAfterTaxSpending:0,annualPortfolioWithdrawalRate:4});
 close(surplus.taxableBalance,19610,'income less $390 tax saved');close(surplus.requestedSpending,0,'zero target');
});
test('Roth prior conversion creates explicit unpaid IRMAA after depletion',()=>{
 const result=roth.runRothAnalysis({...rBase,currentAge:65,lifeExpectancy:67,traditionalIRA:1000000,
 taxableAccount:0,conversionAmount:1000000,targetAfterTaxSpending:1000000,includeIrmaa:true});
 const last=result.withConversion.yearlyData.at(-1);
 assert(last.taxShortfall>0);close(last.taxesPaid,0,'unfunded tax not reported paid');
 close(last.fundedSpending,0,'depleted spending');rothLedger(result);
});
test('Roth multiyear account invariants across tax sources, orders, RMDs and depletion',()=>{
 for(const taxPaymentSource of ['taxable','traditional','roth']) for(const withdrawalOrder of ['traditional_then_roth','roth_then_traditional','traditional_to_bracket_then_roth']) {
  rothLedger(roth.runRothAnalysis({...rBase,currentAge:73,lifeExpectancy:90,traditionalIRA:200000,
   rothIRA:3000,taxableCostBasis:20000,socialSecuritySelf:12000,otherOrdinaryIncome:2000,
   taxExemptInterest:500,annualLongTermGains:100,returnRate:3,taxableReturnRate:2,inflationRate:2,
   targetAfterTaxSpending:50000,conversionYears:4,includeIrmaa:true,includeNiit:true,taxPaymentSource,withdrawalOrder}));
 }
});
const context=vm.createContext({console});
for(const file of ['js/lib/finance-core.js','js/lib/federal-tax-2026.js','js/lib/rmd-tax-core.js','retirement-plan/plan-engine.js','retirement-plan/monte-carlo-engine.js']) vm.runInContext(fs.readFileSync(path.join(root,file),'utf8'),context,{filename:file});
const pBase={currentAge:60,retirementAge:60,planEndAge:60,birthYear:1966,balance:100000,annualContribution:0,
 returnPreRetirement:0,returnRetirement:0,baseAnnualSpending:10000,ssAlreadyReceiving:true,ssCurrentMonthly:0,
 ssClaimAge:67,spouseSsMonthly:0,otherGuaranteedAnnual:0,withdrawalRate:.04,inflation:0,colaRate:0,
 filingStatus:'single',taxDeferredPct:100,useStandardDeduction:false};
const plan=(changes={})=>context.RBPlanEngine.runDeterministicPlan({...pBase,...changes});
function planLedger(p) {
 for(const r of p.years) {
  close(r.balanceStart+r.socialSecurity+r.otherIncome+r.contribution+r.investmentReturn-r.fundedSpending-r.taxesPaid,r.balanceEnd,'Plan cash conservation');
  close(r.traditionalBalance+r.otherAssetsBalance,r.balanceEnd,'actual account split');
  close(r.requestedSpending,r.fundedSpending+r.spendingShortfall,'Plan spending');
  close(r.federalTax,r.taxesPaid+r.taxShortfall,'Plan tax funding');
  if(r.taxFunding) close(Object.values(r.taxFunding).reduce((s,v)=>s+v,0),r.taxesPaid,'Plan tax sources');
 }
 close(p.summary.endingBalance,p.years.at(-1).balanceEnd,'Plan ending summary');
 close(p.summary.lifetimeFederalTax,p.years.reduce((s,r)=>s+r.taxesPaid,0),'Plan tax summary');
}
test('Plan funds $10k spending with $11,111.11 traditional withdrawal at 10%',()=>{
 const p=plan();const r=p.years[0];close(r.withdrawal,10000/.9,'gross withdrawal');close(r.taxesPaid,10000/9,'tax');close(r.balanceEnd,100000-10000/.9,'ending');planLedger(p);
});
test('Plan only taxes traditional draws, retains account source across years',()=>{
 const p=plan({taxDeferredPct:50,planEndAge:66});
 for(const r of p.years.slice(0,5)) {close(r.taxesPaid,0,'other assets untaxed');close(r.traditionalBalance,50000,'traditional preserved');}
 close(p.years[5].traditionalWithdrawal,10000/.9,'traditional after other exhausted');planLedger(p);
});
test('Plan excess RMD is saved after tax; no wealth disappears',()=>{
 const p=plan({currentAge:73,retirementAge:73,planEndAge:73,birthYear:1953,balance:265000,baseAnnualSpending:0});const r=p.years[0];
 close(r.rmd,10000,'RMD');close(r.taxesPaid,1000,'RMD tax');close(r.surplus,9000,'saved excess');close(r.balanceEnd,264000,'wealth less tax only');planLedger(p);
});
test('Plan delayed withdrawals report a gap even with assets remaining',()=>{
 const p=plan({portfolioWithdrawalStartAge:62,planEndAge:63});close(p.years[0].fundedSpending,0,'delayed');close(p.years[0].spendingShortfall,10000,'gap');assert.equal(p.summary.shortfallAge,60);assert.equal(p.summary.status.code,'shortfall');planLedger(p);
});
test('Plan terminal depletion and exact final spending are distinct from failure',()=>{
 const exact=plan({balance:10000,taxDeferredPct:0});close(exact.summary.endingBalance,0,'terminal');assert.equal(exact.summary.depletedAge,60);assert.equal(exact.summary.shortfallAge,null);
 const gap=plan({balance:5000,taxDeferredPct:0});close(gap.years[0].fundedSpending,5000,'funded');close(gap.years[0].spendingShortfall,5000,'gap');assert.equal(gap.summary.depletedAge,60);
});
test('Plan accumulation contributions and income-surplus cash ledger',()=>{
 planLedger(plan({currentAge:55,retirementAge:60,planEndAge:85,annualContribution:1000,returnPreRetirement:4,returnRetirement:3,taxDeferredPct:70,ssCurrentMonthly:2000}));
});
test('Zero volatility aligns MC with deterministic cash flows, including delayed/failing plans',()=>{
 for(const changes of [{},{returnRetirement:4},{portfolioWithdrawalStartAge:63},{balance:5000},{taxDeferredPct:0,balance:10000},{currentAge:73,retirementAge:73,planEndAge:80,baseAnnualSpending:0},{currentAge:55,retirementAge:60,annualContribution:1000}]) {
  const input={...pBase,planEndAge:70,...changes};const d=context.RBPlanEngine.runDeterministicPlan(input);
  const mc=context.RBMonteCarlo.runRetirementStressTest(input,d,{expectedReturnPct:input.returnRetirement,volatilityPct:0,numSims:100});
  close(mc.p25,d.summary.endingBalance,'MC p25');close(mc.p50,d.summary.endingBalance,'MC p50');close(mc.p75,d.summary.endingBalance,'MC p75');
  close(mc.successRate,d.summary.shortfallAge===null?100:0,'MC success');close(mc.volatilityPct,0,'zero volatility');
 }
 const input={...pBase,returnRetirement:8};const d=plan({returnRetirement:8});
 const mc=context.RBMonteCarlo.runRetirementStressTest(input,d,{expectedReturnPct:0,volatilityPct:0,numSims:100});
 close(mc.p50,plan().summary.endingBalance,'explicit zero overrides nonzero return');close(mc.expectedReturnPct,0,'zero expected return');
});
test('MC retains terminal balances after early shortfalls and caps losses at available assets',()=>{
 const input={...pBase,planEndAge:80,balance:1000,returnRetirement:0};
 const mc=context.RBMonteCarlo.runRetirementStressTest(input,plan(input),{expectedReturnPct:-100,volatilityPct:0,numSims:100});
 close(mc.successRate,0,'failed plan');assert(mc.endingBalances.every(v=>v===0));
 close(mc.histogram.counts.reduce((a,b)=>a+b,0),100,'histogram simulation count');
});
// Exercise PAS calculation, rendered summaries, chart datasets and export payloads in
// a local DOM stub. fetch never performs I/O; it only captures the export payload.
const nodes={},charts={},payloads=[];
const values={portfolioValue:100000,pasFee:1,targetDateFee:0,years:1,returnRate:0,withdrawalPct:0,timelineStartYear:2026,withdrawalsStartYear:2026,pctConservative:0,pctModerate:100,pctAggressive:0};
const doc={getElementById(id){return nodes[id]||=( {value:String(values[id]??0),style:{},events:{},addEventListener(k,fn){this.events[k]=fn;},getContext(){return id;},scrollIntoView(){}});},addEventListener(k,fn){if(k==='DOMContentLoaded')fn();}};
const stub={document:doc,Intl,Date,console,location:{pathname:'/vanguard-pas-vs-target-date/',origin:'http://local.test'},addEventListener(){},
 Chart:function(id,config){charts[id]=config;this.destroy=()=>{};},fetch(url,opts){payloads.push(JSON.parse(opts.body));return {then(){return this;},catch(){return this;},finally(){return this;}};}};
stub.window=stub;vm.runInNewContext(fs.readFileSync(path.join(root,'vanguard-pas-vs-target-date/calculator.js'),'utf8'),stub);
function pas(changes={}) {for(const [k,v] of Object.entries({...values,...changes}))doc.getElementById(k).value=String(v);nodes.calculateBtn.events.click();return stub.lastPASvsTargetResult;}
test('PAS $100k, zero return, one year: $99k vs $100k everywhere',()=>{
 const r=pas();close(r.pasFinal,99000,'PAS ending');close(r.targetFinal,100000,'no fee ending');close(r.opportunityCost,1000,'comparison');
 assert.equal(nodes.pasFinalValue.textContent,'$99,000');assert.equal(nodes.targetFinalValue.textContent,'$100,000');
 close(charts.growthChart.data.datasets[0].data.at(-1),99000,'chart endpoint');close(charts.growthChart.data.datasets[1].data.at(-1),100000,'chart comparison endpoint');
 nodes.downloadPdfBtn.events.click();nodes.downloadCsvBtn.events.click();
 for(const payload of payloads) {close(payload.pasData.at(-1).balance,99000,'export PAS');close(payload.targetData.at(-1).balance,100000,'export target');}
 close(payloads[0].pasFinal,99000,'PDF summary');
});
test('PAS fees and final withdrawals reconcile each year and cumulative totals',()=>{
 const r=pas({years:3,withdrawalPct:10});close(r.pasData[1].balance,89000,'first net');close(r.pasFinal,70496.9,'three net years');
 for(const series of [r.pasData,r.targetData]) {
  for(let i=1;i<series.length;i++)close(series[i-1].balance-series[i].fee-series[i].withdrawal,series[i].balance,'PAS ledger');
  close(series.at(-1).totalFees,series.reduce((s,r)=>s+r.fee,0),'fees total');close(series.at(-1).totalWithdrawals,series.reduce((s,r)=>s+r.withdrawal,0),'withdrawal total');
 }
 const growth=pas({years:2,returnRate:10,withdrawalPct:4});close(growth.pasData[1].balance,104500,'growth then fees/withdrawal');close(growth.pasFinal,109202.5,'second year net');
});
test('Retirement rendered table, summary, chart and exports agree on depleted cash flow',()=>{
 const uiNodes={},uiCharts={},exports=[];let csv='';
 const uiDoc={getElementById(id){return uiNodes[id]||={id,style:{},getContext(){return id;}};},
  addEventListener(){},createElement(){return {click(){}};}};
 const ui={window:null,document:uiDoc,console,isPremiumUser:true,RBFinance:context.RBFinance,RBPlanEngine:context.RBPlanEngine,
  location:{pathname:'/retirement-plan/',origin:'http://local.test'},
  Chart:function(node,config){uiCharts[node.id||node]=config;this.destroy=()=>{};},
  Blob:function(parts){csv=parts.join('');},URL:{createObjectURL(){return 'local';}},
  fetch(url,opts){exports.push(JSON.parse(opts.body));return {then(){return this;},catch(){return this;},finally(){return this;}};}};
 ui.window=ui;
 let source=fs.readFileSync(path.join(root,'retirement-plan/calculator.js'),'utf8');
 // Expose existing closure functions only in this VM, without changing production code.
 source=source.replace(/\}\)\(\);\s*$/, `window.exercise=function(result,inputs){lastResult=result;lastInputs=inputs;renderSummary(result);renderChart(result);renderFullTable(result);exportCsv();downloadPdf();};})();`);
 vm.runInNewContext(source,ui);
 const input={...pBase,taxDeferredPct:0,balance:5000};const result=plan(input);ui.exercise(result,input);
 assert.equal(uiNodes.metricProjected.textContent,'$0');assert(uiNodes.fullTableBody.innerHTML.includes('$10,000 / $5,000'));
 close(uiCharts.planChart.data.datasets[0].data.at(-1),0,'Plan chart terminal');
 const lines=csv.trim().split('\n').map(s=>s.split(','));
 close(Number(lines[1][lines[0].indexOf('Funded Spending')]),5000,'Plan CSV funded');
 close(Number(lines[1][lines[0].indexOf('Spending Shortfall')]),5000,'Plan CSV shortfall');
 close(exports[0].projections[0].fundedSpending,5000,'Plan PDF funded');close(exports[0].projections[0].balanceEnd,0,'Plan PDF endpoint');
});
test('Roth table and chart report funded spending and matching account endpoints',()=>{
 const uiCharts={};const ui={console,window:null,location:{pathname:'/roth-conv/',origin:'http://local.test'},
 document:{getElementById(id){return {id};},addEventListener(){}},
 Chart:function(node,config){uiCharts[node.id]=config;this.destroy=()=>{};}};ui.window=ui;
 vm.runInNewContext(fs.readFileSync(path.join(root,'roth-conv/calculator.js'),'utf8'),ui);
 const result=roth.runRothAnalysis({...rBase,traditionalIRA:1000,taxableAccount:0,conversionAmount:0,targetAfterTaxSpending:10000});
 const html=ui.generateTableRows(result.withConversion.yearlyData,false,false,false);
 assert(html.includes('$1,000'));assert(html.includes('$10,000'));assert(html.includes('$9,000'));
 ui.createAccountBalanceChart(result);close(uiCharts.accountBalanceChart.data.datasets[0].data.at(-1),0,'Roth account chart terminal');
 ui.createCumulativeTaxChart(result);close(uiCharts.cumulativeTaxChart.data.datasets[0].data.at(-1),result.withConversion.totalTaxesPaid,'Roth cumulative summary');
});
console.log(`Phase 2 cash-flow tests passed (${checks} numeric assertions plus behavioral checks)`);
