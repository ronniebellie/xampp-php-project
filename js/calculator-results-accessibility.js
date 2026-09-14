/** Announce completed result updates without reading an entire projection table. */
(function () {
  function setup() {
    const regions = Array.from(document.querySelectorAll('#results, #compoundResults, #singleResults, #targetResults, #annuityResults'));
    if (!regions.length || typeof MutationObserver === 'undefined') return;
    const status = document.createElement('p');
    status.className = 'rb-result-announcement';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    document.body.appendChild(status);
    let timer;
    regions.forEach(region => {
      region.setAttribute('role', 'region');
      if (!region.hasAttribute('aria-label') && !region.hasAttribute('aria-labelledby')) region.setAttribute('aria-label', 'Calculation results');
      new MutationObserver(() => {
        if (!region.getClientRects().length) return;
        clearTimeout(timer);
        status.textContent = '';
        timer = setTimeout(() => { status.textContent = 'Calculation results updated. Review the results section.'; }, 250);
      }).observe(region, {subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ['style', 'hidden']});
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup);
  else setup();
})();
