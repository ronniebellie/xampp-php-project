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
 set value(v){this._value=String(v);} get value(){return this._value;}
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
const values={birthYear:base.birthYear,birthDate:base.birthDate,retirementAge:base.retirementAge,planEndAge:base.planEndAge,balance:base.balance,temporaryCash:base.temporaryCash,portfolioWithdrawalStartAge:base.portfolioWithdrawalStartAge,annualContribution:0,returnPreRetirement:base.returnPreRetirement,returnRetirement:base.returnRetirement,currentMonthlySpending:base.baseAnnualSpending/12,annualSpendingDirect:base.baseAnnualSpending,retirementSpendingPct:100,ssCurrentMonthly:base.ssCurrentMonthly,ssPiaMonthly:0,ssClaimAge:base.ssClaimAge,spouseSsCurrentMonthly:0,spouseSsMonthly:0,spouseSsClaimAge:67,otherGuaranteedAnnual:0,otherIncomeStartAge:'',withdrawalRate:4,inflation:2.5,colaRate:2.5,filingStatus:'married',taxDeferredPct:90,spouseBeneficiary:'no',spouseAge:'',volatility:10,simulations:1000,federalTaxIncludedMonthly:0};
for(const [id,v] of Object.entries(values))get(id).value=v;
for(const id of ['alreadyRetired','ssAlreadyReceiving','spouseSsAlreadyReceiving']){get(id).type='checkbox';get(id).checked=true;}
const run=x=>c.RBPlanEngine.runDeterministicPlan(x),mc=(x,d,v=10)=>c.RBMonteCarlo.runRetirementStressTest(x,d,{expectedReturnPct:5,volatilityPct:v,numSims:1000});
(async()=>{
 const zero=h.collect();assert.equal(h.validate(zero).length,0);const original=run(zero),before=mc(zero,original);
 assert.deepEqual(JSON.parse(JSON.stringify(original)),JSON.parse(JSON.stringify(run(base))),'$0 allowance must preserve the original engine output exactly');
 get('federalTaxIncludedMonthly').value=base.monthlyAllowance;
 const adjusted=h.collect();assert.equal(h.validate(adjusted).length,0);
 near(adjusted.enteredAnnualSpending,base.baseAnnualSpending);near(adjusted.annualFederalTaxAllowanceRemoved,base.monthlyAllowance*12);near(adjusted.baseAnnualSpending,base.baseAnnualSpending-base.monthlyAllowance*12);
 const normalized=JSON.stringify(adjusted),result=run(adjusted),after=mc(adjusted,result);
 assert.equal(JSON.stringify(adjusted),normalized,'Deterministic and MC must not subtract again or mutate inputs');
 const manual=run({...base,baseAnnualSpending:adjusted.baseAnnualSpending});
 assert.deepEqual(JSON.parse(JSON.stringify(result)),JSON.parse(JSON.stringify(manual)),'Actual taxes, RMDs and every cash flow must match the unchanged engine with a manually netted baseline');
 assert(result.years.some(y=>y.federalTax>0),'The allowance must not disable actual taxes');
 for(const y of result.years){
  near(y.requestedSpending,adjusted.baseAnnualSpending*Math.pow(1.025,y.age-base.currentAge));
  near(y.balanceStart+y.socialSecurity+y.otherIncome+y.temporaryCashUsed+y.investmentReturn-y.fundedSpending-y.taxesPaid,y.balanceEnd);
  if(y.age<base.portfolioWithdrawalStartAge)near(y.withdrawal,0);
 }
 near(result.years[0].temporaryCashUsed,adjusted.baseAnnualSpending-base.ssCurrentMonthly*12);
 const flat=mc(adjusted,result,0);near(flat.p50,result.summary.endingBalance);assert.equal(flat.successRate,result.summary.shortfallAge===null?100:0);
 // Saved data keeps the original household budget and raw monthly allowance.
 const data=JSON.parse(JSON.stringify(h.saveData()));assert.equal(data.federalTaxIncludedMonthly,base.monthlyAllowance);near(data.currentMonthlySpending,base.baseAnnualSpending/12);
 h.save();get('scenarioNameInput').value='Synthetic tax allowance';get('confirmSaveScenario').events.click();await new Promise(setImmediate);
 assert.equal(saved.scenario_data.federalTaxIncludedMonthly,base.monthlyAllowance);
 get('federalTaxIncludedMonthly').value=999;h.load();await new Promise(setImmediate);get('saveStatus').children[0].value='0';get('saveStatus').children[1].events.click();
 near(h.collect().baseAnnualSpending,adjusted.baseAnnualSpending);assert.equal(get('federalTaxIncludedMonthly').value,String(base.monthlyAllowance));
 const legacy={...data};delete legacy.federalTaxIncludedMonthly;h.apply(legacy);assert.equal(get('federalTaxIncludedMonthly').value,'0');near(h.collect().baseAnnualSpending,base.baseAnnualSpending);
 h.apply(data);
 // Direct annual spending uses the same monthly-dollar allowance, no hidden percentage.
 radio.value='direct';near(h.collect().baseAnnualSpending,adjusted.baseAnnualSpending);
 radio.value='estimate';get('retirementSpendingPct').value=80;const scaled=h.collect();near(scaled.baseAnnualSpending,(base.baseAnnualSpending-base.monthlyAllowance*12)*.8);near(scaled.annualFederalTaxAllowanceRemoved,base.monthlyAllowance*12*.8);get('retirementSpendingPct').value=100;
 for(const value of [-1,'nonsense',Infinity,base.baseAnnualSpending/12+1]){get('federalTaxIncludedMonthly').value=value;assert(h.validate(h.collect()).some(e=>e.includes('federal-tax allowance')));}
 get('federalTaxIncludedMonthly').value=base.baseAnnualSpending/12;assert.equal(h.validate(h.collect()).length,0,'Equal allowance is permitted; non-tax spending can be zero');
 get('federalTaxIncludedMonthly').value=base.monthlyAllowance;
 const savedBeforeInvalid=JSON.stringify(saved);h.save();get('scenarioNameInput').value='Invalid synthetic allowance';
 for(const value of [-1,base.baseAnnualSpending/12+1]){get('federalTaxIncludedMonthly').value=value;assert.throws(()=>get('confirmSaveScenario').events.click(),/federal-tax allowance/);assert.equal(JSON.stringify(saved),savedBeforeInvalid,'Invalid allowance must not reach save API');}
 get('saveStatus').textContent='';get('federalTaxIncludedMonthly').value=base.monthlyAllowance;
 h.display(result,adjusted);assert(get('spendingBasisNote').textContent.includes('$'+(base.monthlyAllowance*12).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})));
 assert.equal(parseFloat(get('mcSuccessRate').textContent),after.successRate);near(charts.planChart.data.datasets[0].data.at(-1),Math.round(result.summary.endingBalance));
 h.csv();h.pdf();const pdf=payloads.at(-1);near(pdf.inputs.baseAnnualSpending,adjusted.baseAnnualSpending);near(pdf.inputs.annualFederalTaxAllowanceRemoved,base.monthlyAllowance*12);assert(pdf.summary.incomeTimingText.includes('Annual non-tax spending baseline'));
 near(pdf.projections[0].requestedSpending,adjusted.baseAnnualSpending);near(pdf.projections[0].temporaryCashUsed,result.years[0].temporaryCashUsed);
 assert(csv.includes('Federal-tax budget allowance removed'));const lines=csv.trim().split('\n'),idx=lines.findIndex(x=>x.startsWith('Age,Portfolio,')),header=lines[idx].split(','),first=lines[idx+1].split(',');near(Number(first[header.indexOf('Requested Spending')]),adjusted.baseAnnualSpending);
 console.log(JSON.stringify({checks,annualEntered:adjusted.enteredAnnualSpending,annualAllowanceRemoved:adjusted.annualFederalTaxAllowanceRemoved,annualNonTaxBaseline:adjusted.baseAnnualSpending,zeroAllowance:{success:before.successRate,endingPortfolio:original.summary.endingBalance,temporaryCashRequired:original.years[0].temporaryCashUsed},withAllowance:{success:after.successRate,endingPortfolio:result.summary.endingBalance,temporaryCashRequired:result.years[0].temporaryCashUsed,unusedTemporaryCash:result.summary.temporaryCashRemaining},save_load_validation_inflation_pdf_csv_checks:'passed'},null,2));
})().catch(e=>{console.error(e);process.exitCode=1;});
