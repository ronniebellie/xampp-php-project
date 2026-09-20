(function(){
  'use strict';
  const base=new URL('.',document.currentScript.src);
  const ids=['annualWithdrawal','inflation','conservativeReturn','conservativeVolatility','moderateReturn','moderateVolatility','aggressiveReturn','aggressiveVolatility','pasReturn','pasVolatility','simulations','simulationSeed'];
  const defaults=[60000,2.5,4,6,5.5,10,7,16,6,11,10000,0];
  const el=id=>document.getElementById(id),num=id=>Number(el(id).value);
  const money=v=>new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',maximumFractionDigits:0}).format(v);
  const pct=v=>v.toFixed(1)+'%';
  let worker=null,generation=0,charts=[];
  function isStress(){return el('analysisMode').value==='stress';}
  function seed(){const a=new Uint32Array(1);crypto.getRandomValues(a);return a[0];}
  function invalidate(){generation++;if(worker){worker.terminate();worker=null;}window.lastPASvsTargetResult=null;el('results').style.display='none';el('stressStatus').textContent=isStress()?'Inputs ready. Run the Stress Test to calculate results.':'';el('calculateBtn').disabled=false;}
  function setMode(){invalidate();el('stressInputs').hidden=!isStress();el('stressResults').hidden=!isStress();el('simpleProjectionResults').hidden=isStress();el('newSimulationBtn').hidden=!isStress();document.querySelectorAll('[data-simple-only]').forEach(node=>node.hidden=isStress());el('calculateBtn').textContent=isStress()?'Run Retirement Stress Test':'Calculate True Cost';}
  function collect(allocation){
    ids.concat(['portfolioValue','years','timelineStartYear','withdrawalsStartYear','pasFee','targetDateFee']).forEach(id=>{if(el(id).value.trim()==='')throw Error('Enter all Stress Test assumptions.');});
    return PASStressEngine.validate({portfolioValue:num('portfolioValue'),years:num('years'),timelineStartYear:num('timelineStartYear'),withdrawalsStartYear:num('withdrawalsStartYear'),pasFee:num('pasFee'),targetDateFee:num('targetDateFee'),annualWithdrawal:num('annualWithdrawal'),inflation:num('inflation'),simulations:num('simulations'),seed:num('simulationSeed'),allocation:[allocation.c,allocation.m,allocation.a],means:[num('conservativeReturn'),num('moderateReturn'),num('aggressiveReturn'),num('pasReturn')],volatilities:[num('conservativeVolatility'),num('moderateVolatility'),num('aggressiveVolatility'),num('pasVolatility')]});
  }
  function render(r){
    const p=r.inputs,end=p.timelineStartYear+p.years-1;
    el('stressRunDetails').textContent=p.simulations.toLocaleString()+' simulations • '+p.timelineStartYear+'–'+end+' • Seed '+r.seed+' • Model '+r.modelVersion;
    const year=v=>v===null?'No failures':String(v);
    const metrics=[['Survival through '+end,s=>pct(s.survival)],['Funded all scheduled withdrawals',s=>pct(s.fullyFunded)],['Median ending balance',s=>money(s.ending.p50)],['10th percentile ending balance',s=>money(s.ending.p10)],['25th percentile ending balance',s=>money(s.ending.p25)],['75th percentile ending balance',s=>money(s.ending.p75)],['90th percentile ending balance',s=>money(s.ending.p90)],['Median cumulative income actually paid',s=>money(s.medianIncome)],['Median cumulative fees charged',s=>money(s.medianFees)],['Median failure year (failed paths only)',s=>year(s.medianFailureYear)],['Early failure year (10th percentile of failed paths)',s=>year(s.earlyFailureYear)]];
    el('stressComparison').innerHTML=metrics.map(([label,f])=>'<tr><th scope="row">'+label+'</th><td>'+f(r.pas)+'</td><td>'+f(r.target)+'</td></tr>').join('');
    el('stressBucketDetails').innerHTML=r.buckets.depletion.map((d,i)=>'<p><strong>'+['Conservative','Moderate','Aggressive'][i]+':</strong> '+(!d.funded?'Not funded at start':d.medianYear===null?'Not depleted in any simulation':pct(d.percent)+' depleted; median depletion '+d.medianYear+' (10th–90th percentile: '+d.p10Year+'–'+d.p90Year+')')+'.</p>').join('')+'<p>Aggressive needed for withdrawals: '+pct(r.buckets.aggressiveNeeded)+'. Median ending Aggressive balance: '+money(r.buckets.medianAggressiveEnd)+'.</p>';
    charts.forEach(c=>c.destroy());charts=[];
    function chart(id,type,labels,datasets,percent=false){charts.push(new Chart(el(id).getContext('2d'),{type,data:{labels,datasets},options:{responsive:true,maintainAspectRatio:false,animation:false,plugins:{legend:{position:'top'},tooltip:{mode:'index',intersect:false,callbacks:{label:c=>c.dataset.label+': '+(percent?pct(c.parsed.y):money(c.parsed.y))}}},scales:{y:{beginAtZero:true,...(percent?{max:100}:{}),ticks:{callback:v=>percent?v+'%':money(v)}}}}}));}
    const series=(label,data,color)=>({label,data,borderColor:color,backgroundColor:color,borderWidth:2,tension:0,pointRadius:1,fill:false});
    chart('stressEndingChart','bar',['10th','25th','Median','75th','90th'],[series('PAS',['p10','p25','p50','p75','p90'].map(k=>r.pas.ending[k]),'#dc2626'),series('Three-Bucket',['p10','p25','p50','p75','p90'].map(k=>r.target.ending[k]),'#16a34a')]);
    const labels=r.annual.map(row=>row.year?'End '+row.calendarYear:'Start '+row.calendarYear);
    chart('stressSurvivalChart','line',labels,[series('PAS',r.annual.map(row=>row.pasSurvival),'#dc2626'),series('Three-Bucket',r.annual.map(row=>row.targetSurvival),'#16a34a')],true);
    chart('stressBucketChart','line',labels,['Conservative','Moderate','Aggressive'].map((name,i)=>series(name,r.annual.map(row=>row.buckets[i].p50),['#2563eb','#d97706','#16a34a'][i])));
  }
  function calculate(scroll,allocation){
    if(!scroll){invalidate();return;}
    invalidate();const token=generation;
    let input;try{input=collect(allocation);}catch(e){el('stressStatus').textContent=e.message;return;}
    el('calculateBtn').disabled=true;el('stressStatus').textContent='Running '+input.simulations.toLocaleString()+' simulations…';
    function fail(message){if(token!==generation)return;if(worker){worker.terminate();worker=null;}el('results').style.display='none';el('calculateBtn').disabled=false;el('stressStatus').textContent='Stress Test could not complete: '+message;}
    function done(result){if(token!==generation)return;if(worker){worker.terminate();worker=null;}try{el('results').style.display='block';render(result);window.lastPASvsTargetResult=result;el('calculateBtn').disabled=false;el('stressStatus').textContent='Simulation complete. Seed '+result.seed+'.';el('results').scrollIntoView({behavior:'smooth',block:'start'});}catch(e){window.lastPASvsTargetResult=null;fail(e.message);}}
    function fallback(){if(worker){worker.terminate();worker=null;}let job;try{job=PASStressEngine.create(input);}catch(e){fail(e.message);return;}function batch(){if(token!==generation)return;try{const progress=job.step(100);el('stressStatus').textContent='Simulating… '+Math.round(progress*100)+'%';if(progress<1)setTimeout(batch,0);else done(job.finish());}catch(e){fail(e.message);}}setTimeout(batch,0);}
    try{worker=new Worker(new URL('stress-worker.js?v=1',base));worker.onmessage=e=>{if(e.data.error)fail(e.data.error);else done(e.data.result);};worker.onerror=fallback;worker.postMessage(input);}catch(_){fallback();}
  }
  function saved(){return {analysisMode:el('analysisMode').value,stressModelVersion:PASStressEngine.VERSION,stress:Object.fromEntries(ids.map(id=>[id,el(id).value]))};}
  function load(data){ids.forEach((id,i)=>el(id).value=data.stress&&data.stress[id]!==undefined?data.stress[id]:id==='simulationSeed'?seed():defaults[i]);el('analysisMode').value=data.analysisMode==='stress'?'stress':'simple';setMode();}
  function pdfPayload(r){return Object.assign({},r,{stressCharts:['stressEndingChart','stressSurvivalChart','stressBucketChart'].map(id=>el(id).toDataURL('image/png'))});}
  function explanation(r){return 'Retirement Stress Test. '+PASStressEngine.DISCLAIMER+'\nGeneric assumptions, not Vanguard forecasts. All dollars nominal. Means/volatilities are in Conservative, Moderate, Aggressive, PAS order. Fees follow growth and withdrawals, based on remaining balances. Same inflation-adjusted spending requirement for both. Sequential C/M/A withdrawals, no replenishment. Survival is fully funded spending, not guaranteed future success. Dollar percentiles include failed paths. Depletion statistics condition on depletion; failure-year statistics condition on failure. Early failure is the 10th percentile of failed years. Independent medians are not additive; do not attribute return/volatility differences to fees or declare a winner from one metric.\n'+JSON.stringify({model:r.modelVersion,inputs:r.inputs,correlation:r.correlation,pas:r.pas,target:r.target,buckets:r.buckets});}
  el('simulationSeed').value=seed();
  el('analysisMode').addEventListener('change',setMode);
  ids.concat(['portfolioValue','years','timelineStartYear','withdrawalsStartYear','pasFee','targetDateFee','pctConservative','pctModerate','pctAggressive']).forEach(id=>el(id).addEventListener('input',()=>{if(isStress())invalidate();}));
  el('newSimulationBtn').addEventListener('click',()=>{el('simulationSeed').value=seed();el('calculateBtn').disabled=false;el('calculateBtn').click();});
  window.PASStressUI={isStress,calculate,saved,load,pdfPayload,explanation};
  setMode();
})();
