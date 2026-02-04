document.addEventListener("DOMContentLoaded", function () {
  const calculateBtn = document.getElementById("calculate");
  const assumptionsSection = document.getElementById("assumptions");
  const assumptionsOutput = document.getElementById("assumptionsOutput");
  const modeSelect = document.getElementById("mode");

  if (!calculateBtn || !assumptionsSection || !assumptionsOutput || !modeSelect) return;

  calculateBtn.addEventListener("click", function () {
    const mode = modeSelect.value;

    const assumptions = {
      mode: mode,
      projectionStart: "current month",
      endAge: 90,
      colaMonth: "January (30-year avg shown)",
      survivorModel: mode === "couple" ? "simplified survivor modeling ON" : "n/a",
      breakevenBaseline: "FRA",
      benefitModel: "retirement-only",
      exclusions: [
        "no taxes",
        "no earnings test",
        "no Medicare / IRMAA",
        "no spousal benefits"
      ]
    };

    assumptionsOutput.textContent = JSON.stringify(assumptions, null, 2);
    assumptionsSection.style.display = "block";
  });
});