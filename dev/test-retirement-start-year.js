'use strict';
const fs=require('node:fs'),vm=require('node:vm'),path=require('node:path'),assert=require('node:assert/strict');
const root=process.argv[2]||path.resolve(__dirname,'..'),c=vm.createContext({console});
for(const f of ['js/lib/finance-core.js','js/lib/federal-tax-2026.js','js/lib/rmd-tax-core.js','retirement-plan/plan-engine.js','retirement-plan/monte-carlo-engine.js'])vm.runInContext(fs.readFileSync(path.join(root,f),'utf8'),c);
// Public defaults are fictional; exact personal inputs stay in a private file.
const base={currentAge:65,birthYear:1961,birthDate:'1961-01-01',retirementAge:65,planEndAge:91,balance:1200000,annualContribution:0,returnPreRetirement:5,returnRetirement:5,baseAnnualSpending:96000,ssAlreadyReceiving:true,ssCurrentMonthly:45000/12,ssPiaMonthly:0,ssClaimAge:66,spouseSsAlreadyReceiving:true,spouseSsCurrentMonthly:0,spouseSsMonthly:0,spouseSsClaimAge:67,otherGuaranteedAnnual:0,withdrawalRate:.04,inflation:2.5,colaRate:2.5,filingStatus:'married',taxDeferredPct:90,spouseIsBeneficiary:false,spouseAge:null,useStandardDeduction:true,portfolioWithdrawalStartAge:66,temporaryCash:51000,volatilityPct:10,numSims:1000,monthlyAllowance:25,...(process.env.RB_TAX_ALLOWANCE_INPUT?JSON.parse(fs.readFileSync(process.env.RB_TAX_ALLOWANCE_INPUT,'utf8')):{})};
let checks=0;function near(a,b){checks++;assert(Math.abs(a-b)<1e-5,`${a} != ${b}`);}
const charts={},nodes=new Map(),payloads=[],radio={value:'estimate'},timers=[];let saved=null,csv='';
class Element {
 constructor(id=''){this.id=id;this.type='number';this.tagName='INPUT';this.value='0';this.checked=false;this.style={};this.dataset={};this.children=[];this.events={};this.classList={toggle(){}};}
 set value(v){this._value=v==null?'':String(v);} get value(){return this._value;}
 set textContent(v){this._text=v;this.children=[];for(const id of ['scenarioNameInput','confirmSaveScenario','scenarioLoadSelect'])nodes.delete(id);}get textContent(){return this._text;}
 set innerHTML(v){this._html=v;if(v.includes('scenarioNameInput')){nodes.set('scenarioNameInput',new Element('scenarioNameInput'));nodes.set('confirmSaveScenario',new Element('confirmSaveScenario'));}}get innerHTML(){return this._html;}
 addEventListener(n,fn){this.events[n]=fn;}setAttribute(){}focus(){}scrollIntoView(){}click(){}getContext(){return this.id;}
 appendChild(child){this.children.push(child);if(child.id)nodes.set(child.id,child);}
}
function get(id){if(['scenarioNameInput','confirmSaveScenario','scenarioLoadSelect'].includes(id))return nodes.get(id)||null;if(!nodes.has(id))nodes.set(id,new Element(id));return nodes.get(id);}
class PlanningDate extends Date{constructor(...args){super(...(args.length?args:['2026-10-29T12:00:00Z']));}}
function request(url,opts){
 if(url.includes('generate_retirement_plan_pdf.php')){payloads.push(JSON.parse(opts.body));return {then(){return this;},catch(){return this;},finally(){return this;}};}
 if(url.includes('save_scenario.php')){saved=JSON.parse(opts.body);return Promise.resolve({json:async()=>({success:true})});}
 if(url.includes('load_scenarios.php'))return Promise.resolve({json:async()=>({success:true,scenarios:[{scenario_name:saved.scenario_name,data:saved.scenario_data}]})});
 throw new Error('Unexpected synthetic request');
}
const ui={Chart:function(node,config){charts[node.id||node]=config;this.destroy=()=>{};},console,Date:PlanningDate,Number,isPremiumUser:true,RBFinance:c.RBFinance,RBPlanEngine:c.RBPlanEngine,RBMonteCarlo:c.RBMonteCarlo,location:{origin:'https://synthetic.invalid',pathname:'/retirement-plan/'},
 document:{getElementById:get,addEventListener(){},createElement:()=>new Element(),querySelector(selector){const m=selector.match(/\[value="([^"]+)"\]/);return m?{set checked(v){if(v)radio.value=m[1];}}:radio;}},
 alert(message){throw new Error(message);},setTimeout:fn=>timers.push(fn),fetch:request,rbScenarioFetch:request,Blob:function(parts){csv=parts.join('');},URL:{createObjectURL(){return 'synthetic';}}};
ui.window=ui;
let source=fs.readFileSync(path.join(root,'retirement-plan/calculator.js'),'utf8');
source=source.replace(/\}\)\(\);\s*$/, 'window.allowanceTest={collect:collectInputs,validate:validateInputs,saveData:getFormDataForSave,apply:applyFormData,save:saveScenario,load:loadScenario,display:displayResults,csv:exportCsv,pdf:downloadPdf};})();');
vm.runInNewContext(source,ui);const h=ui.allowanceTest;
// Exercise legacy scenarios through the real loader before populating the fixture.
h.apply({});
const values={birthYear:base.birthYear,birthDate:base.birthDate,retirementAge:base.retirementAge,planEndAge:base.planEndAge,balance:base.balance,temporaryCash:base.temporaryCash,portfolioWithdrawalStartAge:base.portfolioWithdrawalStartAge,annualContribution:0,returnPreRetirement:base.returnPreRetirement,returnRetirement:base.returnRetirement,currentMonthlySpending:base.baseAnnualSpending/12,annualSpendingDirect:base.baseAnnualSpending,retirementSpendingPct:100,ssCurrentMonthly:base.ssCurrentMonthly,ssPiaMonthly:0,ssClaimAge:base.ssClaimAge,spouseSsCurrentMonthly:0,spouseSsMonthly:0,spouseSsClaimAge:67,otherGuaranteedAnnual:0,otherIncomeStartAge:'',withdrawalRate:4,inflation:2.5,colaRate:2.5,filingStatus:'married',taxDeferredPct:90,spouseBeneficiary:'no',spouseAge:'',volatility:10,simulations:1000,federalTaxIncludedMonthly:0};
for(const [id,v] of Object.entries(values))get(id).value=v;
for(const id of ['alreadyRetired','ssAlreadyReceiving','spouseSsAlreadyReceiving']){get(id).type='checkbox';get(id).checked=true;}
const run=x=>c.RBPlanEngine.runDeterministicPlan(x),mc=(x,d,v=10)=>c.RBMonteCarlo.runRetirementStressTest(x,d,{expectedReturnPct:5,volatilityPct:v,numSims:1000});
(async()=>{
 const legacyInputs=h.collect(),legacy=run(legacyInputs),legacyMc=mc(legacyInputs,legacy);
 assert.equal(legacyInputs.projectionStartYear,null);
 // Explicitly choosing the same year must preserve every original financial row.
 const same={...h.saveData(),projectionStartYear:2026};h.apply(same);
 const sameInputs=h.collect(),sameResult=run(sameInputs);
 const strip=result=>JSON.parse(JSON.stringify(result,(k,v)=>k==='year'?undefined:v));
 assert.deepEqual(strip(sameResult),strip(legacy));
 assert.deepEqual(JSON.parse(JSON.stringify(mc(sameInputs,sameResult))),JSON.parse(JSON.stringify(legacyMc)));
 // A future start is an opening balance, with no prior-year bridge or growth.
 const future={...same,projectionStartYear:2027,portfolioWithdrawalStartAge:66,temporaryCash:0,federalTaxIncludedMonthly:200,spouseAge:66,spouseSsCurrentMonthly:500,ssCurrentMonthly:4000};
 h.apply(future);const inputs=h.collect();assert.equal(h.validate(inputs).length,0);
 const result=run(inputs),first=result.years[0];
 assert.equal(inputs.currentAge,66);assert.equal(inputs.retirementAge,66);
 assert.equal(first.year,2027);assert.equal(first.age,66);near(first.balanceStart,base.balance);
 near(first.requestedSpending,base.baseAnnualSpending-2400);near(first.socialSecurity,54000);
 near(first.temporaryCashUsed,0);assert(first.withdrawal>0);assert.equal(result.summary.shortfallAge,null);
 assert(result.years.every(y=>y.year>=2027));assert.equal(result.years.at(-1).age,base.planEndAge);
 near(result.years[1].requestedSpending,first.requestedSpending*1.025);near(result.years[1].socialSecurity,first.socialSecurity*1.025);
 const firstRmd=result.years.find(y=>y.rmd>0);assert.equal(firstRmd.age,75);assert.equal(firstRmd.year,2036);
 const flat=mc(inputs,result,0);near(flat.p50,result.summary.endingBalance);assert.equal(flat.startAge,66);assert.equal(flat.yearsToModel,result.years.length);
 assert.equal(flat.successRate,100);
 const savedData=h.saveData();assert.equal(savedData.projectionStartYear,2027);
 h.save();get('scenarioNameInput').value='Synthetic future start';get('confirmSaveScenario').events.click();await new Promise(setImmediate);
 assert.equal(saved.scenario_data.projectionStartYear,2027);
 h.apply({...same,projectionStartYear:2028});h.load();await new Promise(setImmediate);get('saveStatus').children[0].value='0';get('saveStatus').children[1].events.click();
 assert.equal(h.collect().projectionStartYear,2027);assert.deepEqual(strip(run(h.collect())),strip(result));
 h.display(result,inputs);h.csv();h.pdf();const pdf=payloads.at(-1);
 if(process.env.RB_START_YEAR_QA_PAYLOAD)fs.writeFileSync(process.env.RB_START_YEAR_QA_PAYLOAD,JSON.stringify(pdf));
 assert.equal(pdf.inputs.projectionStartYear,2027);assert.equal(pdf.projections[0].year,2027);assert.equal(pdf.projections[0].age,66);
 assert.equal(charts.planChart.data.labels[0],'2027 (age 66)');assert(get('fullTableBody').innerHTML.includes('2027 / 66'));
 assert(csv.includes('Year,Age,Portfolio'));assert(csv.includes('\n2027,66,'));assert(!csv.includes('\n2026,'));
 assert(pdf.summary.incomeTimingText.includes('Projection begins 2027'));
 for(const year of [2027.5,0,2201,NaN]){h.apply({...future,projectionStartYear:year});assert(h.validate(h.collect()).length>0);assert.throws(()=>run(h.collect()));}
 h.apply({...future,projectionStartYear:2100});assert(h.validate(h.collect()).length>0);
 // Loading a legacy record after future-year use must reset the year and mode.
 const old={...same};delete old.projectionStartYear;h.apply(old);assert.equal(h.collect().projectionStartYear,null);assert.deepEqual(strip(run(h.collect())),strip(legacy));
 const stillLegacy=h.saveData();assert.equal(stillLegacy.projectionStartYear,null);h.apply(stillLegacy);assert.equal(h.collect().projectionStartYear,null);
 console.log('Projection start year passed: same-year financial regression, future opening balance, no prior rows/cash, allowance once, first-year SS/COLA, RMD cohort, MC parity, save/load, legacy reset, chart/timeline, CSV/PDF payloads and validation.');
})().catch(e=>{console.error(e);process.exitCode=1;});
