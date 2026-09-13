'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm'),path=require('node:path');
const root=path.join(__dirname,'..'),math=require('../js/lib/numerical-core.js');let checks=0;
function close(a,b,tol=1e-6){checks++;assert(Number.isFinite(a)&&Math.abs(a-b)<=tol,`${a} != ${b}`);}
close(math.pensionPV(30000,.05,25).value,422818.34,.005);
close(math.pensionPV(30000,0,25).value,750000);
close(math.annuityFV(100,.01,12),1268.2503013197);
close(math.annuityFV(100,.01,12,true),1280.9328043329);
close(math.annuityFV(100,0,12),1200);
close(math.requiredPayment(12000,2000,0,10),1000);
for(const rate of [0,.01,-.01,1e-12])for(const due of [false,true])for(const n of [1,10,12,120]) {
  let independent=2000;for(let i=0;i<n;i++)independent=due?(independent+100)*(1+rate):independent*(1+rate)+100;
  close(math.annuityFV(100,rate,n,due,2000),independent);
  const ledger=math.annuityLedger(100,rate,n,due,2000);close(ledger.at(-1).value,independent);
  close(ledger.at(-1).contributed+ledger.at(-1).interest,independent);
  const payment=math.requiredPayment(12000,2000,rate,n,due);close(math.annuityFV(payment,rate,n,due,2000),12000);
}
for(const args of [[100,-1,12],[100,-1.01,12],[100,NaN,12],[100,.01,Infinity],[100,.01,-1],[100,.01,1.5]])assert.throws(()=>math.annuityFV(...args),RangeError);
assert.throws(()=>math.requiredPayment(1000,0,0,0),RangeError);
for(const [flows,status,roots] of [[[-100,110],'unique',[.1]],[[-1,1],'unique',[0]],[[100,100],'no_irr',[]],[[-100,-110],'no_irr',[]],[[-100,230,-132],'multiple_irr',[.1,.2]]]) {
  const result=math.irr(flows);assert.equal(result.status,status);assert.equal(result.roots.length,roots.length);roots.forEach((r,i)=>close(result.roots[i],r));
}
close(math.irr([-1e-10,1.1e-10]).roots[0],.1);
for (const flows of [[0,-100,110,0,0],[0,0,-1e-20,1.1e-20,0],[-1e20,1.1e20]]) {
  const result=math.irr(flows);assert.equal(result.status,'unique');assert.equal(result.roots.length,1);close(result.roots[0],.1);
}
assert.equal(math.irr([0,0]).status,'indeterminate');
assert.throws(()=>math.irr([-1e308,1e-308]),/scale/);
assert.throws(()=>math.debtSaving(1000,0,1e308,1e308,0,1,false),/Finite/);
assert.throws(()=>math.debtPayoff([{name:'A',balance:1000,apr:0,minPayment:1e308},{name:'B',balance:1000,apr:0,minPayment:1e308}],'avalanche',0),/Finite/);
assert.equal(math.irr([-1,2,-1]).status,'ambiguous','double root must not be presented as a unique IRR');
const stuck=math.debtPayoff([{name:'Loan',balance:1000,apr:12,minPayment:10}],'avalanche',0);
assert.equal(stuck.status,'non_amortizing');assert.equal(stuck.months,0);
const long=math.debtPayoff([{name:'Slow',balance:1000,apr:0,minPayment:1}],'snowball',0);
assert.equal(long.status,'outside_horizon');assert.equal(long.months,720);close(long.remainingBalance,280);
for(const strategy of ['avalanche','snowball']) {
  const result=math.debtPayoff([{name:'A',balance:50,apr:0,minPayment:50},{name:'B',balance:500,apr:0,minPayment:50}],strategy,0);
  assert.equal(result.months,6);close(result.schedule[0].payment,100);close(result.schedule[1].payment,100);close(result.totalPaid,550);
  let prior=550;for(const row of result.schedule){const remaining=row.balances.reduce((a,b)=>a+b,0);close(prior+row.interest-row.payment,remaining);close(row.payment+row.unusedBudget,row.budget);prior=remaining;}
}
for(const rate of [0,6,12])for(const inv of [0,5,-5]) {
  const a=math.debtSaving(1000,rate,100,50,inv,2,false),b=math.debtSaving(1000,rate,100,50,inv,2,true);
  for(let i=0;i<24;i++){close(a.rows[i].budget,b.rows[i].budget);for(const row of [a.rows[i],b.rows[i]])close(row.payment+row.contribution,150);}
  if(rate===0&&inv===0){close(a.invest,2600);close(b.invest,2600);}
}
function widget(file,values={},source){
  const nodes={},events={},ready=[],alerts=[];
  const node=id=>nodes[id]||(nodes[id]={value:String(values[id]??''),checked:false,dataset:{},style:{},textContent:'',innerHTML:'',children:[],setAttribute(){},classList:{add(){},remove(){},toggle(){}},addEventListener(type,fn){events[id+':'+type]=fn;},appendChild(child){this.children.push({...child});},querySelectorAll(){return [];},scrollIntoView(){},getContext(){return {};}});
  const context=vm.createContext({console,Intl,Date,URLSearchParams,RBNumerical:math,alert:msg=>alerts.push(msg),setTimeout(){},Chart:class {constructor(ctx,config){this.config=config;}destroy(){}},document:{readyState:'loading',getElementById:node,querySelector(){return null;},querySelectorAll(){return [];},createElement(){return node('created');},addEventListener(type,fn){if(type==='DOMContentLoaded')ready.push(fn);}},window:{location:{pathname:'/'+file.split('/')[0]+'/',origin:'offline',search:''},addEventListener(){}}});
  vm.runInContext(source??fs.readFileSync(path.join(root,file),'utf8'),context,{filename:file});return {context,nodes,node,events,ready,alerts};
}
const fv=widget('future-value-app/calculator.js');
close(fv.context.futureValueAnnuity(100,.12,1),1268.2503013197);
close(fv.context.generateAnnuityGrowthData(100,.12,1).at(-1).value,1268.2503013197);
close(fv.context.requiredPayment(12000,0,10/12,2000),1000);
close(fv.context.requiredPayment(12000,0,.8333333333,2000),1000);
assert.throws(()=>fv.context.requiredPayment(12000,0,.84,2000),/whole/);
assert.throws(()=>fv.context.futureValueAnnuity(100,0,-1e-12),/whole/);
assert.throws(()=>fv.context.presentValue(100,-1,2),/Invalid/);
const submit={preventDefault(){}};
for (const [timing,rate,expected] of [['end',12,1268.2503013197],['begin',12,1280.9328043329],['end',0,1200],['begin',0,1200]]) {
  const ui=widget('future-value-app/calculator.js',{annuityPayment:100,annuityRate:rate,annuityYears:1,annuityTiming:timing});
  ui.events['annuityForm:submit'](submit);assert.equal(ui.alerts.length,0);
  close(ui.context.window.annuityChartChart.config.data.datasets[0].data.at(-1),expected);
  assert(ui.nodes.annuityResults.innerHTML.includes(Math.round(expected).toLocaleString('en-US')));
  assert(!/NaN|Infinity/.test(ui.nodes.annuityResults.innerHTML));
}
for (const rate of [0,12]) {
  const ui=widget('future-value-app/calculator.js',{targetGoal:12000,targetPresent:2000,targetRate:rate,targetYears:10/12});
  ui.events['targetForm:submit'](submit);assert.equal(ui.alerts.length,0);
  close(ui.context.window.targetChartChart.config.data.datasets[0].data.at(-1),12000);
  if(rate===0)assert(ui.nodes.targetResults.innerHTML.includes('$1,000'));
}
const zeroSingle=widget('future-value-app/calculator.js',{singleType:'fv',singleAmount:0,singleRate:0,singleYears:1});
zeroSingle.events['singleForm:submit'](submit);assert.equal(zeroSingle.alerts.length,0);assert(!/NaN|Infinity/.test(zeroSingle.nodes.singleResults.innerHTML));
const ss=widget('social-security-claiming-analyzer/calculator.js');
const early=ss.context.calculateLifetimeBenefits(100,62,62,0,5),late=ss.context.calculateLifetimeBenefits(100*1.05**8,70,70,0,5);
close(early[0].cumulativeTotal,1200/1.05);close(early[0].cumulativeTotal,late[0].cumulativeTotal);
assert.equal(ss.context.birthYearFromDateOnly('1960-01-01'),1960);
assert.throws(()=>ss.context.birthYearFromDateOnly('1960-02-30'),/calendar/);
assert.throws(()=>ss.context.calculateLifetimeBenefits(100,62,85,0,-100),/valid/);
close(ss.context.calculateLifetimeBenefits(100,70,65,0,5).at(-1).cumulativeTotal,0);
const ssUI=widget('social-security-claiming-analyzer/calculator.js',{birthDate:'1960-01-01',monthlyPIA:1000,lifeExpectancy:85,claimAgeA:70,claimAgeB:62,claimAgeC:67,colaRate:0,discountRate:5});
ssUI.events['ssForm:submit'](submit);assert.equal(ssUI.alerts.length,0);
assert.equal(ssUI.context.window.lastSSResult.birthYear,1960);assert.equal(ssUI.context.window.lifetimeBenefitsChart.config.data.labels[0],62);
['A','B','C'].forEach((key,i)=>{const result=ssUI.context.window.lastSSResult;close(result['total'+key],result['data'+key].reduce((s,row)=>s+row.annualBenefit/1.05**(row.age-62+1),0));close(ssUI.context.window.lifetimeBenefitsChart.config.data.datasets[i].data.at(-1),result['total'+key]);});
assert(ssUI.nodes.tableBody.innerHTML.includes('<td>62</td>'));
let exportPayload;
ssUI.context.fetch=(url,options)=>{exportPayload=JSON.parse(options.body);return new Promise(()=>{});}; // Capture only: never makes a request.
ssUI.context.downloadCSV();['A','B','C'].forEach(key=>close(exportPayload['data'+key].at(-1).cumulativeTotal,ssUI.context.window.lastSSResult['total'+key]));
ssUI.node('monthlyPIA').value='0';ssUI.events['ssForm:submit'](submit);assert.equal(ssUI.alerts.length,0);assert(!/NaN|Infinity/.test(ssUI.nodes.interpretation.innerHTML));
const irrSource=Array.from(fs.readFileSync(path.join(root,'jp-business.ronbelisle.com/npv-irr/index.php'),'utf8').matchAll(/<script>([\s\S]*?)<\/script>/g)).find(m=>m[1].includes('function irr'))[1].replace(/<\?php[\s\S]*?\?>/g,'""');
const irrUI=widget('npv-irr/inline.js',{initialInv:-100,discountRate:5,cf1:110,cf2:0,cf3:0,cf4:0,cf5:0},irrSource);
irrUI.events['npvForm:submit'](submit);assert.equal(irrUI.nodes.resultIrr.textContent,'10.00%');close(irrUI.context.npv(.05,[-100,110]),-100+110/1.05);
irrUI.node('initialInv').value='100';irrUI.events['npvForm:submit'](submit);assert(irrUI.nodes.resultIrr.textContent.includes('No IRR'));
irrUI.node('initialInv').value='-100';irrUI.node('cf1').value='230';irrUI.node('cf2').value='-132';irrUI.events['npvForm:submit'](submit);assert(irrUI.nodes.resultIrr.textContent.includes('10.00%, 20.00%'));
const pension=widget('pension-vs-lump-sum/calculator.js',{monthlyPension:2500,lumpSum:400000,currentAge:65,growthRate:5,lifeExpectancy:90});
pension.events['monthlyPension:input']();close(pension.context.window.lastPensionResult.pensionPV,422818.34,.005);
close(pension.context.window.pensionComparisonChart.config.data.datasets[0].data.at(-1),422818.34,.005);
assert(pension.nodes.resultsBody.innerHTML.includes('422,818'));
pension.node('growthRate').value='0';pension.events['growthRate:input']();close(pension.context.window.lastPensionResult.pensionPV,750000);
const track=widget('401k-on-track/calculator.js');close(track.context.suggestedAnnualContribution(2000,12000,0,10),1000);close(track.context.runProjection(2000,1000,0,10).projected,12000);
assert.throws(()=>track.context.runProjection(2000,1000,NaN,10),/finite/);
assert.throws(()=>track.context.suggestedAnnualContribution(2000,12000,1e308,120),/range/);
const trackUI=widget('401k-on-track/calculator.js',{currentAge:40,yearsToRetirement:10,currentBalance:2000,annualContribution:1000,expectedReturn:0,targetBalance:12000,desiredIncome:0,withdrawalRate:4});
trackUI.context.updateOnTrack();assert.equal(trackUI.alerts.length,0);close(trackUI.context.window.lastOnTrackResult.result.projected,12000);assert.equal(trackUI.nodes.expectedReturnLabel.textContent,'0%');assert.equal(trackUI.nodes.resultProjected.textContent,'$12,000');
trackUI.node('desiredIncome').value='1000';trackUI.node('withdrawalRate').value='0';trackUI.context.updateOnTrack();assert(trackUI.alerts.at(-1).includes('positive withdrawal rate'));assert.equal(trackUI.context.window.lastOnTrackResult,null);
const debtUI=widget('debt-payoff/calculator.js');debtUI.context.displayResults(long);assert.equal(debtUI.nodes.resultMonths.textContent,'Beyond 720 months');assert(!debtUI.nodes.payoffWarning.textContent.includes('never'));
const loanUI=widget('student-loan-payoff/calculator.js');loanUI.context.displayResults(stuck);assert(loanUI.alerts[0].includes('does not amortize'));assert.equal(loanUI.nodes.results.style.display,'none');
const swr=widget('swr-fee-impact/calculator.js').context.window.RBSwrMath;
assert.equal(swr.maxSWR(100,2,[-1,0],null,0),null);assert.equal(swr.maxSWR(100,1,[0],null,0),.15);
assert.throws(()=>swr.maxSWR(100,2,[0,0],[NaN,0],0),/inflation/);
const below=swr.maxSWR(100,120,Array(120).fill(-.1),null,0);assert(below>=0&&below<.005);assert(swr.simulateRetirement({startBalance:100,years:120,returns:Array(120).fill(-.1),inflation:null,feeRate:0,initialSpend:100*below}).survived);
const timeline=widget('retirement-timeline/calculator.js');timeline.ready.forEach(fn=>fn());
const dates=timeline.context.window.RBTimelineDates,date=dates.parseDate('2026-09-10');
assert.equal(date.getFullYear(),2026);assert.equal(date.getMonth(),8);assert.equal(date.getDate(),10);assert.equal(dates.parseDate('2026-02-30'),null);
assert.equal(dates.calendarISO(date),'2026-09-10');assert.equal(dates.calendarISO(dates.addMonths(date,-3)),'2026-06-10');
assert.equal(dates.calculateAgeOn(dates.parseDate('1961-09-10'),date),65);assert.equal(dates.calculateAgeOn(dates.parseDate('1961-09-11'),date),64);
assert.equal(dates.calendarISO(dates.addMonths(dates.parseDate('2026-01-31'),1)),'2026-02-28');
(async()=>{
  const saved=widget('future-value-app/calculator.js',{annuityTiming:'begin'});
  let scenarioData={annuityPayment:100,annuityRate:0,annuityYears:1};
  saved.context.prompt=()=> '1';
  saved.context.fetch=()=>Promise.resolve({json:()=>Promise.resolve({success:true,scenarios:[{name:'Offline fixture',updated_at:'2026-09-10',data:scenarioData}]})});
  saved.context.loadScenario();await new Promise(resolve=>setImmediate(resolve));assert.equal(saved.node('annuityTiming').value,'end','legacy scenario has explicit ordinary timing');
  scenarioData={...scenarioData,annuityTiming:'begin'};saved.context.loadScenario();await new Promise(resolve=>setImmediate(resolve));assert.equal(saved.node('annuityTiming').value,'begin');
  console.log(`Phase 4 numerical tests passed (${checks} numeric assertions plus domain, solver, termination, UI/export, saved-timing and date checks; TZ=${process.env.TZ||'system'}).`);
})().catch(error=>{console.error(error);process.exitCode=1;});
