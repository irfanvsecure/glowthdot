/**
 * Glowth home — motion + polish (no content changes)
 */
(function () {
  'use strict';

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function ready(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  function setupHeader() {
    var hero = document.getElementById('bg');
    function tick() {
      var y = window.scrollY;
      var past = hero ? y > Math.min(hero.offsetHeight * 0.25, 160) : y > 60;
      document.body.classList.toggle('gp-scrolled', past);
    }
    tick();
    window.addEventListener('scroll', tick, { passive: true });
  }

  function setupHero() {
    var hero = document.getElementById('bg');
    if (!hero || reduce) return;
    var nodes = hero.querySelectorAll(
      '.elementor-widget-heading, .elementor-widget-text-editor, .elementor-widget-button, .elementor-widget-icon-list'
    );
    var n = 0;
    for (var i = 0; i < nodes.length && n < 5; i++) {
      var el = nodes[i];
      if (el.getBoundingClientRect().top - hero.getBoundingClientRect().top > 480) continue;
      el.classList.add('gp-hero-step');
      n++;
    }
    requestAnimationFrame(function () {
      hero.classList.add('gp-hero-ready');
    });
  }

  function setupReveals() {
    if (reduce || !('IntersectionObserver' in window)) return;

    var lists = document.querySelectorAll('.elementor-widget-icon-list');
    for (var i = 0; i < lists.length; i++) {
      if ((lists[i].textContent || '').indexOf('Google Ads Certified') !== -1) {
        lists[i].classList.add('gp-trust');
        break;
      }
    }

    var parents = document.querySelectorAll('.elementor-8 > .e-con.e-parent, .elementor-8 .e-con.e-parent');
    var count = 0;
    parents.forEach(function (p) {
      if (p.id === 'bg' || p.classList.contains('bg')) return;
      if (count++ > 18) return;
      p.classList.add('gp-reveal');
    });

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (e) {
          if (!e.isIntersecting) return;
          e.target.classList.add('is-in');
          if (e.target.classList.contains('gp-trust')) e.target.classList.add('is-visible');
          io.unobserve(e.target);
        });
      },
      { rootMargin: '0px 0px -10% 0px', threshold: 0.1 }
    );

    document.querySelectorAll('.gp-reveal, .gp-trust').forEach(function (el) {
      io.observe(el);
    });
  }

  function setupSmoothAnchors() {
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[href^="#"]');
      if (!a) return;
      var id = a.getAttribute('href');
      if (!id || id === '#' || id === '#content') return;
      if (!document.getElementById('audit') && id === '#audit') {
        var hs = document.querySelectorAll('h2,h3');
        for (var i = 0; i < hs.length; i++) {
          if (/Free Salon.*Growth Audit/i.test(hs[i].textContent || '')) {
            (hs[i].closest('.elementor-element') || hs[i]).id = 'audit';
            break;
          }
        }
      }
      var t = document.querySelector(id);
      if (!t) return;
      e.preventDefault();
      t.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
    });
  }

  ready(function () {
    document.documentElement.classList.add('gp-home');
    setupHeader();
    setupHero();
    setupReveals();
    setupSmoothAnchors();
  });
})();
