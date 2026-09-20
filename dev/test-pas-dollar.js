'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const nodes = {}, charts = {}, payloads = [];
let scenarios = [], choice = '1', alerts=[];
const defaults = {withdrawalModel:"dollar",annualWithdrawal:60000,inflation:2.5,portfolioValue:100000,pasFee:0.3,targetDateFee:0.08,years:30,returnRate:6,withdrawalPct:4.5,timelineStartYear:2026,withdrawalsStartYear:2026,pctConservative:20,pctModerate:30,pctAggressive:50};
const doc = {getElementById(id) {return nodes[id] ||= {value:String(defaults[id] ?? 0),style:{},events:{},addEventListener(k,f){this.events[k]=f;},toDataURL(){return "data:image/png;base64,test";},getContext(){return id;},scrollIntoView(){}};},addEventListener(k,f){if(k==='DOMContentLoaded')f();}};
const ctx = {document:doc,Intl,Date,console,Number,location:{pathname:'/vanguard-pas-vs-target-date/',origin:'http://test.local'},addEventListener(){},alert(message){alerts.push(message);},prompt(){return choice;},setTimeout(){},Chart:function(id,c){charts[id]=c;this.destroy=()=>{};},fetch(url,opts){if(opts && opts.body){payloads.push(JSON.parse(opts.body));return {then(){return this;},catch(){return this;}};}return Promise.resolve({json:()=>Promise.resolve({success:true,scenarios})});},rbScenarioFetch(url,opts){payloads.push(JSON.parse(opts.body));return Promise.resolve({ok:true,status:200,text:()=>Promise.resolve('{}')});}};
ctx.window=ctx;ctx.rbExplainFetch=ctx.fetch;
vm.runInNewContext(fs.readFileSync(path.join(root,'vanguard-pas-vs-target-date/calculator.js'),'utf8'),ctx);
let checks=0;
function close(a,b){assert(Math.abs(a-b)<=1e-8*Math.max(1,Math.abs(b)),`${a} != ${b}`);checks++;}
function run(changes={}){for(const [k,v] of Object.entries({...defaults,...changes}))doc.getElementById(k).value=String(v);nodes.calculateBtn.events.click();return ctx.lastPASvsTargetResult;}
const keys=['conservative','moderate','aggressive'];

