'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs');
const E=require('../vanguard-pas-vs-target-date/stress-engine.js');
const base={portfolioValue:1000000,years:26,timelineStartYear:2026,withdrawalsStartYear:2027,pasFee:.3,targetDateFee:.08,annualWithdrawal:60000,inflation:2.5,simulations:1000,seed:12345,allocation:[20,30,50],means:[4,5.5,7,6],volatilities:[6,10,16,11]};
let checks=0;function near(a,b,tol=1e-7){assert(Math.abs(a-b)<=tol*Math.max(1,Math.abs(b)),`${a} != ${b}`);checks++;}
function path(changes={},source){return E.path(E.validate({...base,...changes}),E.random(42),source);}
const first=E.run(base);assert.deepEqual(first,E.run(base));assert.notDeepEqual(first,E.run({...base,seed:54321}));
// Independent scalar ledger for zero volatility: withdrawals precede remaining-balance fees.
const zero=E.run({...base,volatilities:[0,0,0,0]});let amounts=[200000,300000,500000],pas=1000000,paid=0,fee=0;
for(let y=0;y<26;y++){
 amounts=amounts.map((v,i)=>v*(1+base.means[i]/100));pas*=1.06;
 let need=y===0?0:60000*1.025**(y-1);let remaining=need;
 for(let i=0;i<3;i++){let take=Math.min(remaining,amounts[i]);amounts[i]-=take;remaining-=take;}
 amounts=amounts.map(v=>v*.9992);let take=Math.min(pas,need);paid+=take;pas-=take;fee+=pas*.003;pas*=.997;
 near(zero.annual[y+1].pas.p50,pas);near(zero.annual[y+1].target.p50,amounts.reduce((a,b)=>a+b,0));
 for(let i=0;i<3;i++)near(zero.annual[y+1].buckets[i].p50,amounts[i]);
}
near(zero.pas.medianIncome,paid);near(zero.pas.medianFees,fee);
const order=path({portfolioValue:1000,allocation:[2,3,95],annualWithdrawal:100,withdrawalsStartYear:2026,years:1,pasFee:0,targetDateFee:0},()=>[0,0,0,0]);
assert.deepEqual(order.rows[1].bucketPaid,[20,30,50]);assert.deepEqual(order.rows[1].buckets,[0,0,900]);assert.equal(order.depletion[0],2026);assert.equal(order.depletion[1],2026);assert(order.aggressiveNeeded);
const exact=E.run({...base,portfolioValue:100,years:1,annualWithdrawal:100,withdrawalsStartYear:2026,volatilities:[0,0,0,0],means:[0,0,0,0]});near(exact.pas.survival,100);near(exact.target.survival,100);near(exact.pas.ending.p50,0);
const failed=E.run({...base,portfolioValue:100,years:2,annualWithdrawal:100,inflation:0,withdrawalsStartYear:2026,volatilities:[0,0,0,0],means:[0,0,0,0]});near(failed.pas.survival,0);assert.equal(failed.pas.medianFailureYear,2027);assert.equal(failed.target.earlyFailureYear,2027);near(failed.annual[1].targetSurvival,100);near(failed.annual[2].targetSurvival,0);near(failed.target.medianIncome,100);
[0,60000,61500,63037.50].forEach((amount,i)=>near(E.spending(base,2026+i),amount));
near(E.spending({...base,inflation:0},2035),60000);near(E.spending({...base,withdrawalsStartYear:2000},2026),60000);
const none=E.run({...base,annualWithdrawal:0,means:[-100,-100,-100,-100],volatilities:[0,0,0,0]});near(none.pas.survival,100);near(none.target.medianIncome,0);
const late=E.run({...base,withdrawalsStartYear:2099});near(late.target.survival,100);near(late.pas.medianIncome,0);
const nofees=path({pasFee:0,targetDateFee:0});near(nofees.pasFees,0);near(nofees.targetFees,0);
const loss=path({},()=>[-4,-2,-1,-10]);assert(loss.rows.every(r=>r.buckets.every(v=>v>=0)&&r.pas>=0));near(loss.rows[1].target,0);
// Every account ledger reconciles against independent per-year return arithmetic.
const varied=path({years:50},y=>[.02*Math.sin(y),.08*Math.cos(y),.1*Math.sin(2*y),.05*Math.cos(2*y)]);
for(let y=1;y<varied.rows.length;y++){
 const r=varied.rows[y],prev=varied.rows[y-1],returns=[.02*Math.sin(y),.08*Math.cos(y),.1*Math.sin(2*y),.05*Math.cos(2*y)];
 near(r.target,r.buckets.reduce((s,v)=>s+v,0));near(r.required,E.spending(base,r.calendarYear));near(r.targetPaid,r.bucketPaid.reduce((s,v)=>s+v,0));
 for(let i=0;i<3;i++){assert(r.buckets[i]>=0);near(prev.buckets[i]*(1+returns[i])-r.bucketPaid[i]-r.bucketFees[i],r.buckets[i]);if(r.bucketPaid[i]>0)for(let k=0;k<i;k++)assert.equal(r.buckets[k],0);}
 near(prev.pas*(1+returns[3])-r.pasPaid-r.pasFee,r.pas);assert(r.pasPaid<=r.required+1e-7);assert(r.targetPaid<=r.required+1e-7);
}
// Sequence risk: identical returns in reverse order, same total compound return, different funded spending.
const seq={portfolioValue:100,allocation:[100,0,0],years:2,annualWithdrawal:40,withdrawalsStartYear:2026,inflation:0,pasFee:0,targetDateFee:0};
assert(path(seq,y=>Array(4).fill(y===1?-.5:1)).targetFailure!==null);assert.equal(path(seq,y=>Array(4).fill(y===1?1:-.5)).targetFailure,null);
near(E.percentile([0,10,20,30],.25),7.5);near(E.percentile([0,10,20,30],.5),15);near(E.percentile([2],.9),2);assert.equal(E.eventYear([2030,2028,2040,2032]),2030);
assert.throws(()=>E.cholesky([[1,.9,.9,0],[.9,1,-.9,0],[.9,-.9,1,0],[0,0,0,1]]));
assert.doesNotThrow(()=>E.cholesky(Array.from({length:4},()=>[1,1,1,1])));
assert.throws(()=>E.validate({...base,seed:-1}));assert.throws(()=>E.validate({...base,allocation:[20,20,20]}));assert.throws(()=>E.validate({...base,annualWithdrawal:-1}));
// Statistical validation: all six Pearson correlations, normal means/std deviations.
const n=200000,rng=E.random(91827),s=[0,0,0,0],ss=[0,0,0,0],cross=Array.from({length:4},()=>[0,0,0,0]);
for(let k=0;k<n;k++){const z=E.returns(base,rng).map((v,i)=>(v*100-base.means[i])/base.volatilities[i]);for(let i=0;i<4;i++){s[i]+=z[i];ss[i]+=z[i]*z[i];for(let j=0;j<4;j++)cross[i][j]+=z[i]*z[j];}}
const correlations=[];
for(let i=0;i<4;i++){assert(Math.abs(s[i]/n)<.01);assert(Math.abs(Math.sqrt(ss[i]/n-(s[i]/n)**2)-1)<.01);for(let j=i+1;j<4;j++){const corr=(cross[i][j]/n-s[i]*s[j]/n**2)/Math.sqrt((ss[i]/n-(s[i]/n)**2)*(ss[j]/n-(s[j]/n)**2));assert(Math.abs(corr-E.CORRELATION[i][j])<.01);correlations.push([i,j,corr]);}}
// Independently count observed failure flags to audit aggregate survival and medians.
const checkRng=E.random(base.seed),failure=[],incomes=[];let success=0;
for(let i=0;i<base.simulations;i++){const t=E.path(base,checkRng);if(t.targetFailure===null)success++;else failure.push(t.targetFailure);incomes.push(t.targetIncome);}
near(first.target.survival,100*success/base.simulations);failure.sort((a,b)=>a-b);assert.equal(first.target.medianFailureYear,failure[Math.ceil(failure.length/2)-1]);incomes.sort((a,b)=>a-b);near(first.target.medianIncome,(incomes[499]+incomes[500])/2);
const started=performance.now();const full=E.run({...base,simulations:10000});const ms=performance.now()-started;assert(ms<15000,'10,000 x 26 performance budget exceeded');
const chunked=E.create(base);while(chunked.step(73)<1){}assert.deepEqual(chunked.finish(),first);
if(process.argv[2])fs.writeFileSync(process.argv[2],JSON.stringify(full));
console.log(JSON.stringify({result:'PASS',numericChecks:checks,statisticalDraws:n,correlations,milliseconds10000x26:Math.round(ms),survival:{pas:full.pas.survival,target:full.target.survival}},null,2));
