/**
 * Glowth v2, site-wide: 3D reveal, tilt + glare on cards and floating mock-ups.
 * Styles: public/css/v2.css. Nothing here changes content; without JS every page is complete.
 *
 * Cards and mock-ups come from, in order:
 *  - window.GLOWTH_V2 = { grids: { dataId: 'photo' | '' }, mocks: { dataId: 'left surface ...' } }
 *    (set by a page script that loads first, e.g. public/js/home-v2.js)
 *  - service and location pages: their .lp-* cards and hero visual
 *  - other pages: any row of bordered or shadowed boxes, found by their computed style
 */
(function () {
  'use strict';

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var config = window.GLOWTH_V2 || {};

  var LP_CARDS = '.lp-card:not(.lp-card--raw), .lp-service, .lp-rule, .lp-stat, .lp-review, .lp-glance, .lp-links__group, .lp-feature__visual';
  var CONTENT = '.x-type-page, .x-type-post';

  function byId(id) {
    return document.querySelector('[data-id="' + id + '"]');
  }

  function childBoxes(el) {
    var parent = el.querySelector(':scope > .c-con-inner') || el;
    var out = [];
    for (var i = 0; i < parent.children.length; i++) {
      if (parent.children[i].classList.contains('c-con')) out.push(parent.children[i]);
    }
    return out;
  }

  function isLight(rgb) {
    var m = rgb.match(/\d+(\.\d+)?/g);
    if (!m) return true;
    if (m.length === 4 && +m[3] === 0) return null; // transparent
    return (0.2126 * m[0] + 0.7152 * m[1] + 0.0722 * m[2]) / 255 > 0.45;
  }

  // Other pages: light or dark text for each top-level section, from its own background
  function setTones() {
    document.querySelectorAll(CONTENT).forEach(function (page) {
      for (var i = 0; i < page.children.length; i++) {
        var sec = page.children[i];
        if (!sec.classList.contains('c-con') || sec.classList.contains('v2-sec')) continue;
        var cs = getComputedStyle(sec);
        var light = isLight(cs.backgroundColor);
        if (light === null && cs.backgroundImage !== 'none') {
          // Photo or gradient: a dark colour overlay makes it a dark section, otherwise leave it
          var overlay = getComputedStyle(sec, '::before');
          var alpha = (overlay.backgroundColor.match(/[\d.]+/g) || [])[3];
          var dim = isLight(overlay.backgroundColor) === false && (alpha === undefined || +alpha * (+overlay.opacity || 1) >= 0.3);
          if (dim) sec.classList.add('v2-tone-dark');
          continue;
        }
        sec.classList.add(light === false ? 'v2-tone-dark' : 'v2-tone-light');
      }
    });
  }

  // Headings whose colour (often a section-wide tone rule) lands on a box of the same colour,
  // e.g. a black card in a light section or a white form card in a dark one: flip them.
  function rgbOf(c) {
    var m = (c || '').match(/[\d.]+/g);
    return m ? m.map(Number) : null;
  }
  function lum(c) {
    var f = function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
    return 0.2126 * f(c[0]) + 0.7152 * f(c[1]) + 0.0722 * f(c[2]);
  }
  function backdrop(el) {
    for (var p = el; p && p.nodeType === 1; p = p.parentElement) {
      var cs = getComputedStyle(p);
      if (cs.backgroundImage.indexOf('url(') !== -1) return null; // photo: leave it
      var c = rgbOf(cs.backgroundColor);
      if (c && (c.length < 4 || c[3] > 0.5)) return c;
      if (cs.backgroundImage.indexOf('gradient') !== -1) {
        var stop = cs.backgroundImage.match(/rgba?\([^)]*\)/);
        if (stop) return rgbOf(stop[0]);
      }
    }
    return [255, 255, 255];
  }
  function checkHeading(h) {
    if (h.classList.contains('v2-on-dark') || h.classList.contains('v2-on-light')) return;
    var bg = backdrop(h);
    var fg = rgbOf(getComputedStyle(h).color);
    if (!bg || !fg) return;
    var a = lum(fg), b = lum(bg);
    if ((Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05) < 1.6) {
      h.classList.add(b < 0.4 ? 'v2-on-dark' : 'v2-on-light');
    }
  }
  function fixHiddenHeadings() {
    var heads = [];
    document.querySelectorAll(CONTENT).forEach(function (page) {
      page.querySelectorAll('h1, h2, h3, h4, .x-heading-title, .v2-title').forEach(function (h) {
        if (h.textContent.trim()) heads.push(h);
      });
    });
    heads.forEach(checkHeading);
    // Lazy-loaded section backgrounds only appear on scroll: check again when each heading shows
    if (!('IntersectionObserver' in window)) return;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        io.unobserve(e.target);
        setTimeout(function () { checkHeading(e.target); }, 150);
      });
    });
    heads.forEach(function (h) { io.observe(h); });
  }

  function mark(el, i, photo) {
    el.classList.add('v2-card', 'v2-tilt');
    if (photo) el.classList.add('v2-card--photo');
    el.style.setProperty('--d', Math.min(i, 6) * 70 + 'ms');
  }

  function findCards() {
    var cards = [];
    if (config.grids) {
      Object.keys(config.grids).forEach(function (id) {
        var grid = byId(id);
        if (!grid) return;
        childBoxes(grid).forEach(function (c, i) {
          mark(c, i, config.grids[id] === 'photo');
          cards.push(c);
        });
      });
      return cards;
    }

    // Service and location pages keep their own card styles; they only gain depth and motion
    var lp = document.querySelectorAll(LP_CARDS);
    if (lp.length) {
      lp.forEach(function (c) {
        var siblings = c.parentElement ? [].indexOf.call(c.parentElement.children, c) : 0;
        c.classList.add('v2-tilt');
        c.style.setProperty('--d', Math.min(siblings, 6) * 70 + 'ms');
        cards.push(c);
      });
      return cards;
    }

    // Other pages: a row of two or more boxes with a border or shadow and rounded corners
    document.querySelectorAll(CONTENT).forEach(function (page) {
      page.querySelectorAll('.c-con').forEach(function (row) {
        if (row.closest('.v2-card')) return;
        var kids = childBoxes(row);
        if (kids.length < 2) return;
        var rowWidth = row.getBoundingClientRect().width;
        var boxed = kids.filter(function (k) {
          if (k.querySelector('form, input, select, textarea')) return false;
          var cs = getComputedStyle(k);
          var edged = parseFloat(cs.borderTopWidth) > 0 || cs.boxShadow !== 'none';
          return edged && parseFloat(cs.borderTopLeftRadius) >= 6 && k.getBoundingClientRect().width < rowWidth * 0.7;
        });
        if (boxed.length < 2 || boxed.length < kids.length / 2) return;
        boxed.forEach(function (k, i) {
          mark(k, i, getComputedStyle(k).backgroundImage.indexOf('url(') !== -1);
          cards.push(k);
        });
      });
    });
    return cards;
  }

  function findMocks() {
    var mocks = [];
    if (config.mocks) {
      Object.keys(config.mocks).forEach(function (id) {
        var el = byId(id);
        if (!el) return;
        el.classList.add('v2-mock');
        config.mocks[id].split(' ').forEach(function (mod) {
          if (mod) el.classList.add('v2-mock--' + mod);
        });
        mocks.push(el);
      });
    }
    // Service pages: the product visual beside the hero copy
    document.querySelectorAll('.lp-hero__visual > *:first-child').forEach(function (el) {
      el.classList.add('v2-mock');
      mocks.push(el);
    });
    // Location pages: the audit card in the hero leans like the home page's
    document.querySelectorAll('.lp-hero .lp-audit').forEach(function (el) {
      el.classList.add('v2-tilt', 'v2-tilt--glass');
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
    var n = 0;
    document.querySelectorAll('.v2-kicker, .v2-title').forEach(function (h) {
      var w = h.closest('.w');
      if (!w || w.closest('#bg')) return;
      w.style.setProperty('--d', (n++ % 3) * 90 + 'ms');
      targets.push(w);
      // The intro right under a title rises with it
      var next = w.nextElementSibling;
      if (h.classList.contains('v2-title') && next && next.classList.contains('w-text-editor')) {
        next.style.setProperty('--d', (n++ % 3) * 90 + 'ms');
        targets.push(next);
      }
    });
    document.querySelectorAll('.lp-section .lp-head, .gl-fix__head, .gl-fix__body').forEach(function (el) {
      targets.push(el);
    });
    targets = targets.concat(cards, mocks.filter(function (m) { return !m.closest('.lp-hero, #bg'); }));

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

  function init() {
    document.documentElement.classList.add('v2-js');
    setTones();
    fixHiddenHeadings();
    var cards = findCards();
    var mocks = findMocks();
    if (finePointer && !reduce) {
      cards.forEach(function (c) { tilt(c, 9); });
      mocks.forEach(function (m) { tilt(m, 6); });
    }
    setupReveal(cards, mocks);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
