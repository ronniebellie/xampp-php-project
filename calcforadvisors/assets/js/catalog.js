(function () {
  'use strict';
  fetch('catalog.php', {credentials: 'omit'}).then(function (r) {
    if (!r.ok) throw new Error('Catalog unavailable');
    return r.json();
  }).then(function (data) {
    if (!Array.isArray(data.calculators) || data.count !== data.calculators.length) return;
    document.querySelectorAll('[data-advisor-count]').forEach(function (el) { el.textContent = String(data.count); });
    var list = document.getElementById('advisor-catalog');
    if (!list) return;
    data.calculators.forEach(function (c) {
      var item = document.createElement('li');
      var features = ['save', 'compare', 'pdf', 'csv', 'ai'].filter(function (f) { return c[f] === true; });
      item.textContent = c.name + (features.length ? ' — supported Premium features: ' + features.join(', ').toUpperCase() : ' — core calculator');
      list.appendChild(item);
    });
  }).catch(function () { /* Static copy remains truthful without JavaScript. */ });
})();
