/* Retirement Stress Test v1. Pure, shared numerical engine (browser/Worker/Node).
 * Annual arithmetic returns: max(-1, mean + volatility * correlated normal).
 * Box-Muller normals, Mulberry32 seeded PRNG, Cholesky-correlated shocks shared
 * by C/M/A/PAS. Years are independent; correlations/parameters are constant.
 * The -100% floor alters moments/correlations at extreme user assumptions.
 * Annual order: growth -> identical scheduled spending -> fee on remainder.
 * Failure = first unfunded spending (not a low or zero ending balance alone).
 * Dollar percentiles use linear interpolation at (n-1)*p (including failures).
 * Calendar event percentiles use nearest rank among observed events only.
 */
(function(root, factory) {
  const api = factory();
  if (typeof module === 'object' && module.exports) module.exports = api;
  else root.PASStressEngine = api;
})(typeof self !== 'undefined' ? self : globalThis, function() {
  'use strict';
  const VERSION = 'pas-stress-v1';
  const CORRELATION = [[1,.65,.40,.60],[.65,1,.80,.85],[.40,.80,1,.90],[.60,.85,.90,1]];
  const DISCLAIMER = 'Monte Carlo results are hypothetical planning illustrations based on the assumptions entered. They are not predictions or guarantees of future investment performance.';
  function cholesky(matrix) {
    if (!Array.isArray(matrix) || matrix.length !== 4 || matrix.some(row=>!Array.isArray(row)||row.length!==4)) throw Error('Expected a 4 by 4 correlation matrix.');
    const L = Array.from({length:4},()=>[0,0,0,0]);
    for(let i=0;i<4;i++)for(let j=0;j<4;j++) {
      if(!Number.isFinite(matrix[i][j]) || Math.abs(matrix[i][j])>1 || Math.abs(matrix[i][j]-matrix[j][i])>1e-12 || (i===j && matrix[i][j]!==1)) throw Error('Invalid correlation matrix.');
    }
    for(let i=0;i<4;i++)for(let j=0;j<=i;j++) {
      let s=matrix[i][j];for(let k=0;k<j;k++)s-=L[i][k]*L[j][k];
      if(i===j) {if(s < -1e-12)throw Error('Correlation matrix is not positive semidefinite.'); L[i][j]=Math.sqrt(Math.max(0,s));}
      else if(L[j][j]>1e-12)L[i][j]=s/L[j][j];
      else if(Math.abs(s)>1e-12)throw Error('Correlation matrix is not positive semidefinite.');
    }
    return L;
  }
  const FACTOR = cholesky(CORRELATION); // Also validates the fixed production matrix at load.
  function random(seed) {
    let state=seed>>>0;
    return function(){state=(state+0x6D2B79F5)>>>0;let t=state;t=Math.imul(t^(t>>>15),t|1);t^=t+Math.imul(t^(t>>>7),t|61);return ((t^(t>>>14))>>>0)/4294967296;};
  }
  function shocks(rng, L=FACTOR) {
    const z=[];
    for(let i=0;i<2;i++){const radius=Math.sqrt(-2*Math.log(1-rng()));const angle=2*Math.PI*rng();z.push(radius*Math.cos(angle),radius*Math.sin(angle));}
    return L.map((row,i)=>row.slice(0,i+1).reduce((s,v,j)=>s+v*z[j],0));
  }
  function returns(input, rng) {return shocks(rng).map((z,i)=>Math.max(-1,(input.means[i]+input.volatilities[i]*z)/100));}
  function validate(raw) {
    const p=JSON.parse(JSON.stringify(raw));
    function range(v,lo,hi,name,integer=false){if(!Number.isFinite(v)||v<lo||v>hi||(integer&&!Number.isInteger(v)))throw Error('Invalid '+name+'.');}
    range(p.portfolioValue,0,1e12,'portfolio');range(p.years,1,50,'timeline',true);
    range(p.timelineStartYear,1900,9999,'timeline start year',true);range(p.withdrawalsStartYear,1900,9999,'withdrawal start year',true);
    range(p.annualWithdrawal,0,1e10,'starting annual withdrawal');range(p.inflation,-10,20,'inflation');
    range(p.pasFee,0,100,'PAS fee');range(p.targetDateFee,0,100,'fund expense');
    if(![1000,5000,10000].includes(p.simulations))throw Error('Choose 1,000, 5,000 or 10,000 simulations.');
    range(p.seed,0,4294967295,'seed',true);
    if(!Array.isArray(p.allocation)||p.allocation.length!==3)throw Error('Invalid allocation.');
    p.allocation.forEach(v=>range(v,0,100,'allocation'));
    if(Math.abs(p.allocation.reduce((a,b)=>a+b,0)-100)>1e-8)throw Error('Allocations must total 100%.');
    for(const [key,min,max] of [['means',-100,100],['volatilities',0,100]]) {
      if(!Array.isArray(p[key])||p[key].length!==4)throw Error('Invalid return assumptions.');p[key].forEach(v=>range(v,min,max,key));
    }
    return p;
  }
  function spending(p, year) {
    // No inflation before the first modeled withdrawal, even if the requested start precedes the timeline.
    const start=Math.max(p.timelineStartYear,p.withdrawalsStartYear);
    return year < start ? 0 : p.annualWithdrawal*Math.pow(1+p.inflation/100,year-start);
  }
  function split(p) {
    const b=[0,0,0];let allocated=0;let last=0;
    p.allocation.forEach((v,i)=>{if(v>0)last=i;});
    p.allocation.forEach((v,i)=>{b[i]=i===last?p.portfolioValue-allocated:p.portfolioValue*v/100;allocated+=b[i];});return b;
  }
  function path(p,rng,returnSource) {
    let b=split(p),pas=p.portfolioValue,targetIncome=0,pasIncome=0,targetFees=0,pasFees=0;
    let targetFailure=null,pasFailure=null,aggressiveNeeded=false;
    const depletion=[null,null,null],initial=b.slice();
    const rows=[{year:0,calendarYear:p.timelineStartYear,buckets:b.slice(),target:p.portfolioValue,pas,required:0,targetPaid:0,pasPaid:0,targetFee:0,pasFee:0,bucketPaid:[0,0,0],bucketFees:[0,0,0]}];
    for(let y=1;y<=p.years;y++) {
      const calendarYear=p.timelineStartYear+y-1;
      const r=returnSource?returnSource(y):returns(p,rng);
      if(!Array.isArray(r)||r.length!==4||r.some(v=>!Number.isFinite(v)))throw Error('Invalid annual returns.');
      b=b.map((v,i)=>v*(1+Math.max(-1,r[i])));pas*=1+Math.max(-1,r[3]);
      const required=spending(p,calendarYear);let remaining=required;
      const bucketPaid=b.map((v,i)=>{const paid=Math.min(v,remaining);b[i]-=paid;remaining=Math.max(0,remaining-paid);return paid;});
      const targetPaid=bucketPaid.reduce((a,v)=>a+v,0),pasPaid=Math.min(pas,required);pas-=pasPaid;
      // Tolerate sub-cent floating-point roundoff only, not an economic shortfall.
      if(remaining>1e-7 && targetFailure===null)targetFailure=calendarYear;
      if(required-pasPaid>1e-7 && pasFailure===null)pasFailure=calendarYear;
      if(bucketPaid[2]>0)aggressiveNeeded=true;
      const bucketFees=b.map((v,i)=>{const fee=v*p.targetDateFee/100;b[i]=Math.max(0,v-fee);return fee;});
      const targetFee=bucketFees.reduce((a,v)=>a+v,0),pasFee=pas*p.pasFee/100;pas=Math.max(0,pas-pasFee);
      for(let i=0;i<3;i++)if(initial[i]>0 && b[i]===0 && depletion[i]===null)depletion[i]=calendarYear;
      targetIncome+=targetPaid;pasIncome+=pasPaid;targetFees+=targetFee;pasFees+=pasFee;
      const target=b.reduce((a,v)=>a+v,0);
      if(![target,pas,targetIncome,pasIncome,targetFees,pasFees].every(Number.isFinite))throw Error('Projection exceeds supported amounts.');
      rows.push({year:y,calendarYear,buckets:b.slice(),target,pas,required,targetPaid,pasPaid,targetFee,pasFee,bucketPaid,bucketFees,targetFailed:targetFailure!==null,pasFailed:pasFailure!==null});
    }
    return {rows,targetIncome,pasIncome,targetFees,pasFees,targetFailure,pasFailure,depletion,aggressiveNeeded};
  }
  function percentile(sorted,p) {if(!sorted.length)return null;const rank=(sorted.length-1)*p,lo=Math.floor(rank),hi=Math.ceil(rank);return sorted[lo]+(sorted[hi]-sorted[lo])*(rank-lo);}
  function stats(values) {const sorted=Float64Array.from(values).sort();return {p10:percentile(sorted,.1),p25:percentile(sorted,.25),p50:percentile(sorted,.5),p75:percentile(sorted,.75),p90:percentile(sorted,.9)};}
  function eventYear(values,p=.5) {if(!values.length)return null;const s=values.slice().sort((a,b)=>a-b);return s[Math.max(0,Math.ceil(s.length*p)-1)];}
  function create(raw) {
    const p=validate(raw),n=p.simulations,rng=random(p.seed);
    const data=Array.from({length:p.years+1},()=>Array.from({length:5},()=>new Float64Array(n)));
    const incomes=[new Float64Array(n),new Float64Array(n)],fees=[new Float64Array(n),new Float64Array(n)];
    const fails=[[],[]],depleted=[[],[],[]],survived=Array.from({length:p.years+1},()=>[0,0]);let usedAggressive=0,done=0;
    function step(count=100) {
      const until=Math.min(n,done+count);
      for(;done<until;done++) {
        const trial=path(p,rng);
        trial.rows.forEach((row,y)=>{[row.pas,row.target,...row.buckets].forEach((v,i)=>data[y][i][done]=v);survived[y][0]+=row.pasFailed?0:1;survived[y][1]+=row.targetFailed?0:1;});
        incomes[0][done]=trial.pasIncome;incomes[1][done]=trial.targetIncome;fees[0][done]=trial.pasFees;fees[1][done]=trial.targetFees;
        if(trial.pasFailure!==null)fails[0].push(trial.pasFailure);if(trial.targetFailure!==null)fails[1].push(trial.targetFailure);
        trial.depletion.forEach((y,i)=>{if(y!==null)depleted[i].push(y);});if(trial.aggressiveNeeded)usedAggressive++;
      }
      return done/n;
    }
    function finish() {
      if(done!==n)throw Error('Simulation is incomplete.');
      const annual=data.map((d,y)=>({year:y,calendarYear:p.timelineStartYear+Math.max(0,y-1),required:y?spending(p,p.timelineStartYear+y-1):0,pas:stats(d[0]),target:stats(d[1]),buckets:d.slice(2).map(stats),pasSurvival:100*survived[y][0]/n,targetSurvival:100*survived[y][1]/n}));
      const end=annual[p.years];
      function summary(i,ending){return {survival:100*(n-fails[i].length)/n,fullyFunded:100*(n-fails[i].length)/n,ending,medianIncome:stats(incomes[i]).p50,medianFees:stats(fees[i]).p50,failures:fails[i].length,medianFailureYear:eventYear(fails[i]),earlyFailureYear:eventYear(fails[i],.1)};}
      return {analysisMode:'stress',modelVersion:VERSION,inputs:p,seed:p.seed,correlation:CORRELATION.map(r=>r.slice()),disclaimer:DISCLAIMER,
        pas:summary(0,end.pas),target:summary(1,end.target),annual,
        buckets:{depletion:depleted.map((years,i)=>({funded:split(p)[i]>0,percent:100*years.length/n,medianYear:eventYear(years),p10Year:eventYear(years,.1),p90Year:eventYear(years,.9)})),aggressiveNeeded:100*usedAggressive/n,medianAggressiveEnd:end.buckets[2].p50}};
    }
    return {step,finish};
  }
  function run(p){const job=create(p);job.step(p.simulations);return job.finish();}
  return {VERSION,DISCLAIMER,CORRELATION,cholesky,random,shocks,returns,validate,spending,split,path,percentile,stats,eventYear,create,run};
});
