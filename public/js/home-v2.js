/**
 * Glowth home v2: tells the shared engine (public/js/v2.js, loaded next) which home grids and
 * mock-ups to bring to life, and runs the hero stage. Styles: public/css/home-v2.css.
 */
(function () {
  'use strict';

  window.GLOWTH_V2 = {
    // Card grids by Elementor id; photo cards keep their image background
    grids: {
      ba68542: 'photo', // Who we serve
      d3862d9: '',      // Industries
      e606669: '',      // Services, rows 1-3
      '59bda90': '',
      '134373c': '',
      a0e3781: '',      // 5-step process
      '1ef3e81': '',    // Guides
      '66d1c99': '',    // Where we work
      a289bcc: ''
    },
    // Product mock-ups: Google result (SEO, a white card), ad stats (Paid ads: white chips, on the left), AI chat
    mocks: { a3f3353: 'surface', fc525e6: 'left chips', '38e4604': '' }
  };

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  // Hero: the card leans toward the pointer, the notifications drift at their own depth
  function setupStage() {
    var hero = document.getElementById('bg');
    var stage = document.querySelector('[data-v2-stage]');
    if (!hero || !stage || reduce || !finePointer) return;
    var card = stage.querySelector('[data-v2-tilt]');
    var floats = stage.querySelectorAll('.gh-float');
    var frame = 0;
    hero.addEventListener('pointermove', function (e) {
      if (frame) return;
      frame = requestAnimationFrame(function () {
        frame = 0;
        var r = hero.getBoundingClientRect();
        var x = (e.clientX - r.left) / r.width - 0.5;
        var y = (e.clientY - r.top) / r.height - 0.5;
        if (card) {
          card.style.setProperty('--rx', (-y * 8).toFixed(2) + 'deg');
          card.style.setProperty('--ry', (x * 10).toFixed(2) + 'deg');
          card.classList.add('is-tilting');
        }
        floats.forEach(function (f) {
          var depth = parseFloat(f.getAttribute('data-depth')) || 1;
          f.style.setProperty('--px', (x * 18 * depth).toFixed(1) + 'px');
          f.style.setProperty('--py', (y * 14 * depth).toFixed(1) + 'px');
        });
      });
    });
    hero.addEventListener('pointerleave', function () {
      if (card) {
        card.classList.remove('is-tilting');
        card.style.removeProperty('--rx');
        card.style.removeProperty('--ry');
      }
      floats.forEach(function (f) {
        f.style.removeProperty('--px');
        f.style.removeProperty('--py');
      });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setupStage);
  else setupStage();
})();