// Independent aggregate recurrence and independent cash-flow reconciliation.
for(const principal of [0,.01,100000,1000000.19])for(const ret of [-100,0,6,20])for(const amount of [0,60000])for(const inflation of [0,2.5])for(const allocation of [[20,30,50],[0,0,100],[100,0,0],[33,66,0]]) {
 const r=run({portfolioValue:principal,returnRate:ret,annualWithdrawal:amount,inflation,pctConservative:allocation[0],pctModerate:allocation[1],pctAggressive:allocation[2]});
 assert(r,alerts.at(-1));close(r.pasData[0].balance,principal);close(r.targetData[0].balance,principal);
 let pas=principal,target=principal,pFees=0,tFees=0;
 for(let y=1;y<=r.years;y++) {
  const p=r.pasData[y],t=r.targetData[y],prev=r.targetData[y-1];
  const spending=amount*Math.pow(1+inflation/100,y-1);
  close(p.requiredWithdrawal,spending);close(t.requiredWithdrawal,spending);
  const pg=pas*(1+ret/100),tg=target*(1+ret/100),pf=pg*.003,tf=tg*.0008;
  const pp=Math.min(pg-pf,spending),tp=Math.min(tg-tf,spending);
  pas=pg-pf-pp;target=tg-tf-tp;pFees+=pf;tFees+=tf;
  close(p.balance,pas);close(t.balance,target);close(p.fee,pf);close(t.fee,tf);
  close(p.withdrawal,pp);close(t.withdrawal,tp);close(p.shortfall,spending-pp);close(t.shortfall,spending-tp);
  close(t.balance,keys.reduce((sum,k)=>sum+t.buckets[k],0));
  close(t.fee,keys.reduce((sum,k)=>sum+t.bucketFees[k],0));
  for(const [j,k] of keys.entries()) {assert(t.buckets[k]>=0);close(prev.buckets[k]*(1+ret/100),t.buckets[k]+t.bucketFees[k]+t.bucketWithdrawals[k]);if(t.bucketWithdrawals[k]>0)for(const earlier of keys.slice(0,j))assert.equal(t.buckets[earlier],0);}
 }
 close(r.directFeeDiff,pFees-tFees);close(r.opportunityCost,target-pas);close(r.directFeeDiff+r.lostGrowth,r.opportunityCost);
 close(Number(nodes.opportunityCost.textContent.replace(/[^0-9.-]/g,'')),Math.round(r.directFeeDiff));
}
let r=run({years:4,withdrawalsStartYear:2027});close(r.pasData[1].requiredWithdrawal,0);close(r.pasData[2].requiredWithdrawal,60000);close(r.pasData[3].requiredWithdrawal,61500);close(r.pasData[4].requiredWithdrawal,63037.5);
r=run({years:2,withdrawalsStartYear:2025});close(r.pasData[1].requiredWithdrawal,61500);
r=run({years:2,withdrawalsStartYear:2030});assert(r.targetData.every(x=>!x.withdrawal));
for(const fee of [0,.3,100]) {r=run({pasFee:fee,targetDateFee:fee});r.pasData.forEach((p,i)=>close(p.balance,r.targetData[i].balance));close(r.directFeeDiff,0);close(r.opportunityCost,0);}
r=run({portfolioValue:1000,returnRate:0,pasFee:0,targetDateFee:0,annualWithdrawal:1000,inflation:0,years:1});assert.equal(r.pasData[1].shortfall,0);assert.equal(r.pasFinal,0);assert(r.pasWithdrawalStatus.includes('All scheduled'));
r=run({portfolioValue:1000,returnRate:0,pasFee:0,targetDateFee:0,annualWithdrawal:600,inflation:0,years:2});assert.equal(r.pasData[2].shortfall,200);assert(r.pasWithdrawalStatus.includes('2027'));
r=run({portfolioValue:1000,returnRate:0,targetDateFee:0,annualWithdrawal:100,years:1,pctConservative:2,pctModerate:3,pctAggressive:95});assert.deepEqual(JSON.parse(JSON.stringify(r.targetData[1].bucketWithdrawals)),{conservative:20,moderate:30,aggressive:50});
(async()=>{
 r=run({portfolioValue:1000000,years:26,withdrawalsStartYear:2027});
 nodes.downloadPdfBtn.events.click();nodes.downloadCsvBtn.events.click();
 for(const p of payloads){assert.equal(p.withdrawalModel,'dollar');assert.equal(p.annualWithdrawal,60000);assert.equal(p.inflation,2.5);assert.equal(JSON.stringify(p.pasData),JSON.stringify(r.pasData));assert.equal(JSON.stringify(p.targetData),JSON.stringify(r.targetData));}
 ctx.explainPASResults();const explanation=payloads.at(-1).results_summary;assert(explanation.includes('Both face starting annual spending $60000'));assert(explanation.includes('additional direct PAS cost'));assert(!/Monte Carlo|survival|percentile/.test(explanation));
 nodes.saveScenarioBtn.events.click();await new Promise(setImmediate);const saved=payloads.at(-1).scenario_data;assert.equal(saved.withdrawalModel,'dollar');assert(!('stress' in saved));
 scenarios=[{name:'Dollars',data:saved}];run({annualWithdrawal:100});nodes.loadScenarioBtn.events.click();await new Promise(setImmediate);assert.equal(ctx.lastPASvsTargetResult,null);nodes.calculateBtn.events.click();assert.deepEqual(ctx.lastPASvsTargetResult,r);
 scenarios=[{name:'Retired',data:{analysisMode:'stress',portfolioValue:77}}];const before=nodes.portfolioValue.value;nodes.loadScenarioBtn.events.click();await new Promise(setImmediate);assert.equal(nodes.portfolioValue.value,before);assert(alerts.at(-1).includes('no longer supported'));
 scenarios=[{name:'Legacy',data:{...saved,withdrawalModel:undefined,annualWithdrawal:undefined,inflation:undefined,withdrawalPct:'4.5'}}];nodes.loadScenarioBtn.events.click();await new Promise(setImmediate);assert.equal(nodes.withdrawalModel.value,'percentage');assert.equal(nodes.legacyWithdrawals.hidden,false);nodes.calculateBtn.events.click();const legacy=ctx.lastPASvsTargetResult;close(legacy.pasData[2].withdrawal,legacy.pasData[1].balance*1.06*.045);
 nodes.useDollarWithdrawals.events.click();assert.equal(nodes.withdrawalModel.value,'dollar');assert.equal(nodes.legacyWithdrawals.hidden,true);
 if(process.argv[2])fs.writeFileSync(process.argv[2],JSON.stringify(r));
 console.log(`PASS ${checks} independent dollar/fee/bucket numerical checks, shortfalls, exports, explanation, saved round trip, legacy compatibility and retired scenario rejection.`);
})().catch(e=>{console.error(e);process.exitCode=1;});
