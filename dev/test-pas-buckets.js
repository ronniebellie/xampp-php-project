'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const nodes = {}, charts = {}, payloads = [];
let scenarios = [], choice = '1';
const defaults = {portfolioValue:100000,pasFee:0.3,targetDateFee:0.08,years:30,returnRate:6,withdrawalPct:4.5,timelineStartYear:2026,withdrawalsStartYear:2026,pctConservative:20,pctModerate:30,pctAggressive:50};
const doc = {getElementById(id) {return nodes[id] ||= {value:String(defaults[id] ?? 0),style:{},events:{},addEventListener(k,f){this.events[k]=f;},getContext(){return id;},scrollIntoView(){}};},addEventListener(k,f){if(k==='DOMContentLoaded')f();}};
const ctx = {document:doc,Intl,Date,console,Number,location:{pathname:'/vanguard-pas-vs-target-date/',origin:'http://test.local'},addEventListener(){},alert(message){if(message.startsWith('Scenario'))return;throw Error(message);},prompt(){return choice;},setTimeout(){},Chart:function(id,c){charts[id]=c;this.destroy=()=>{};},fetch(url,opts){if(opts && opts.body){payloads.push(JSON.parse(opts.body));return {then(){return this;},catch(){return this;}};}return Promise.resolve({json:()=>Promise.resolve({success:true,scenarios})});},rbScenarioFetch(url,opts){payloads.push(JSON.parse(opts.body));return Promise.resolve({ok:true,status:200,text:()=>Promise.resolve('{}')});}};
ctx.window=ctx;
vm.runInNewContext(fs.readFileSync(path.join(root,'vanguard-pas-vs-target-date/calculator.js'),'utf8'),ctx);
let checks=0;
function close(a,b){assert(Math.abs(a-b)<=1e-8*Math.max(1,Math.abs(b)),`${a} != ${b}`);checks++;}
function run(changes={}){for(const [k,v] of Object.entries({...defaults,...changes}))doc.getElementById(k).value=String(v);nodes.calculateBtn.events.click();return ctx.lastPASvsTargetResult;}
const keys=['conservative','moderate','aggressive'];
for(const principal of [0,0.01,100000,1325000.19])for(const ret of [-100,0,6,20])for(const withdrawal of [0,4.5,10])for(const allocation of [[20,30,50],[0,0,0],[0,100,0],[100,0,0],[1,1,1],[0,0,100],[33,66,0]]) {
 const r=run({portfolioValue:principal,returnRate:ret,withdrawalPct:withdrawal,pctConservative:allocation[0],pctModerate:allocation[1],pctAggressive:allocation[2]});
 close(Object.values(r.allocation).reduce((a,b)=>a+b,0),100);
 close(Object.values(r.targetData[0].buckets).reduce((a,b)=>a+b,0),principal);
 let legacy=principal,pas=principal;
 for(let i=1;i<r.targetData.length;i++) {
  const row=r.targetData[i], prev=r.targetData[i-1], factor=1+ret/100;
  close(row.withdrawal,prev.balance*factor*withdrawal/100);
  close(row.fee,prev.balance*factor*0.08/100);
  close(row.balance,keys.reduce((sum,k)=>sum+row.buckets[k],0));
  close(row.withdrawal,keys.reduce((sum,k)=>sum+row.bucketWithdrawals[k],0));
  for(const [j,k] of keys.entries()) {
   assert(row.buckets[k]>=0);
   close(row.buckets[k],prev.buckets[k]*factor-row.bucketFees[k]-row.bucketWithdrawals[k]);
   if(row.bucketWithdrawals[k]>0)for(const earlier of keys.slice(0,j))assert.equal(row.buckets[earlier],0);
  }
  legacy=legacy*factor*(1-0.0008-withdrawal/100);
  pas=pas*factor*(1-0.003-withdrawal/100);
  close(row.balance,legacy);close(r.pasData[i].balance,pas);
 }
 close(r.directFeeDiff+r.lostGrowth,r.opportunityCost);
 close(charts.growthChart.data.datasets[1].data.at(-1),r.targetFinal);
 keys.forEach((k,i)=>close(charts.bucketChart.data.datasets[i].data.at(-1),r.targetData.at(-1).buckets[k]));
}
let r=run({portfolioValue:1000,returnRate:0,targetDateFee:0,pctConservative:2,pctModerate:3,pctAggressive:95,withdrawalPct:10,years:1});
assert.deepEqual(JSON.parse(JSON.stringify(r.targetData[1].bucketWithdrawals)),{conservative:20,moderate:30,aggressive:50});
assert(nodes.bucketSummary.innerHTML.includes('Depleted: 2026'));
r=run({years:5,withdrawalsStartYear:2031});assert(r.targetData.every(row=>row.withdrawal===0));
r=run({years:5,withdrawalsStartYear:2028});assert.equal(r.targetData[2].withdrawal,0);assert(r.targetData[3].withdrawal>0);
r=run({years:1,pctConservative:0});assert(nodes.bucketSummary.innerHTML.includes('Not funded at start'));
(async()=>{
 r=run();nodes.downloadPdfBtn.events.click();nodes.downloadCsvBtn.events.click();
 for(const payload of payloads){assert.equal(JSON.stringify(payload.targetData),JSON.stringify(r.targetData));assert.equal(JSON.stringify(payload.allocation),JSON.stringify(r.allocation));assert.equal(payload.timelineStartYear,2026);}
 nodes.saveScenarioBtn.events.click();await new Promise(setImmediate);
 const saved=payloads.at(-1).scenario_data;
 assert.equal(saved.pctConservative,'20');
 scenarios=[{name:'Legacy scenario',data:saved}];
 run({pctConservative:100});nodes.loadScenarioBtn.events.click();await new Promise(setImmediate);
 nodes.calculateBtn.events.click();assert.deepEqual(ctx.lastPASvsTargetResult.targetData,r.targetData);
 if(process.argv[2])fs.writeFileSync(process.argv[2],JSON.stringify(r));
 console.log(`PAS bucket tests passed: ${checks} numeric checks, sequential depletion, zero balances, normalized allocations, delayed withdrawals, unchanged PAS/totals, charts, export payloads and legacy scenario round trip.`);
})().catch(e=>{console.error(e);process.exitCode=1;});
