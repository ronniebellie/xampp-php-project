/**
 * SSA period life expectancy (2021 table, e_x = expected remaining years).
 * Used for longevity modeling only — not benefit calculations.
 * Source: SSA Actuarial Life Table 2021 (rounded to one decimal).
 */
(function (global) {
  'use strict';

  var MALE_EX = {
    60: 21.5, 61: 20.7, 62: 19.8, 63: 19.0, 64: 18.2, 65: 17.4, 66: 16.6, 67: 15.7, 68: 14.9, 69: 14.1,
    70: 13.4, 71: 12.6, 72: 11.8, 73: 11.0, 74: 10.3, 75: 9.5, 76: 8.8, 77: 8.1, 78: 7.4, 79: 6.8,
    80: 6.2, 81: 5.6, 82: 5.0, 83: 4.5, 84: 3.9, 85: 3.5, 86: 3.0, 87: 2.7, 88: 2.3, 89: 2.0, 90: 1.7,
    91: 1.4, 92: 1.2, 93: 1.0, 94: 0.9, 95: 0.7, 96: 0.6, 97: 0.5, 98: 0.4, 99: 0.3, 100: 0.2
  };

  var FEMALE_EX = {
    60: 24.1, 61: 23.2, 62: 22.3, 63: 21.4, 64: 20.5, 65: 19.7, 66: 18.8, 67: 17.9, 68: 17.1, 69: 16.2,
    70: 15.4, 71: 14.6, 72: 13.8, 73: 13.0, 74: 12.2, 75: 11.4, 76: 10.7, 77: 9.9, 78: 9.2, 79: 8.5,
    80: 7.9, 81: 7.2, 82: 6.6, 83: 6.0, 84: 5.5, 85: 4.9, 86: 4.4, 87: 3.9, 88: 3.5, 89: 3.1, 90: 2.7,
    91: 2.3, 92: 2.0, 93: 1.7, 94: 1.4, 95: 1.2, 96: 1.0, 97: 0.8, 98: 0.7, 99: 0.5, 100: 0.4
  };

  function normalizeSex(sex) {
    var s = String(sex || 'male').toLowerCase();
    return s === 'female' || s === 'f' ? 'female' : 'male';
  }

  function lookupEx(sex, age) {
    var table = normalizeSex(sex) === 'female' ? FEMALE_EX : MALE_EX;
    var a = Math.round(age);
    if (a <= 60) return table[60];
    if (a >= 100) return table[100];
    return table[a] != null ? table[a] : table[60];
  }

  /** Expected remaining years of life from current age. */
  function getRemainingLifeExpectancy(sex, currentAge) {
    return lookupEx(sex, currentAge);
  }

  /** Actuarial death age = current age + remaining life expectancy (rounded). */
  function getActuarialDeathAge(sex, currentAge) {
    var age = Math.max(22, Math.min(100, Math.round(currentAge)));
    var remaining = lookupEx(sex, age);
    var deathAge = Math.round(age + remaining);
    return Math.max(age + 1, Math.min(100, deathAge));
  }

  function ageFromBirthYear(birthYear, asOfYear) {
    var y = asOfYear || new Date().getFullYear();
    return Math.max(22, Math.min(100, y - parseInt(birthYear, 10)));
  }

  global.RBActuarial = {
    getRemainingLifeExpectancy: getRemainingLifeExpectancy,
    getActuarialDeathAge: getActuarialDeathAge,
    ageFromBirthYear: ageFromBirthYear,
    normalizeSex: normalizeSex
  };
})(typeof window !== 'undefined' ? window : this);
