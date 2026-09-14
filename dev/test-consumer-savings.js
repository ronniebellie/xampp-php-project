'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm'),path=require('node:path'),math=require('../js/lib/numerical-core.js');
const root=path.resolve(__dirname,'..');let count=0;const eq=(a,b)=>{count++;assert(Math.abs(a-b)<1e-6,`${a} != ${b}`);};
for(const rate of [0,5,-5]){
 const r=math.goalSavings(1000,950,100,rate,new Date(2026,0,31));let prior=950;
 for(const row of r.schedule){eq(prior+row.interest+row.contribution,row.balance);prior=row.balance;}
 eq(r.finalBalance,prior);assert(r.schedule.length<=600);assert.equal(r.reachedDate.getMonth(),1);assert.equal(r.reachedDate.getDate(),28);
}
const zero=math.goalSavings(1000,950,100,0);eq(zero.finalBalance,1050);eq(zero.schedule.at(-1).balance,1050);
eq(math.goalSavings(1000,1000,0,0).monthsToGoal,0);assert.equal(math.goalSavings(1000,0,0,0).monthsToGoal,null);
for(const values of [[NaN,0,0,0],[1000,0,Infinity,0],[1000,0,0,-100]])assert.throws(()=>math.goalSavings(...values));
const n={value:'0',addEventListener(){},style:{},getContext(){return {};}};
for(const folder of ['down-payment','emergency-fund','compound-interest']){
 const ctx=vm.createContext({RBNumerical:math,Date,Intl,document:{getElementById(){return n;},addEventListener(){}},window:{}});vm.runInContext(fs.readFileSync(path.join(root,folder,'calculator.js'),'utf8'),ctx);
 if(folder==='compound-interest')for(const r of [0,5,-5]){let b=1000;for(let m=0;m<120;m++)b=b*(1+r/1200)+100;eq(ctx.runCompoundProjection(1000,100,r,10).finalBalance,b);}
 else {const r=folder==='down-payment'?ctx.runProjection(1000,950,100,0):ctx.runProjection(1000,1,950,100,0);eq(r.finalBalance,r.schedule.at(-1).balance);}
}
function mc(values){
 const nodes={};const get=id=>nodes[id]||=( {value:String(values[id]??''),checked:false,style:{},dataset:{},textContent:'',innerHTML:'',classList:{add(){},remove(){},toggle(){}},addEventListener(){},setAttribute(){},getContext(){return {};},scrollIntoView(){}} );
 const context={window:null,console,Intl,Date,URLSearchParams,document:{getElementById:get,querySelector(){return null;},querySelectorAll(){return [];},addEventListener(){}},location:{pathname:'/plan-success/',origin:'http://local.test',search:''},Chart:class{destroy(){}},setTimeout(){},clearTimeout(){},addEventListener(){}};context.window=context;
 let source=fs.readFileSync(path.join(root,'plan-success/calculator.js'),'utf8').replace('  var chartInstance = null;','  window.testRun=runMonteCarlo;\n  var chartInstance = null;');vm.runInNewContext(source,context);context.testRun(false);return context.lastPlanSuccessResult;
}
for(const timing of ['annual','monthly']) {
 const r=mc({portfolio:1000,withdrawal:200,years:5,expectedReturn:0,volatility:0,simulations:100,inflationRate:0,withdrawalTiming:timing,withdrawalMethod:'fixed',withdrawalRate:0});
 eq(Number(r.successRate),100);eq(r.p50,0);
 const fail=mc({portfolio:1000,withdrawal:250,years:5,expectedReturn:0,volatility:0,simulations:100,inflationRate:0,withdrawalTiming:timing,withdrawalMethod:'fixed',withdrawalRate:0});eq(Number(fail.successRate),0);eq(fail.p50,0);
}
console.log(`Consumer savings and actual Monte Carlo adapter: ${count} checks passed.`);
const pasNodes={};const pv={portfolioValue:1000,pasFee:1,targetDateFee:.5,years:2,returnRate:10,withdrawalPct:10,timelineStartYear:2030,withdrawalsStartYear:2030,pctConservative:0,pctModerate:100,pctAggressive:0};
const pnode=id=>pasNodes[id]||={value:String(pv[id]??0),style:{},textContent:'',innerHTML:'',getContext(){return {};},addEventListener(){}};
const pas={window:null,document:{getElementById:pnode,addEventListener(){},querySelectorAll(){return [];}},location:{pathname:'/vanguard-pas-vs-target-date/',origin:'offline'},Intl,Date,Chart:class{destroy(){}},alert(){},addEventListener(){}};pas.window=pas;
vm.runInNewContext(fs.readFileSync(path.join(root,'vanguard-pas-vs-target-date/calculator.js'),'utf8').replace('  function calculatePortfolio(', '  window.pasTest={project:calculatePortfolio,calculate:calculate};\n  function calculatePortfolio('),pas);
pas.pasTest.calculate(false);const pr=pas.lastPASvsTargetResult;eq(pr.pasFinal,958.441);eq(pr.targetFinal,969.24025);eq(pr.lostGrowth,-.055);assert(pasNodes.breakdownGrowth.textContent.includes('-'));
const late=pas.pasTest.project(1000,0,0,2,10,6);eq(late.at(-1).totalWithdrawals,0);eq(late.at(-1).balance,1000);
assert.throws(()=>pas.pasTest.project(1e308,100,0,120,0,1));
console.log('PAS actual screen and projection: independent cash ledger, signed residual, future withdrawals and overflow checks passed.');

if(process.env.RB_PAS_QA_INPUT)fs.writeFileSync(process.env.RB_PAS_QA_INPUT,JSON.stringify(pr));
