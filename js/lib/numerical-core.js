/** Phase 4 nonstatutory cash-flow math. Rates are decimals per period. */
(function(root){'use strict';
  function finite(...values){if(!values.every(v=>typeof v==='number'&&Number.isFinite(v)))throw new RangeError('Finite numeric inputs are required.');}
  function periods(rate,n){finite(rate,n);if(rate<=-1||!Number.isInteger(n)||n<0||n>12000)throw new RangeError('Positive compounding base and 0–12000 whole periods are required.');}
  function checked(value){if(!Number.isFinite(value))throw new RangeError('Result exceeds the supported numerical range.');return value;}
  function annuityFactor(rate,n,due=false){periods(rate,n);return checked((rate===0?n:Math.expm1(n*Math.log1p(rate))/rate)*(due?1+rate:1));}
  function annuityFV(payment,rate,n,due=false,principal=0){finite(payment,principal);return checked(principal*Math.pow(1+rate,n)+payment*annuityFactor(rate,n,due));}
  function requiredPayment(target,principal,rate,n,due=false){finite(target,principal);periods(rate,n);if(n===0)throw new RangeError('At least one payment period is required.');return checked(Math.max(0,target-principal*Math.pow(1+rate,n))/annuityFactor(rate,n,due));}
  function annuityLedger(payment,rate,n,due=false,principal=0){periods(rate,n);finite(payment,principal);let balance=principal;const rows=[{period:0,value:principal,contributed:principal,interest:0}];for(let i=1;i<=n;i++){balance=checked(due?(balance+payment)*(1+rate):balance*(1+rate)+payment);const contributed=principal+i*payment;rows.push({period:i,value:balance,contributed,interest:balance-contributed});}return rows;}
  function goalSavings(target,principal,payment,annualRatePercent,now=new Date()) {
    finite(target,principal,payment,annualRatePercent);
    if(target<0||principal<0||payment<0||annualRatePercent<=-100||annualRatePercent>100)throw new RangeError('Nonnegative money and an annual rate above -100% through 100% are required.');
    const rate=annualRatePercent/1200, schedule=[]; let balance=principal,month=0;
    while(balance<target&&month<600){const interest=balance*rate;balance=checked(balance+interest+payment);month++;schedule.push({month,balance,interest,contribution:payment,pct:target>0?Math.min(100,balance/target*100):0});}
    let reachedDate=null;
    if(balance>=target&&month>0){reachedDate=new Date(now.getFullYear(),now.getMonth()+month,1);const lastDay=new Date(reachedDate.getFullYear(),reachedDate.getMonth()+1,0).getDate();reachedDate.setDate(Math.min(now.getDate(),lastDay));}
    return {target,monthsToGoal:balance>=target?month:null,reachedDate,schedule,finalBalance:balance};
  }
  function pensionPV(annual,rate,years){finite(annual);periods(rate,years);let value=0;const rows=[];for(let year=1;year<=years;year++){value=checked(value+annual/Math.pow(1+rate,year));rows.push({year,pensionPV:value,nominalPension:annual*year});}return {value,rows};}
  function debtPayoff(debts,strategy,extra,horizon=720){
    finite(extra,horizon);if(extra<0||!Number.isInteger(horizon)||horizon<1||horizon>12000||!['avalanche','snowball'].includes(strategy))throw new RangeError('Valid payoff strategy, payment and bounded horizon required.');
    debts.forEach(d=>{finite(d.balance,d.apr,d.minPayment);if(d.balance<0||d.apr<0||d.minPayment<0)throw new RangeError('Debt inputs must be nonnegative.');});
    const order=[...debts].sort(strategy==='avalanche'?(a,b)=>b.apr-a.apr:(a,b)=>a.balance-b.balance),orderIndex=order.map(d=>debts.indexOf(d));
    const balances=debts.map(d=>d.balance),budget=extra+debts.reduce((s,d)=>s+d.minPayment,0),schedule=[],series=order.map(d=>[d.balance]),payoffOrder=[];
    finite(budget,balances.reduce((s,b)=>s+b,0));
    let totalInterest=0,totalPaid=0,month=0,status='paid_off';
    // A single fixed-payment loan with payment <= interest cannot amortize.
    if(debts.length===1&&balances[0]>0&&budget<=balances[0]*debts[0].apr/1200)status='non_amortizing';
    while(status==='paid_off'&&balances.some(b=>b>0.005)&&month<horizon){
      month++;let available=budget,interest=0;const payments=balances.map(()=>0);
      const priority=orderIndex.filter(i=>balances[i]>0.005).sort(strategy==='snowball'?(a,b)=>balances[a]-balances[b]:()=>0),target=priority[0];
      balances.forEach((b,i)=>{const charge=b*debts[i].apr/1200;interest+=charge;balances[i]=checked(b+charge);});
      balances.forEach((b,i)=>{const pay=Math.min(b,debts[i].minPayment,available);balances[i]-=pay;payments[i]+=pay;available-=pay;});
      for(const i of priority){const pay=Math.min(balances[i],available);balances[i]-=pay;payments[i]+=pay;available-=pay;}
      const payment=payments.reduce((a,b)=>a+b,0);totalPaid=checked(totalPaid+payment);totalInterest=checked(totalInterest+interest);
      for(const i of priority)if(balances[i]<=0.005&&!payoffOrder.includes(debts[i].name))payoffOrder.push(debts[i].name);
      orderIndex.forEach((i,k)=>series[k].push(balances[i]));
      schedule.push({month,targetDebt:debts[target].name,payment,targetPayment:payments[target],interest,balances:[...balances],budget,unusedBudget:available});
    }
    if(status==='paid_off'&&balances.some(b=>b>0.005))status='outside_horizon';
    return {months:month,totalInterest,totalPaid,schedule,payoffOrder,series,order,orderIndex,names:order.map(d=>d.name),status,neverPaysOff:status!=='paid_off',remainingBalance:balances.reduce((a,b)=>a+b,0)};
  }
  function debtSaving(balance,debtRate,minPayment,extra,investRate,years,payDebtFirst){
    finite(balance,debtRate,minPayment,extra,investRate,years);const n=Math.round(years*12);periods(investRate/1200,n);
    if(balance<0||debtRate<0||minPayment<0||extra<0||years<=0||Math.abs(years*12-n)>1e-8)throw new RangeError('Valid balances, payments and whole monthly periods required.');
    let debt=balance,invest=0;const budget=minPayment+extra,rows=[];
    finite(budget);
    for(let month=1;month<=n;month++){const interest=debt*debtRate/1200;debt+=interest;const payment=Math.min(debt,payDebtFirst?budget:minPayment);debt=checked(debt-payment);const contribution=budget-payment;invest=checked(invest*(1+investRate/1200)+contribution);rows.push({month,budget,payment,contribution,debt,invest});}
    return {debt,invest,rows};
  }
  function irr(cfs){
    if(!Array.isArray(cfs)||cfs.length<2||cfs.length>1000)throw new RangeError('Between 2 and 1000 periodic cash flows required.');finite(...cfs);
    const scale=Math.max(...cfs.map(Math.abs));if(scale===0)return {status:'indeterminate',roots:[]};
    if(!cfs.some(v=>v>0)||!cfs.some(v=>v<0))return {status:'no_irr',roots:[]};
    const a=cfs.map(v=>v/scale);
    if(cfs.some((v,i)=>v!==0&&a[i]===0))throw new RangeError('Cash-flow scale exceeds the supported numerical range.');
    while(a[0]===0)a.shift();
    while(a[a.length-1]===0)a.pop();
    const nonzero=a.filter(v=>v!==0),changes=nonzero.slice(1).filter((v,i)=>Math.sign(v)!==Math.sign(nonzero[i])).length;
    // Search log(1+r), bounded to avoid invalid discount bases. Multiple sign
    // changes remain explicitly ambiguous even if a bounded scan finds one root.
    // Scale both the exponent and residual: vanishing NPVs at extreme rates
    // are not roots, and leading/trailing zero flows must not create roots.
    const npv=x=>{let sum=0,magnitude=0;const shift=x<0?a.length-1:0;for(let t=0;t<a.length;t++){const term=a[t]*Math.exp(-x*(t-shift));sum+=term;magnitude+=Math.abs(term);}return sum/magnitude;},roots=[];
    function add(x){const rate=Math.expm1(x);if(Number.isFinite(rate)&&Math.abs(npv(x))<1e-9&&!roots.some(r=>Math.abs(r-rate)<1e-7))roots.push(rate);}
    let left=-13.8,fl=npv(left);
    for(let k=1;k<=6000;k++){const right=-13.8+k*27.6/6000,fr=npv(right);if(Math.abs(fr)<1e-12)add(right);if(Number.isFinite(fl)&&Number.isFinite(fr)&&fl!==0&&fr!==0&&Math.sign(fl)!==Math.sign(fr)){let lo=left,hi=right,flo=fl;for(let j=0;j<100;j++){const mid=(lo+hi)/2,fm=npv(mid);if(Math.abs(fm)<1e-13){lo=hi=mid;break;}if(Math.sign(flo)===Math.sign(fm)){lo=mid;flo=fm;}else hi=mid;}add((lo+hi)/2);}left=right;fl=fr;}
    roots.sort((a,b)=>a-b);return {status:roots.length>1?'multiple_irr':changes>1?'ambiguous':roots.length===1?'unique':'outside_search_range',roots};
  }
  const api={goalSavings,annuityFactor,annuityFV,requiredPayment,annuityLedger,pensionPV,debtPayoff,debtSaving,irr};
  if(typeof module!=='undefined'&&module.exports)module.exports=api;else root.RBNumerical=api;
})(typeof window!=='undefined'?window:this);
