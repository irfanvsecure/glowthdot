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
      '.w-heading, .w-text-editor, .w-button, .w-icon-list'
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

    var lists = document.querySelectorAll('.w-icon-list');
    for (var i = 0; i < lists.length; i++) {
      if ((lists[i].textContent || '').indexOf('Google Ads Certified') !== -1) {
        lists[i].classList.add('gp-trust');
        break;
      }
    }

    var parents = document.querySelectorAll('.x-8 > .c-con.c-parent, .x-8 .c-con.c-parent');
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
            (hs[i].closest('.x-element') || hs[i]).id = 'audit';
            break;
          }
        }
      }
      var t = document.querySelector(id);
      if (!t) return;
      e.preventDefault();
      t.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
      if (id === '#free-audit') {
        t.classList.remove('gp-pulse');
        setTimeout(function () {
          t.classList.add('gp-pulse');
          var first = t.querySelector('.x-form input:not([type=hidden])');
          if (first && window.matchMedia('(min-width: 768px)').matches) first.focus({ preventScroll: true });
        }, reduce ? 0 : 700);
      }
    });
  }

  // Phones: a bottom bar with the page's own closing CTAs, shown once the hero is gone
  // and hidden while the audit form or the closing CTA band is already on screen.
  function setupStickyCta() {
    var finale = document.querySelector('[data-id="8c7c125"]');
    var form = document.getElementById('free-audit');
    var hero = document.getElementById('bg');
    if (!finale || !form || !hero) return;

    var audit = null;
    var wa = null;
    finale.querySelectorAll('a.x-button').forEach(function (a) {
      var href = a.getAttribute('href') || '';
      if (href.indexOf('get-a-free-audit') !== -1) audit = a;
      else if (href.indexOf('wa.me') !== -1) wa = a;
    });
    if (!audit || !wa) return;

    var bar = document.createElement('div');
    bar.className = 'gp-sticky-cta';
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', 'Quick contact');
    [[audit, 'gp-sticky-audit'], [wa, 'gp-sticky-wa']].forEach(function (pair) {
      var a = document.createElement('a');
      a.href = pair[0].getAttribute('href');
      a.className = pair[1];
      a.textContent = pair[0].textContent.trim();
      if (pair[0].target) {
        a.target = pair[0].target;
        a.rel = 'noopener';
      }
      bar.appendChild(a);
    });
    document.body.appendChild(bar);
    document.body.classList.add('gp-has-sticky');

    var heroGone = false;
    var blockers = new Set();
    function update() {
      document.body.classList.toggle('gp-sticky-on', heroGone && blockers.size === 0);
    }
    new IntersectionObserver(function (entries) {
      heroGone = !entries[0].isIntersecting;
      update();
    }).observe(hero);
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) blockers.add(e.target);
        else blockers.delete(e.target);
      });
      update();
    }, { threshold: 0.15 });
    io.observe(form);
    io.observe(finale);
  }

  // Phones: position dots under the sideways-swiping card rows
  function setupSwipeDots() {
    ['.k-ba68542', '.k-d3862d9'].forEach(function (sel) {
      var row = document.querySelector(sel);
      if (!row) return;
      var cards = Array.prototype.filter.call(row.children, function (c) {
        return c.classList.contains('c-con');
      });
      if (cards.length < 2) return;
      var dots = document.createElement('div');
      dots.className = 'gp-swipe-dots';
      dots.setAttribute('aria-hidden', 'true');
      cards.forEach(function (_, i) {
        var d = document.createElement('span');
        if (i === 0) d.className = 'is-on';
        dots.appendChild(d);
      });
      row.parentNode.insertBefore(dots, row.nextSibling);
      row.addEventListener('scroll', function () {
        var step = cards[1].offsetLeft - cards[0].offsetLeft || 1;
        var idx = Math.min(cards.length - 1, Math.round(row.scrollLeft / step));
        Array.prototype.forEach.call(dots.children, function (d, i) {
          d.classList.toggle('is-on', i === idx);
        });
      }, { passive: true });
    });
  }

  ready(function () {
    document.documentElement.classList.add('gp-home');
    setupHeader();
    setupHero();
    setupReveals();
    setupSmoothAnchors();
    if ('IntersectionObserver' in window) setupStickyCta();
    setupSwipeDots();
  });
})();
