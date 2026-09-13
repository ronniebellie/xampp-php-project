/** Supported inherited-IRA branches from IRS Publication 590-B (2025). */
(function(global){'use strict';
function unsupported(reason){return {supported:false,reason:reason};}
function classify(opts){
  opts=opts||{};var category=opts.beneficiaryCategory;
  if(typeof opts.ownerDiedBeforeRequiredBeginningDate!=='boolean')return unsupported('Explicit confirmation of death before or on/after the owner required beginning date is required; unknown RBD status is unsupported.');
  if(category!=='noneligible_designated'&&category!=='eligible_designated_elects_10_year')return unsupported('This beneficiary category is not implemented from the available authoritative material.');
  if(opts.ownerDiedBeforeRequiredBeginningDate===true)return {supported:true,rule:'10-year-pre-rbd',deadlineYear:10,annualDistributionsRequired:false,description:'No distribution is required before year 10; voluntary distributions are allowed and the account must be empty by the end of year 10.'};
  if(category!=='noneligible_designated')return unsupported('Post-required-beginning-date life-expectancy mechanics for this eligible designated-beneficiary category are not implemented.');
  return {supported:true,rule:'10-year-post-rbd',deadlineYear:10,annualDistributionsRequired:true,description:'Annual life-expectancy RMDs apply during years 1-9 and the account must be empty by the end of year 10.'};
}
function postRbdInitialDivisor(opts){
  opts=opts||{};var lookup=opts.getSingleLifeExpectancy;
  if(typeof lookup!=='function')return unsupported('Verified Table I lookup is required.');
  var beneficiary=lookup(opts.beneficiaryAgeFirstDistributionYear);
  var ownerAtDeath=lookup(opts.ownerAgeAtDeath);
  if(typeof beneficiary!=='number'||!Number.isFinite(beneficiary)||beneficiary<=0||typeof ownerAtDeath!=='number'||!Number.isFinite(ownerAtDeath)||ownerAtDeath<=0)return unsupported('Table I does not cover the beneficiary first-distribution age or owner age at death.');
  var ownerRemaining=ownerAtDeath-1;
  return {supported:true,divisor:Math.max(beneficiary,ownerRemaining),beneficiaryDivisor:beneficiary,ownerRemainingDivisor:ownerRemaining,basis:beneficiary>=ownerRemaining?'beneficiary':'owner'};
}
function postRbdDivisor(initialDivisor,distributionYear){var year=distributionYear,initial=initialDivisor;if(typeof year!=='number'||!Number.isInteger(year)||year<1||year>9||typeof initial!=='number'||!Number.isFinite(initial))return null;var value=initial-(year-1);return value>0?value:null;}
function postRbdRequiredDistribution(priorYearEndBalance,initialDivisor,distributionYear){var divisor=postRbdDivisor(initialDivisor,distributionYear),balance=priorYearEndBalance;if(divisor==null||typeof balance!=='number'||!Number.isFinite(balance)||balance<0)return unsupported('Valid post-RBD balance, initial divisor, and distribution year 1-9 are required.');return {supported:true,divisor:divisor,amount:balance/divisor};}
var api={RULE_VERSION:'IRS-Pub-590-B-2025-expanded',classify:classify,postRbdInitialDivisor:postRbdInitialDivisor,postRbdDivisor:postRbdDivisor,postRbdRequiredDistribution:postRbdRequiredDistribution};if(typeof module!=='undefined'&&module.exports)module.exports=api;else global.RBInheritedIraRules=api;
})(typeof window!=='undefined'?window:this);
