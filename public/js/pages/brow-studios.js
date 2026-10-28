/* Widgets inside the page (calculators, tabs, counters) */
// Reveals each step in sequence on load (skip if you just want it static).
  document.addEventListener('DOMContentLoaded', function () {
    var steps = document.querySelectorAll('#journeySteps .journey-step');
    steps.forEach(function (step, i) {
      step.style.opacity = '0';
      step.style.transform = 'translateY(8px)';
      step.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      setTimeout(function () {
        step.style.opacity = '1';
        step.style.transform = 'translateY(0)';
      }, i * 120);
    });
  });;
