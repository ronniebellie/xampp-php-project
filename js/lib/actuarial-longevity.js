/** Exact SSA 2021 period-life-table fixtures supplied for Phase 3. No interpolation. */
(function(global){'use strict';
var MALE_EX={0:73.54,22:52.55,50:28.12,60:20.41,62:19.00,65:16.95,67:15.63,70:13.69,73:11.82,75:10.62,80:7.92,85:5.65,89:4.21,90:3.90,95:2.76,100:2.09,105:1.58,110:1.16,113:0.95,119:0.60};
var FEMALE_EX={0:79.30,22:58.03,50:32.07,60:23.65,62:22.07,65:19.75,67:18.23,70:16.00,73:13.85,75:12.49,80:9.38,85:6.72,89:5.02,90:4.65,95:3.22,100:2.35,105:1.71,110:1.20,113:0.95,119:0.60};
function normalizeSex(sex){var s=String(sex||'male').toLowerCase();return s==='female'||s==='f'?'female':'male';}
function getRemainingLifeExpectancy(sex,currentAge){var age=Number(currentAge);if(!Number.isInteger(age))return null;var table=normalizeSex(sex)==='female'?FEMALE_EX:MALE_EX;return Object.prototype.hasOwnProperty.call(table,age)?table[age]:null;}
function getActuarialDeathAge(sex,currentAge){var remaining=getRemainingLifeExpectancy(sex,currentAge);return remaining==null?null:Math.round(Number(currentAge)+remaining);}
function ageFromBirthYear(birthYear,asOfYear){var y=asOfYear||new Date().getFullYear(),b=parseInt(birthYear,10);return Number.isFinite(b)?y-b:null;}
var api={DATA_VERSION:'SSA-2021-period-life-table-fixtures',getRemainingLifeExpectancy:getRemainingLifeExpectancy,getActuarialDeathAge:getActuarialDeathAge,ageFromBirthYear:ageFromBirthYear,normalizeSex:normalizeSex};if(typeof module!=='undefined'&&module.exports)module.exports=api;else global.RBActuarial=api;
})(typeof window!=='undefined'?window:this);
