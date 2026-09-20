'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const nodes = {}, charts = {}, payloads = [];
let scenarios = [], choice = '1', alerts=[], finishedCharts=[];
const defaults = {withdrawalModel:"dollar",pasFundExpense:.08,annualWithdrawal:60000,inflation:2.5,portfolioValue:100000,pasFee:0.3,targetDateFee:0.08,years:30,returnRate:6,withdrawalPct:4.5,timelineStartYear:2026,withdrawalsStartYear:2026,pctConservative:20,pctModerate:30,pctAggressive:50};
const doc = {getElementById(id) {return nodes[id] ||= {value:String(defaults[id] ?? 0),style:{},events:{},addEventListener(k,f){this.events[k]=f;},toDataURL(){return "data:image/png;base64,test";},getContext(){return id;},scrollIntoView(){}};},addEventListener(k,f){if(k==='DOMContentLoaded')f();}};
const ctx = {document:doc,Intl,Date,console,Number,location:{pathname:'/vanguard-pas-vs-target-date/',origin:'http://test.local'},addEventListener(){},alert(message){alerts.push(message);},prompt(){return choice;},setTimeout(){},Chart:function(id,c){charts[id]=c;this.destroy=()=>{};this.stop=()=>{};this.update=mode=>{assert.equal(mode,"none");finishedCharts.push(id);};},fetch(url,opts){if(opts && opts.body){payloads.push(JSON.parse(opts.body));return {then(){return this;},catch(){return this;}};}return Promise.resolve({json:()=>Promise.resolve({success:true,scenarios})});},rbScenarioFetch(url,opts){payloads.push(JSON.parse(opts.body));return Promise.resolve({ok:true,status:200,text:()=>Promise.resolve('{}')});}};
ctx.window=ctx;ctx.rbExplainFetch=ctx.fetch;
vm.runInNewContext(fs.readFileSync(path.join(root,'vanguard-pas-vs-target-date/calculator.js'),'utf8'),ctx);
let checks=0;
function close(a,b){assert(Math.abs(a-b)<=1e-8*Math.max(1,Math.abs(b)),`${a} != ${b}`);checks++;}
function run(changes={}){for(const [k,v] of Object.entries({...defaults,...changes}))doc.getElementById(k).value=String(v);nodes.calculateBtn.events.click();return ctx.lastPASvsTargetResult;}
const keys=['conservative','moderate','aggressive'];


for(const principal of [0,1000000.19])for(const ret of [-100,0,6,15])for(const advisory of [0,.3,1])for(const fund of [0,.08,.5])for(const expense of [0,.08,.3]) {
 const r=run({portfolioValue:principal,returnRate:ret,pasFee:advisory,pasFundExpense:fund,targetDateFee:expense,withdrawalsStartYear:2027});
 assert(r);close(r.pasFee,advisory+fund);
 let balance=principal,advisorySum=0,fundSum=0;
 for(let y=1;y<=r.years;y++) {
  const grown=balance*(1+ret/100),a=grown*advisory/100,f=grown*fund/100;
  const required=y<2?0:60000*Math.pow(1.025,y-2),paid=Math.min(grown-a-f,required);
  balance=grown-a-f-paid;advisorySum+=a;fundSum+=f;
  const p=r.pasData[y],t=r.targetData[y];
  close(p.advisoryFee,a);close(p.fundExpense,f);close(p.fee,a+f);close(p.balance,balance);close(p.totalAdvisoryFees,advisorySum);close(p.totalFundExpenses,fundSum);close(p.totalFees,advisorySum+fundSum);
  close(p.requiredWithdrawal,required);close(t.requiredWithdrawal,required);close(t.fee,r.targetData[y-1].balance*(1+ret/100)*expense/100);
 }
 close(r.directFeeDiff,advisorySum+fundSum-r.targetData.at(-1).totalFees);close(r.opportunityCost,r.targetFinal-r.pasFinal);
 close(charts.feesChart.data.datasets[0].data.at(-1),advisorySum+fundSum);
}
let r=run({portfolioValue:1000000,years:1,withdrawalsStartYear:2027});assert.equal(nodes.pasAllInCost.textContent,'0.38%');close(r.pasData[1].advisoryFee,3180);close(r.pasData[1].fundExpense,848);close(r.pasData[1].fee,4028);close(r.targetData[1].fee,848);close(r.directFeeDiff,3180);
for(const expense of [0,.08,.3]) {r=run({pasFee:0,pasFundExpense:expense,targetDateFee:expense});close(r.directFeeDiff,0);close(r.opportunityCost,0);assert.equal(nodes.totalFeesDiff.textContent,'$0');assert.equal(nodes.finalValueDiff.textContent,'$0');r.pasData.forEach((p,i)=>close(p.balance,r.targetData[i].balance));}
(async()=>{
 r=run({portfolioValue:1000000,years:26,withdrawalsStartYear:2027});nodes.downloadPdfBtn.events.click();nodes.downloadCsvBtn.events.click();assert.deepEqual(finishedCharts.sort(),['bucketChart','feesChart','growthChart']);
 for(const p of payloads) {assert.equal(p.pasAdvisoryFee,.3);assert.equal(p.pasFundExpense,.08);assert.equal(p.pasFee,.38);assert.equal(JSON.stringify(p.pasData),JSON.stringify(r.pasData));}
 ctx.explainPASResults();const message=payloads.at(-1).results_summary;for(const phrase of ['PAS advisory assumption 0.3%','PAS fund expense assumption 0.08%','modeled all-in PAS cost 0.38%','PAS cumulative advisory fees','PAS cumulative fund expenses','PAS total costs'])assert(message.includes(phrase),phrase);
 nodes.saveScenarioBtn.events.click();await new Promise(setImmediate);const saved=payloads.at(-1).scenario_data;assert.equal(saved.pasAdvisoryFee,'0.3');assert.equal(saved.pasFundExpense,'0.08');assert.equal(saved.targetDateFee,'0.08');assert(!('pasFee' in saved));
 scenarios=[{name:'Split',data:saved}];run({pasFee:1,pasFundExpense:1});nodes.loadScenarioBtn.events.click();await new Promise(setImmediate);nodes.calculateBtn.events.click();assert.deepEqual(ctx.lastPASvsTargetResult,r);
 const old={...saved,pasFee:'0.30'};delete old.pasAdvisoryFee;delete old.pasFundExpense;delete old.pasCostSchema;
 scenarios=[{name:'Legacy total',data:old}];nodes.loadScenarioBtn.events.click();await new Promise(setImmediate);assert.equal(nodes.pasFundExpense.value,'0');assert.equal(nodes.pasFee.value,'0.30');assert.equal(nodes.pasCostMigration.hidden,false);assert(nodes.pasCostMigration.textContent.includes('not a verified'));assert.equal(old.pasFee,'0.30');nodes.calculateBtn.events.click();close(ctx.lastPASvsTargetResult.pasFee,.3);
 const migrated=JSON.stringify(ctx.lastPASvsTargetResult.pasData);run({portfolioValue:1000000,years:26,withdrawalsStartYear:2027,pasFee:.3,pasFundExpense:0});assert.equal(JSON.stringify(ctx.lastPASvsTargetResult.pasData),migrated);
 if(process.argv[2])fs.writeFileSync(process.argv[2],JSON.stringify(r));
 console.log(`PASS ${checks} independent PAS component checks; all-in display, no double counting, equal-cost portfolios, exports, AI context, Save/Load and legacy migration.`);
})().catch(e=>{console.error(e);process.exitCode=1;});
