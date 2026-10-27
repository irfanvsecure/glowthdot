/**
 * Glowth home v2: 3D reveal, tilt + glare on cards, floating mock-ups and the hero stage.
 * Styles: public/css/home-v2.css. Nothing here changes content; without JS the page is complete.
 */
(function () {
  'use strict';

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  // Card grids by Elementor id; photo cards keep their image background
  var GRIDS = {
    ba68542: 'photo',  // Who we serve
    d3862d9: '',       // Industries
    e606669: '',       // Services, rows 1-3
    '59bda90': '',
    '134373c': '',
    a0e3781: '',       // 5-step process
    '1ef3e81': '',     // Guides
    '66d1c99': '',     // Where we work
    a289bcc: ''
  };
  // Product mock-ups: Google result (SEO, a white card), ad stats (Paid ads: white chips, on the left), AI chat
  var MOCKS = { a3f3353: 'surface', fc525e6: 'left chips', '38e4604': '' };

  function byId(id) {
    return document.querySelector('[data-id="' + id + '"]');
  }

  function childCards(grid) {
    var out = [];
    var parent = grid.querySelector(':scope > .e-con-inner') || grid;
    for (var i = 0; i < parent.children.length; i++) {
      if (parent.children[i].classList.contains('e-con')) out.push(parent.children[i]);
    }
    return out;
  }

  function markCards() {
    var cards = [];
    Object.keys(GRIDS).forEach(function (id) {
      var grid = byId(id);
      if (!grid) return;
      childCards(grid).forEach(function (card, i) {
        card.classList.add('v2-card', 'v2-tilt');
        if (GRIDS[id] === 'photo') card.classList.add('v2-card--photo');
        card.style.setProperty('--d', Math.min(i, 6) * 70 + 'ms');
        cards.push(card);
      });
    });
    return cards;
  }

  function markMocks() {
    var mocks = [];
    Object.keys(MOCKS).forEach(function (id) {
      var el = byId(id);
      if (!el) return;
      el.classList.add('v2-mock');
      MOCKS[id].split(' ').forEach(function (mod) {
        if (mod) el.classList.add('v2-mock--' + mod);
      });
      mocks.push(el);
    });
    return mocks;
  }

  // Pointer position over an element as rotation (deg) and glare position (%)
  function tilt(el, max) {
    var frame = 0;
    el.addEventListener('pointermove', function (e) {
      if (frame) return;
      frame = requestAnimationFrame(function () {
        frame = 0;
        var r = el.getBoundingClientRect();
        var x = (e.clientX - r.left) / r.width;
        var y = (e.clientY - r.top) / r.height;
        el.style.setProperty('--rx', ((0.5 - y) * max).toFixed(2) + 'deg');
        el.style.setProperty('--ry', ((x - 0.5) * max).toFixed(2) + 'deg');
        el.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
        el.style.setProperty('--my', (y * 100).toFixed(1) + '%');
        el.classList.add('is-tilting');
      });
    });
    el.addEventListener('pointerleave', function () {
      el.classList.remove('is-tilting');
      el.style.removeProperty('--rx');
      el.style.removeProperty('--ry');
    });
  }

  function setupReveal(cards, mocks) {
    if (reduce || !('IntersectionObserver' in window)) return;
    var targets = [];
    document.querySelectorAll('.v2-sec').forEach(function (sec) {
      var n = 0;
      sec.querySelectorAll('.v2-kicker, .v2-title').forEach(function (h) {
        var w = h.closest('.elementor-widget');
        if (!w) return;
        w.style.setProperty('--d', n++ * 90 + 'ms');
        targets.push(w);
        // The intro right under a title rises with it
        var next = w.nextElementSibling;
        if (h.classList.contains('v2-title') && next && next.classList.contains('elementor-widget-text-editor')) {
          next.style.setProperty('--d', n++ * 90 + 'ms');
          targets.push(next);
        }
      });
    });
    document.querySelectorAll('.gl-fix__head, .gl-fix__body').forEach(function (el, i) {
      el.style.setProperty('--d', i * 120 + 'ms');
      targets.push(el);
    });
    targets = targets.concat(cards, mocks);

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (e) {
          if (!e.isIntersecting) return;
          var el = e.target;
          el.classList.add('is-in');
          io.unobserve(el);
          // Once it has landed, hand the transform back to the tilt/mock styles
          setTimeout(function () {
            el.classList.remove('v2-r', 'is-in');
          }, 1300 + (parseInt(el.style.getPropertyValue('--d'), 10) || 0));
        });
      },
      { rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
    );
    targets.forEach(function (el) {
      el.classList.add('v2-r');
      io.observe(el);
    });
  }

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

  function init() {
    document.documentElement.classList.add('v2-js');
    var cards = markCards();
    var mocks = markMocks();
    if (finePointer && !reduce) {
      cards.forEach(function (c) { tilt(c, 9); });
      mocks.forEach(function (m) { tilt(m, 6); });
    }
    setupReveal(cards, mocks);
    setupStage();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
