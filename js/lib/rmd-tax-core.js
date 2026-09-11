/** Versioned RMD rules and simplified 2026 federal tax estimates. */
(function (global) {
  'use strict';
  var federalTax = global.RBFederalTax;
  if (!federalTax && typeof module !== 'undefined' && module.exports) federalTax = require('./federal-tax-2026.js');
  var RMD_RULE_VERSION = 'IRS-Pub-590-B-2025';
  var rmdDivisors = {
    73:26.5,74:25.5,75:24.6,76:23.7,77:22.9,78:22.0,79:21.1,80:20.2,81:19.4,82:18.5,83:17.7,84:16.8,85:16.0,86:15.2,87:14.4,88:13.7,89:12.9,90:12.2,91:11.5,92:10.8,93:10.1,94:9.5,95:8.9,96:8.4,97:7.8,98:7.3,99:6.8,100:6.4,101:6.0,102:5.6,103:5.2,104:4.9,105:4.6,106:4.3,107:4.1,108:3.9,109:3.7,110:3.5,111:3.4,112:3.3,113:3.1,114:3.0,115:2.9,116:2.8,117:2.7,118:2.5,119:2.3,120:2.0
  };
  var jointLifeExpectancy = {'73_62':27.2};
  var singleLifeExpectancy = {60:27.1,61:26.2,62:25.4,63:24.5,64:23.7,65:22.9,66:22.0,67:21.2,68:20.4,69:19.6,70:18.8,71:18.0,72:17.2,73:16.4,74:15.6,75:14.8,76:14.1,77:13.3,78:12.6,79:11.9,80:11.2,81:10.5,82:9.9,83:9.3,84:8.7,85:8.1,86:7.6,87:7.1,88:6.6,89:6.1,90:5.7,91:5.3,92:4.9,93:4.6,94:4.3,95:4.0,96:3.7,97:3.4,98:3.2,99:3.0,100:2.8,101:2.6,102:2.5,103:2.3,104:2.2,105:2.1,106:2.1,107:2.1,108:2.0,109:2.0,110:2.0,111:2.0,112:2.0,113:1.9,114:1.9,115:1.8,116:1.8,117:1.6,118:1.4,119:1.1,120:1.0};
  var taxBrackets2026 = federalTax.brackets;
  var standardDeductions2026 = federalTax.deductions;
  function unsupported(reason){return {supported:false,reason:reason};}
  function rmdStartAgeForBirthYear(birthYear){
    var year=Number(birthYear);
    if(!Number.isInteger(year)) return unsupported('A valid owner birth year is required.');
    if(year>=1960) return {supported:true,age:75};
    if(year>=1951) return {supported:true,age:73};
    return unsupported('Required-beginning-age cohorts born before 1951 are not covered by the offline Phase 3 reference package.');
  }
  function normalizeFilingStatus(status){if(status==='married'||status==='married_filing_jointly')return 'married';if(status==='hoh'||status==='head')return 'hoh';return 'single';}
  function getRMDDivisorResult(ownerAge,isSpouseSoleBeneficiary,spouseAge){
    var age=Number(ownerAge),spouse=Number(spouseAge);
    var tableII=Boolean(isSpouseSoleBeneficiary)&&Number.isFinite(spouse)&&age-spouse>10;
    if(tableII){var value=jointLifeExpectancy[age+'_'+spouse];return value==null?unsupported('IRS Table II divisor is unavailable for owner age '+age+' and spouse age '+spouse+'; Table III substitution is prohibited.'):{supported:true,divisor:value,table:'II'};}
    if(rmdDivisors[age]==null)return unsupported('IRS Table III divisor is unavailable for owner age '+age+'.');
    return {supported:true,divisor:rmdDivisors[age],table:'III'};
  }
  function getRMDDivisor(ownerAge,isSpouseSoleBeneficiary,spouseAge){var result=getRMDDivisorResult(ownerAge,isSpouseSoleBeneficiary,spouseAge);return result.supported?result.divisor:null;}
  function getSingleLifeExpectancy(age){var numeric=Number(age);if(Number.isInteger(numeric)&&numeric>=120)return 1.0;return singleLifeExpectancy[numeric]==null?null:singleLifeExpectancy[numeric];}
  function resolveRMD(opts){
    opts=opts||{};var age=Number(opts.ownerAge),balance=Number(opts.priorYearEndBalance);
    if(!Number.isFinite(age)||!Number.isFinite(balance)||balance<0)return unsupported('Valid owner age and nonnegative prior-year-end balance are required.');
    var start=rmdStartAgeForBirthYear(Number(opts.birthYear));if(!start.supported)return start;
    if(age<start.age||balance===0)return {supported:true,required:false,amount:0,divisor:null,table:null,rmdStartAge:start.age};
    var divisor=getRMDDivisorResult(age,opts.isSpouseSoleBeneficiary,opts.spouseAge);if(!divisor.supported)return divisor;
    return {supported:true,required:true,amount:balance/divisor.divisor,divisor:divisor.divisor,table:divisor.table,rmdStartAge:start.age};
  }
  function calculateRMD(age,priorYearEndBalance,isSpouseSoleBeneficiary,spouseAge,birthYear){var result=resolveRMD({ownerAge:age,priorYearEndBalance:priorYearEndBalance,isSpouseSoleBeneficiary:isSpouseSoleBeneficiary,spouseAge:spouseAge,birthYear:birthYear});if(!result.supported)throw new RangeError(result.reason);return result.amount;}
  function calculateFederalTax(taxableIncome,filingStatus){var brackets=taxBrackets2026[normalizeFilingStatus(filingStatus)],tax=0;for(var i=0;i<brackets.length;i++){var bracket=brackets[i];tax+=Math.min(Math.max(0,taxableIncome-bracket.min),bracket.max-bracket.min)*bracket.rate;if(taxableIncome<=bracket.max)break;}return tax;}
  function getMarginalRate(taxableIncome,filingStatus){var brackets=taxBrackets2026[normalizeFilingStatus(filingStatus)];for(var i=0;i<brackets.length;i++)if(taxableIncome>=brackets[i].min&&taxableIncome<brackets[i].max)return brackets[i].rate;return brackets[brackets.length-1].rate;}
  function estimateTaxableIncome(portfolioWithdrawal,socialSecurity,otherIncome,filingStatus,useStandardDeduction){var gross=(portfolioWithdrawal||0)+(otherIncome||0)+(socialSecurity||0)*0.5;var deduction=useStandardDeduction!==false?standardDeductions2026[normalizeFilingStatus(filingStatus)]:0;return Math.max(0,gross-deduction);}
  var api={RMD_RULE_VERSION:RMD_RULE_VERSION,RMD_START_AGE:null,rmdStartAgeForBirthYear:rmdStartAgeForBirthYear,getSingleLifeExpectancy:getSingleLifeExpectancy,getRMDDivisorResult:getRMDDivisorResult,getRMDDivisor:getRMDDivisor,resolveRMD:resolveRMD,calculateRMD:calculateRMD,calculateFederalTax:calculateFederalTax,getMarginalRate:getMarginalRate,estimateTaxableIncome:estimateTaxableIncome,normalizeFilingStatus:normalizeFilingStatus};
  if(typeof module!=='undefined'&&module.exports)module.exports=api;else global.RBTaxRmd=api;
})(typeof window!=='undefined'?window:this);
