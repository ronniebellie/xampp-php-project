'use strict';
const assert=require('node:assert/strict'),vm=require('node:vm'),fs=require('node:fs'),path=require('node:path');
const E=require('../vanguard-pas-vs-target-date/stress-engine.js');
const root=path.resolve(__dirname,'..'),nodes={},payloads=[],charts={};let choice='Saved stress',scenarios=[],nextSeed=9876;
const values={portfolioValue:1000000,years:26,timelineStartYear:2026,withdrawalsStartYear:2027,pasFee:.3,targetDateFee:.08,returnRate:6,withdrawalPct:4.2,pctConservative:20,pctModerate:30,pctAggressive:50,analysisMode:'simple',annualWithdrawal:60000,inflation:2.5,conservativeReturn:4,conservativeVolatility:6,moderateReturn:5.5,moderateVolatility:10,aggressiveReturn:7,aggressiveVolatility:16,pasReturn:6,pasVolatility:11,simulations:1000,simulationSeed:1};
function node(id){return nodes[id]||=( {_value:String(values[id]??''),get value(){return this._value;},set value(v){this._value=String(v);},style:{},hidden:false,disabled:false,events:{},textContent:'',addEventListener(k,f){(this.events[k]||=[]).push(f);},click(){this.fire('click');},fire(k){for(const f of this.events[k]||[])f();},getContext(){return id;},scrollIntoView(){},toDataURL(){return 'data:image/png;base64,AA==';}});}
const doc={currentScript:{src:'https://example.test/vanguard-pas-vs-target-date/stress-ui.js'},getElementById:node,querySelectorAll(){return [];},addEventListener(k,f){if(k==='DOMContentLoaded')f();}};
const captured=(url,opts)=>{payloads.push({url,body:JSON.parse(opts.body)});return {then(){return this;},catch(){return this;}};};
const c={document:doc,console,Intl,URL,Date,Uint32Array,Number,crypto:{getRandomValues(a){a[0]=nextSeed++;}},setTimeout,addEventListener(){},alert(msg){if(!msg.startsWith('Scenario loaded'))throw Error(msg);},prompt(){return choice;},PASStressEngine:E,location:{origin:'https://example.test',pathname:'/vanguard-pas-vs-target-date/'},Chart:function(id,config){charts[id]=config;this.destroy=()=>{};},Worker:function(){this.cancelled=false;this.postMessage=input=>setImmediate(()=>{if(!this.cancelled)this.onmessage({data:{result:E.run(input)}});});this.terminate=()=>{this.cancelled=true;};},rbScenarioFetch:captured,rbExplainFetch:captured,fetch(url,opts){if(opts&&opts.body)return captured(url,opts);return Promise.resolve({json:()=>Promise.resolve({success:true,scenarios})});}};
c.window=c;
for(const f of ['calculator.js','stress-ui.js'])vm.runInNewContext(fs.readFileSync(path.join(root,'vanguard-pas-vs-target-date',f),'utf8'),c);
const flush=()=>new Promise(setImmediate);
(async()=>{
 node('calculateBtn').click();const simple=JSON.stringify(c.lastPASvsTargetResult);assert(!node('simpleProjectionResults').hidden);
 node('analysisMode').value='stress';node('analysisMode').fire('change');assert(node('simpleProjectionResults').hidden);assert(!node('stressInputs').hidden);
 node('calculateBtn').click();await flush();const stress=c.lastPASvsTargetResult;assert.equal(stress.analysisMode,'stress');assert.equal(stress.inputs.annualWithdrawal,60000);assert.equal(stress.inputs.seed,9876);assert(charts.stressEndingChart.data.datasets.length===2);assert(node('stressComparison').innerHTML.includes('Median ending balance')); 
 node('saveScenarioBtn').click();const saved=payloads.at(-1).body.scenario_data;assert.equal(saved.analysisMode,'stress');assert.equal(saved.stress.simulationSeed,'9876');assert.equal(saved.stress.pasVolatility,'11');
 node('downloadPdfBtn').click();assert.equal(payloads.at(-1).body.analysisMode,'stress');assert.equal(payloads.at(-1).body.stressCharts.length,3);
 node('downloadCsvBtn').click();assert.deepEqual(payloads.at(-1).body.inputs,stress.inputs);
 node('explainResultsBtnInResults').click();const explain=payloads.at(-1).body;assert.equal(explain.analysis_mode,'stress');assert(explain.results_summary.includes('not predictions or guarantees'));assert(explain.results_summary.includes('"volatilities"'));assert(Buffer.byteLength(explain.results_summary)<8000);
 node('newSimulationBtn').click();await flush();assert.notEqual(c.lastPASvsTargetResult.seed,stress.seed);
 scenarios=[{scenario_name:'Saved stress',data:saved}];choice='1';node('loadScenarioBtn').click();await flush();node('calculateBtn').click();await flush();assert.deepEqual(c.lastPASvsTargetResult,stress);
 node('annualWithdrawal').value='70000';node('annualWithdrawal').fire('input');assert.equal(c.lastPASvsTargetResult,null);assert.equal(node('results').style.display,'none');
 scenarios=[{name:'Legacy',data:values}];node('loadScenarioBtn').click();await flush();assert.equal(node('analysisMode').value,'simple');node('calculateBtn').click();assert.equal(JSON.stringify(c.lastPASvsTargetResult),simple);
 // Stale worker responses cannot overwrite a switched mode.
 node('analysisMode').value='stress';node('analysisMode').fire('change');node('calculateBtn').click();node('analysisMode').value='simple';node('analysisMode').fire('change');await flush();assert.equal(c.lastPASvsTargetResult,null);
 console.log('PASS modes, Simple Projection identity, worker cancellation, saved stress reproducibility, legacy load, seeded rerun, charts, PDF/CSV payloads and Explain context.');
})().catch(e=>{console.error(e);process.exitCode=1;});
