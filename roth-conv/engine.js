(function (root, factory) {
  const api = factory();
  if (typeof module === 'object' && module.exports) module.exports = api;
  root.RothEngine = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';

  const BASE_YEAR = 2026;
  const ORDINARY_BRACKETS = {
    single: [[12400,.10],[50400,.12],[105700,.22],[201775,.24],[256225,.32],[640600,.35],[Infinity,.37]],
    married: [[24800,.10],[100800,.12],[211400,.22],[403550,.24],[512450,.32],[768700,.35],[Infinity,.37]],
    married_separate: [[12400,.10],[50400,.12],[105700,.22],[201775,.24],[256225,.32],[384350,.35],[Infinity,.37]],
    head: [[17700,.10],[67450,.12],[105700,.22],[201750,.24],[256200,.32],[640600,.35],[Infinity,.37]]
  };
  const STANDARD_DEDUCTION = { single:16100, married:32200, married_separate:16100, head:24150 };
  const LTCG_BRACKETS = {
    single: [49450,545500], married:[98900,613700], married_separate:[49450,306850], head:[66200,579600]
  };
  const NIIT_THRESHOLD = { single:200000, married:250000, married_separate:125000, head:200000 };
  const IRMAA = {
    married:[[218000,0],[274000,95.70],[342000,240.40],[410000,385],[750000,529.60],[Infinity,578]],
    single:[[109000,0],[137000,95.70],[171000,240.40],[205000,385],[500000,529.60],[Infinity,578]],
    married_separate:[[109000,0],[391000,529.60],[Infinity,578]]
  };
  const RMD = {73:26.5,74:25.5,75:24.6,76:23.7,77:22.9,78:22,79:21.1,80:20.2,81:19.4,82:18.5,83:17.7,84:16.8,85:16,86:15.2,87:14.4,88:13.7,89:12.9,90:12.2,91:11.5,92:10.8,93:10.1,94:9.5,95:8.9,96:8.4,97:7.8,98:7.3,99:6.8,100:6.4,101:6,102:5.6,103:5.2,104:4.9,105:4.6,106:4.3,107:4.1,108:3.9,109:3.7,110:3.5,111:3.4,112:3.3,113:3.1,114:3,115:2.9,116:2.8,117:2.7,118:2.5,119:2.3,120:2};

  const num = (v, d=0) => Number.isFinite(Number(v)) ? Number(v) : d;
  const bool = (v, d=false) => v == null ? d : !(v === false || v === 'false' || v === '0' || v === 0);
  const inflationFactor = (year, rate) => Math.pow(1 + rate, Math.max(0, year - BASE_YEAR));
  const statusKey = s => s === 'married' || s === 'married_separate' || s === 'head' ? s : 'single';

  function bracketsFor(year, status, inflationRate) {
    const factor = inflationFactor(year, inflationRate);
    return ORDINARY_BRACKETS[statusKey(status)].map(([max, rate]) => [max === Infinity ? Infinity : max * factor, rate]);
  }

  function progressiveTax(income, brackets) {
    let tax = 0, floor = 0;
    for (const [ceiling, rate] of brackets) {
      const slice = Math.max(0, Math.min(income, ceiling) - floor);
      tax += slice * rate;
      if (income <= ceiling) break;
      floor = ceiling;
    }
    return tax;
  }

  function taxableSocialSecurity(benefits, otherIncome, taxExemptInterest, filingStatus) {
    benefits = Math.max(0, benefits); otherIncome = Math.max(0, otherIncome);
    if (!benefits) return 0;
    const married = filingStatus === 'married';
    const base = married ? 32000 : 25000;
    const upper = married ? 44000 : 34000;
    const provisional = otherIncome + Math.max(0, taxExemptInterest) + benefits * 0.5;
    if (provisional <= base) return 0;
    if (provisional <= upper) return Math.min(benefits * 0.5, (provisional - base) * 0.5);
    const baseTaxable = Math.min(benefits * 0.5, married ? 6000 : 4500);
    return Math.min(benefits * 0.85, baseTaxable + (provisional - upper) * 0.85);
  }

  function deductionFor(year, filingStatus, ages, magi, inflationRate) {
    const factor = inflationFactor(year, inflationRate);
    const status = statusKey(filingStatus);
    const seniors = ages.filter(a => a >= 65).length;
    const regularSeniorEach = status === 'single' || status === 'head' ? 2050 : 1650;
    const regularSenior = seniors * regularSeniorEach * factor;
    let enhancedSenior = 0;
    if (year <= 2028 && seniors) {
      const threshold = status === 'married' ? 150000 : 75000;
      const reduction = Math.max(0, magi - threshold) * 0.06;
      enhancedSenior = seniors * Math.max(0, 6000 - reduction);
    }
    return { base:STANDARD_DEDUCTION[status] * factor, regularSenior, enhancedSenior,
      total:STANDARD_DEDUCTION[status] * factor + regularSenior + enhancedSenior, seniors };
  }

  function federalTax(input) {
    const status = statusKey(input.filingStatus);
    const ordinaryBeforeSS = Math.max(0, input.ordinaryIncome || 0);
    const ltcg = Math.max(0, input.longTermCapitalGains || 0);
    const ss = taxableSocialSecurity(input.socialSecurity || 0, ordinaryBeforeSS + ltcg, input.taxExemptInterest || 0, status);
    const agi = ordinaryBeforeSS + ss + ltcg;
    const provisionalMagi = agi + Math.max(0, input.taxExemptInterest || 0);
    const deduction = deductionFor(input.year, status, input.ages || [], provisionalMagi, input.inflationRate || 0);
    const ordinaryTaxable = Math.max(0, ordinaryBeforeSS + ss - deduction.total);
    const taxableGains = Math.max(0, Math.min(ltcg, agi - deduction.total - ordinaryTaxable));
    const ordinaryTax = progressiveTax(ordinaryTaxable, bracketsFor(input.year, status, input.inflationRate || 0));
    const factor = inflationFactor(input.year, input.inflationRate || 0);
    const [zeroTop, fifteenTop] = LTCG_BRACKETS[status].map(v => v * factor);
    const zeroGain = Math.min(taxableGains, Math.max(0, zeroTop - ordinaryTaxable));
    const fifteenGain = Math.min(taxableGains - zeroGain, Math.max(0, fifteenTop - ordinaryTaxable - zeroGain));
    const twentyGain = Math.max(0, taxableGains - zeroGain - fifteenGain);
    const capitalGainsTax = fifteenGain * .15 + twentyGain * .20;
    const magi = provisionalMagi;
    const netInvestmentIncome = Math.max(0, (input.netInvestmentIncome || 0) + ltcg);
    const niit = input.includeNiit ? .038 * Math.min(netInvestmentIncome, Math.max(0, magi - NIIT_THRESHOLD[status])) : 0;
    const taxableIncome = ordinaryTaxable + taxableGains;
    return { federalTax:ordinaryTax + capitalGainsTax, ordinaryTax, capitalGainsTax, niit, taxableSocialSecurity:ss,
      taxableIncome, ordinaryTaxableIncome:ordinaryTaxable, magi, deduction };
  }

  function marginalRate(taxableIncome, year, status, inflationRate) {
    for (const [max, rate] of bracketsFor(year, status, inflationRate)) if (taxableIncome <= max) return rate;
    return .37;
  }

  function irmaaAnnual(magi, filingStatus, premiumYear, inflationRate, enrollees) {
    if (!enrollees) return 0;
    const key = filingStatus === 'married' ? 'married' : filingStatus === 'married_separate' ? 'married_separate' : 'single';
    const factor = inflationFactor(premiumYear, inflationRate);
    for (const [max, monthly] of IRMAA[key]) if (magi <= (max === Infinity ? Infinity : max * factor)) return monthly * factor * 12 * enrollees;
    return 0;
  }

  function normalize(data) {
    const currentAge = num(data.currentAge, 60), spouseAge = data.spouseAge === '' || data.spouseAge == null ? null : num(data.spouseAge);
    const socialSecuritySelf = num(data.socialSecuritySelf, 0), socialSecuritySpouse = num(data.socialSecuritySpouse, 0);
    const hasNewIncome = data.socialSecuritySelf != null || data.socialSecuritySpouse != null || data.otherOrdinaryIncome != null;
    return {
      currentAge, spouseAge, retirementAge:num(data.retirementAge, currentAge), lifeExpectancy:num(data.lifeExpectancy, 90),
      survivorLifeExpectancy:num(data.survivorLifeExpectancy, 95), deathAge:num(data.deathAge, 0), filingStatus:data.filingStatus || 'married',
      traditionalIRA:num(data.traditionalIRA), rothIRA:num(data.rothIRA), taxableAccount:num(data.taxableAccount), taxableCostBasis:num(data.taxableCostBasis),
      socialSecuritySelf, socialSecuritySpouse,
      otherOrdinaryIncome:hasNewIncome ? num(data.otherOrdinaryIncome) : num(data.currentIncome),
      survivorIncomePercent:num(data.survivorIncomePercent, 100)/100, survivorSpendingPercent:num(data.survivorSpendingPercent, 75)/100,
      annualOrdinaryInvestmentIncome:num(data.annualOrdinaryInvestmentIncome, num(data.investmentIncome)),
      annualLongTermGains:num(data.annualLongTermGains), taxExemptInterest:num(data.taxExemptInterest),
      conversionAmount:num(data.conversionAmount), conversionYears:num(data.conversionYears,1),
      returnRate:num(data.returnRate)/100, taxableReturnRate:num(data.taxableReturnRate, num(data.returnRate))/100,
      inflationRate:num(data.inflationRate)/100, discountRate:num(data.discountRate)/100,
      targetAfterTaxSpending:num(data.targetAfterTaxSpending), withdrawalMode:data.withdrawalMode || 'target_after_tax',
      annualPortfolioWithdrawalRate:num(data.annualPortfolioWithdrawalRate), withdrawalOrder:data.withdrawalOrder || 'traditional_to_bracket_then_roth',
      targetMarginalRate:num(data.targetMarginalRate,12)/100, taxPaymentSource:data.taxPaymentSource || 'taxable',
      includeIrmaa:bool(data.includeIrmaa,true), includeNiit:bool(data.includeNiit,true), medicareStartAge:num(data.medicareStartAge,65)
    };
  }

  function sellTaxable(state, gross) {
    gross = Math.max(0, Math.min(gross, state.taxable));
    if (!gross || !state.taxable) return { gross:0, gain:0 };
    const basisRatio = Math.max(0, Math.min(1, state.basis / state.taxable));
    const basisSold = gross * basisRatio;
    state.taxable -= gross; state.basis = Math.max(0, state.basis - basisSold);
    return { gross, gain:gross - basisSold };
  }

  function withdrawForCash(state, need, order, traditionalRoom) {
    const out = { traditional:0, roth:0, taxable:0, taxableGain:0 };
    const takeTrad = cap => { const x=Math.min(need,state.traditional,cap); state.traditional-=x; need-=x; out.traditional+=x; };
    const takeRoth = () => { const x=Math.min(need,state.roth); state.roth-=x; need-=x; out.roth+=x; };
    const takeTaxable = () => { const s=sellTaxable(state,need); need-=s.gross; out.taxable+=s.gross; out.taxableGain+=s.gain; };
    if (order === 'roth_then_traditional') { takeRoth(); takeTrad(Infinity); takeTaxable(); }
    else if (order === 'traditional_to_bracket_then_roth') { takeTrad(Math.max(0,traditionalRoom)); takeRoth(); takeTaxable(); takeTrad(Infinity); }
    else { takeTrad(Infinity); takeRoth(); takeTaxable(); }
    out.shortfall = Math.max(0,need); return out;
  }

  function project(config, doConversion) {
    const c=config, state={traditional:c.traditionalIRA,roth:c.rothIRA,taxable:c.taxableAccount,basis:Math.min(c.taxableCostBasis,c.taxableAccount)};
    const yearsPrimary=c.lifeExpectancy-c.currentAge;
    const yearsSurvivor=c.spouseAge==null ? yearsPrimary : c.survivorLifeExpectancy-c.spouseAge;
    const horizon=Math.max(0,c.deathAge>0 ? Math.max(c.deathAge-c.currentAge,yearsSurvivor) : yearsPrimary);
    const magiHistory={}, statusHistory={}, rows=[];
    let totalTaxesPaid=0,totalDiscountedTaxesPaid=0,totalRMDs=0,totalIrmaaPaid=0,totalNiitPaid=0,totalSpending=0;
    for(let i=0;i<=horizon;i++){
      const year=BASE_YEAR+i, primaryAge=c.currentAge+i, spouseAge=c.spouseAge==null?null:c.spouseAge+i;
      const survivor=c.deathAge>0 && primaryAge>c.deathAge;
      const filingStatus=survivor?'single':c.filingStatus;
      const ownerAge=survivor && spouseAge!=null?spouseAge:primaryAge;
      const ownerBirthYear=BASE_YEAR-(survivor&&c.spouseAge!=null?c.spouseAge:c.currentAge);
      const rmdStartAge=ownerBirthYear>=1960?75:73;
      const ages=survivor?[ownerAge]:[primaryAge].concat(spouseAge==null?[]:[spouseAge]);
      const ssBase=survivor?Math.max(c.socialSecuritySelf,c.socialSecuritySpouse):c.socialSecuritySelf+c.socialSecuritySpouse;
      const ss=ssBase*inflationFactor(year,c.inflationRate);
      const other=c.otherOrdinaryIncome*inflationFactor(year,c.inflationRate)*(survivor?c.survivorIncomePercent:1);
      const ordinaryInvestment=c.annualOrdinaryInvestmentIncome*inflationFactor(year,c.inflationRate);
      const scheduledGains=c.annualLongTermGains*inflationFactor(year,c.inflationRate);
      const taxExempt=c.taxExemptInterest*inflationFactor(year,c.inflationRate);
      const beginningTraditional=state.traditional;
      const rmd=ownerAge>=rmdStartAge && RMD[Math.min(120,Math.floor(ownerAge))]?Math.min(state.traditional,beginningTraditional/RMD[Math.min(120,Math.floor(ownerAge))]):0;
      state.traditional-=rmd; totalRMDs+=rmd;
      const conversionActive=doConversion && !survivor && i<c.conversionYears;
      const conversion=conversionActive?Math.min(c.conversionAmount,state.traditional):0;
      state.traditional-=conversion; state.roth+=conversion;
      const spending=c.withdrawalMode==='target_after_tax'&&c.targetAfterTaxSpending>0
        ?c.targetAfterTaxSpending*inflationFactor(year,c.inflationRate)*(survivor?c.survivorSpendingPercent:1)
        :(c.annualPortfolioWithdrawalRate/100)*(state.traditional+state.roth+state.taxable);
      let cash=ss+other+ordinaryInvestment+scheduledGains+rmd;
      let ordinaryWithdrawal=0,rothWithdrawal=0,taxableWithdrawal=0,realizedGain=0,taxFundingSold=0;
      const prelim=federalTax({year,filingStatus,ages,socialSecurity:ss,ordinaryIncome:other+ordinaryInvestment+rmd+conversion,longTermCapitalGains:scheduledGains,taxExemptInterest:taxExempt,inflationRate:c.inflationRate,includeNiit:c.includeNiit,netInvestmentIncome:ordinaryInvestment});
      const targetBracket=bracketsFor(year,filingStatus,c.inflationRate).find(b=>b[1]===c.targetMarginalRate);
      const traditionalRoom=targetBracket?Math.max(0,targetBracket[0]-prelim.taxableIncome):0;
      if(spending>cash){const w=withdrawForCash(state,spending-cash,c.withdrawalOrder,traditionalRoom);ordinaryWithdrawal+=w.traditional;rothWithdrawal+=w.roth;taxableWithdrawal+=w.taxable;realizedGain+=w.taxableGain;cash+=w.traditional+w.roth+w.taxable;}
      let taxResult,irmaa=0,allInTax=0;
      for(let pass=0;pass<8;pass++){
        taxResult=federalTax({year,filingStatus,ages,socialSecurity:ss,ordinaryIncome:other+ordinaryInvestment+rmd+conversion+ordinaryWithdrawal,longTermCapitalGains:scheduledGains+realizedGain,taxExemptInterest:taxExempt,inflationRate:c.inflationRate,includeNiit:c.includeNiit,netInvestmentIncome:ordinaryInvestment});
        const lookback=year-2, lookbackStatus=statusHistory[lookback]||filingStatus, lookbackMagi=magiHistory[lookback] == null ? taxResult.magi : magiHistory[lookback];
        const enrollees=c.includeIrmaa?(survivor?(ownerAge>=c.medicareStartAge?1:0):([primaryAge,spouseAge].filter(a=>a!=null&&a>=c.medicareStartAge).length)):0;
        irmaa=c.includeIrmaa?irmaaAnnual(lookbackMagi,lookbackStatus,year,c.inflationRate,enrollees):0;
        allInTax=taxResult.federalTax+taxResult.niit+irmaa;
        if(c.taxPaymentSource!=='taxable') break;
        const need=Math.max(0,allInTax-taxFundingSold);
        if(need<1||state.taxable<=0) break;
        const sale=sellTaxable(state,need); taxableWithdrawal+=sale.gross; taxFundingSold+=sale.gross; realizedGain+=sale.gain;
      }
      if(c.taxPaymentSource!=='taxable'){
        const w=withdrawForCash(state,allInTax,c.taxPaymentSource==='roth'?'roth_then_traditional':'traditional_then_roth',0);
        ordinaryWithdrawal+=w.traditional;rothWithdrawal+=w.roth;taxableWithdrawal+=w.taxable;realizedGain+=w.taxableGain;
      }
      const surplus=Math.max(0,cash-spending);
      if(surplus){state.taxable+=surplus;state.basis+=surplus;}
      state.traditional*=1+c.returnRate; state.roth*=1+c.returnRate; state.taxable*=1+c.taxableReturnRate;
      const discounted=allInTax/Math.pow(1+c.discountRate,i);
      totalTaxesPaid+=allInTax;totalDiscountedTaxesPaid+=discounted;totalIrmaaPaid+=irmaa;totalNiitPaid+=taxResult.niit;totalSpending+=spending;
      magiHistory[year]=taxResult.magi;statusHistory[year]=filingStatus;
      rows.push({age:primaryAge,survivorAge:survivor?ownerAge:null,year,filingStatus,conversion,rmd,totalWithdrawal:ordinaryWithdrawal+rothWithdrawal+taxableWithdrawal,
        traditionalWithdrawal:ordinaryWithdrawal,rothWithdrawal,taxableWithdrawal,realizedCapitalGain:realizedGain,socialSecurity:ss,taxableSocialSecurity:taxResult.taxableSocialSecurity,
        income:taxResult.magi-taxExempt,magi:taxResult.magi,taxableIncome:taxResult.taxableIncome,federalTax:taxResult.federalTax,irmaa,niit:taxResult.niit,allInTax,
        totalTaxesPaid,totalDiscountedTaxesPaid,netCash:spending,spending,traditionalBalance:state.traditional,rothBalance:state.roth,taxableBalance:state.taxable,
        standardDeduction:taxResult.deduction.total,enhancedSeniorDeduction:taxResult.deduction.enhancedSenior});
    }
    const last=rows[rows.length-1];
    return {totalTaxesPaid,totalDiscountedTaxesPaid,totalIrmaaPaid,totalNiitPaid,totalRMDs,totalSpending,yearlyData:rows,
      finalTraditionalBalance:last.traditionalBalance,finalRothBalance:last.rothBalance,finalTaxableBalance:last.taxableBalance,
      finalAfterTaxEstate:last.rothBalance+last.taxableBalance+last.traditionalBalance*(1-marginalRate(last.taxableIncome,last.year,last.filingStatus,c.inflationRate))};
  }

  function runRothAnalysis(data){
    const c=normalize(data), withConversion=project(c,true), withoutConversion=project(c,false);
    const firstWith=withConversion.yearlyData[0],firstWithout=withoutConversion.yearlyData[0];
    const conversionTaxCost=firstWith.allInTax-firstWithout.allInTax;
    const taxSavings=withoutConversion.totalTaxesPaid-withConversion.totalTaxesPaid;
    const discountedTaxSavings=withoutConversion.totalDiscountedTaxesPaid-withConversion.totalDiscountedTaxesPaid;
    let breakEvenAge=null,breakEvenAgeDiscounted=null;
    for(let i=0;i<withConversion.yearlyData.length;i++){
      if(breakEvenAge==null&&withConversion.yearlyData[i].totalTaxesPaid<withoutConversion.yearlyData[i].totalTaxesPaid)breakEvenAge=withConversion.yearlyData[i].age;
      if(breakEvenAgeDiscounted==null&&withConversion.yearlyData[i].totalDiscountedTaxesPaid<withoutConversion.yearlyData[i].totalDiscountedTaxesPaid)breakEvenAgeDiscounted=withConversion.yearlyData[i].age;
    }
    const conversionPeriodTaxDifference=withConversion.yearlyData.slice(0,c.conversionYears).reduce((sum,row,i)=>sum+row.allInTax-withoutConversion.yearlyData[i].allInTax,0);
    return {...c,withConversion,withoutConversion,taxSavings,discountedTaxSavings,netBenefit:taxSavings,discountedNetBenefit:discountedTaxSavings,conversionPeriodTaxDifference,
      conversionTaxCost,effectiveTaxRate:c.conversionAmount?conversionTaxCost/c.conversionAmount*100:0,
      rmdReduction:withoutConversion.totalRMDs-withConversion.totalRMDs,irmaaReduction:withoutConversion.totalIrmaaPaid-withConversion.totalIrmaaPaid,
      niitReduction:withoutConversion.totalNiitPaid-withConversion.totalNiitPaid,breakEvenAge,breakEvenAgeDiscounted,
      taxableIncome:firstWithout.taxableIncome,currentMarginalRate:marginalRate(firstWithout.taxableIncome,BASE_YEAR,firstWithout.filingStatus,c.inflationRate),
      marginalRateWithConversion:marginalRate(firstWith.taxableIncome,BASE_YEAR,firstWith.filingStatus,c.inflationRate),standardDeduction:firstWithout.standardDeduction,
      seniorDeductionAdded:firstWithout.standardDeduction-STANDARD_DEDUCTION[firstWithout.filingStatus],seniorCount:firstWithout.filingStatus==='married'?2:1,
      conversionStartAge:c.currentAge,conversionEndAge:c.currentAge+c.conversionYears-1};
  }

  return {runRothAnalysis,federalTax,taxableSocialSecurity,deductionFor,irmaaAnnual,bracketsFor,progressiveTax,RMD};
});
