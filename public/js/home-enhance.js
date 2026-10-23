/**
 * Glowth home — CRO + motion (sticky CTA, reveals, smooth scroll)
 */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var stickyKey = 'glowth_sticky_cta_dismissed';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function ensureAuditAnchor() {
    if (document.getElementById('audit')) return;
    var headings = document.querySelectorAll('h2.elementor-heading-title, h3.elementor-heading-title');
    for (var i = 0; i < headings.length; i++) {
      var t = (headings[i].textContent || '').replace(/\s+/g, ' ').trim();
      if (/Free Salon.*Growth Audit/i.test(t)) {
        var wrap = headings[i].closest('.elementor-element') || headings[i];
        wrap.id = 'audit';
        return;
      }
    }
  }

  function setupHeroEntrance() {
    var hero = document.getElementById('bg');
    if (!hero || reduceMotion) return;

    // Mark first meaningful direct widgets / nested content blocks for stagger
    var candidates = hero.querySelectorAll(
      '.elementor-widget-heading, .elementor-widget-text-editor, .elementor-widget-button, .elementor-widget-icon-list, .elementor-widget-image'
    );
    var steps = 0;
    for (var i = 0; i < candidates.length && steps < 5; i++) {
      var el = candidates[i];
      // only near top of hero (first content cluster)
      if (el.getBoundingClientRect().top - hero.getBoundingClientRect().top > 420) continue;
      el.classList.add('glowth-hero-step');
      steps++;
    }
    // reflow then animate
    requestAnimationFrame(function () {
      hero.classList.add('glowth-hero-ready');
    });
  }

  function setupScrollHeader() {
    var hero = document.getElementById('bg');
    function onScroll() {
      var past = window.scrollY > (hero ? Math.min(hero.offsetHeight * 0.35, 220) : 80);
      document.body.classList.toggle('glowth-scrolled', past);
    }
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  function setupReveals() {
    if (reduceMotion || !('IntersectionObserver' in window)) {
      return;
    }

    // Trust bar: icon-list that includes Google Ads Certified
    var trust = null;
    var lists = document.querySelectorAll('.elementor-widget-icon-list');
    for (var j = 0; j < lists.length; j++) {
      if ((lists[j].textContent || '').indexOf('Google Ads Certified') !== -1) {
        trust = lists[j];
        break;
      }
    }
    if (trust) {
      trust.classList.add('glowth-trust-reveal');
    }

    // Section parents: e-con-parent after hero
    var parents = document.querySelectorAll('.e-con-parent');
    parents.forEach(function (p, idx) {
      if (p.id === 'bg') return;
      if (idx > 14) return; // don't over-animate footer noise
      p.classList.add('glowth-reveal');
    });

    // Also key headings sections
    document.querySelectorAll('h2.elementor-heading-title').forEach(function (h) {
      var box = h.closest('.e-con') || h.closest('.elementor-element');
      if (box && !box.classList.contains('glowth-reveal') && box.id !== 'bg') {
        box.classList.add('glowth-reveal');
      }
    });

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
          }
        });
      },
      { rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
    );

    document.querySelectorAll('.glowth-reveal, .glowth-trust-reveal').forEach(function (el) {
      io.observe(el);
    });
  }

  function setupSmoothScroll() {
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[href^="#"]');
      if (!a) return;
      var id = a.getAttribute('href');
      if (!id || id === '#' || id === '#content') return;
      var target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
      if (history.pushState) {
        history.pushState(null, '', id);
      }
    });
  }

  function setupStickyCta() {
    if (window.matchMedia('(min-width: 922px)').matches) return;
    if (sessionStorage.getItem(stickyKey) === '1') return;

    var bar = document.createElement('div');
    bar.className = 'glowth-sticky-cta';
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', 'Free growth audit');
    bar.innerHTML =
      '<div class="glowth-sticky-cta__copy"><strong>Free audit</strong> — 5 slots left this week</div>' +
      '<a class="glowth-sticky-cta__btn" href="/contact-us/">Get free audit</a>' +
      '<button type="button" class="glowth-sticky-cta__close" aria-label="Dismiss">&times;</button>';
    document.body.appendChild(bar);

    var hero = document.getElementById('bg');
    var shown = false;

    function update() {
      if (sessionStorage.getItem(stickyKey) === '1') {
        bar.classList.remove('is-visible');
        document.body.classList.remove('glowth-sticky-open');
        return;
      }
      var pastHero = hero
        ? window.scrollY > hero.offsetHeight * 0.55
        : window.scrollY > 420;
      if (pastHero && !shown) {
        shown = true;
      }
      var show = pastHero;
      bar.classList.toggle('is-visible', show);
      document.body.classList.toggle('glowth-sticky-open', show);
    }

    bar.querySelector('.glowth-sticky-cta__close').addEventListener('click', function () {
      sessionStorage.setItem(stickyKey, '1');
      bar.classList.remove('is-visible');
      document.body.classList.remove('glowth-sticky-open');
    });

    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  ready(function () {
    ensureAuditAnchor();
    setupHeroEntrance();
    setupScrollHeader();
    setupReveals();
    setupSmoothScroll();
    setupStickyCta();
  });
})();
