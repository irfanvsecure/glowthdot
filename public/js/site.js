/**
 * Glowth site behaviour, plain JS (replaces jQuery and the theme/page-builder scripts):
 * header menus, scroll-to-top, entrance animations, lazy backgrounds, hero slideshow, tabs,
 * accordions, counters and the enquiry forms. Styles: the page's own stylesheet + public/css/site.css.
 */
(function () {
  'use strict';

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function $all(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  // ----- Header -----
  function setupHeader() {
    document.body.classList.add('h-header-loaded');

    // Phone menu (dropdown under the bar)
    $all('.main-header-menu-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var open = !btn.classList.contains('toggled');
        var wrap = btn.closest('#h-mobile-header') || document;
        $all('.h-mobile-header-content', wrap).forEach(function (c) {
          c.style.display = open ? 'block' : 'none';
        });
        btn.classList.toggle('toggled', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('h-main-header-nav-open', open);
      });
    });

    // Sub-menus on phones: the arrow button opens them
    $all('.h-header-break-point .h-menu-toggle, #h-mobile-header .h-menu-toggle').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var li = btn.closest('.nav-item-has-children');
        if (!li) return;
        var sub = li.querySelector(':scope > .nav-sub');
        var open = !li.classList.contains('h-submenu-expanded');
        li.classList.toggle('h-submenu-expanded', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (sub) sub.style.display = open ? 'block' : 'none';
      });
    });

    // Desktop: keyboard focus opens a dropdown like hover does
    $all('.main-header-menu .nav-item-has-children').forEach(function (li) {
      li.addEventListener('focusin', function () { li.classList.add('focus'); });
      li.addEventListener('focusout', function (e) {
        if (!li.contains(e.relatedTarget)) li.classList.remove('focus');
      });
    });

    // Scroll to top
    var top = document.getElementById('h-scroll-top');
    if (top) {
      var tick = function () { top.style.display = window.pageYOffset > 300 ? 'block' : 'none'; };
      tick();
      window.addEventListener('scroll', tick, { passive: true });
      top.addEventListener('click', function (e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
      });
    }
  }

  // ----- Entrance animations and lazy section backgrounds -----
  function setupInView() {
    var anim = $all('.x-invisible[data-anim]');
    var lazy = $all('.c-con.c-parent');
    if (!('IntersectionObserver' in window)) {
      anim.forEach(show);
      lazy.forEach(function (el) { el.classList.add('c-lazyloaded'); });
      return;
    }
    function show(el) {
      el.classList.remove('x-invisible');
      if (!reduce) el.classList.add('animated', el.getAttribute('data-anim'));
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        io.unobserve(e.target);
        if (e.target.hasAttribute('data-anim') && e.target.classList.contains('x-invisible')) show(e.target);
        else e.target.classList.add('c-lazyloaded');
      });
    }, { rootMargin: '200px 0px' });
    anim.concat(lazy).forEach(function (el) { io.observe(el); });
  }

  // ----- Background slideshow (home hero) -----
  function setupSlideshows() {
    $all('[data-slideshow]').forEach(function (box) {
      var urls;
      try { urls = JSON.parse(box.getAttribute('data-slideshow')); } catch (e) { return; }
      if (!urls || !urls.length) return;
      var show = document.createElement('div');
      show.className = 'slideshow';
      show.setAttribute('aria-hidden', 'true');
      urls.forEach(function (u, i) {
        var s = document.createElement('div');
        s.className = 'slideshow__slide' + (i === 0 ? ' is-on' : '');
        s.style.backgroundImage = 'url("' + u + '")';
        show.appendChild(s);
      });
      box.insertBefore(show, box.firstChild);
      if (reduce || urls.length < 2) return;
      var slides = show.children;
      var at = 0;
      setInterval(function () {
        slides[at].classList.remove('is-on');
        at = (at + 1) % slides.length;
        slides[at].classList.add('is-on');
      }, parseInt(box.getAttribute('data-slide-ms'), 10) || 5000);
    });
  }

  // ----- Tabs and accordions (title [data-tab] <-> content [data-tab]) -----
  function setupTabs() {
    // Both open their first item; a second click closes an accordion item, not a tab
    $all('.x-tabs, .x-accordion').forEach(function (w) {
      var isAccordion = w.classList.contains('x-accordion');
      var titles = $all('.x-tab-title', w);
      var contents = $all('.x-tab-content', w);
      function set(tab, on) {
        titles.forEach(function (t) {
          if (t.getAttribute('data-tab') !== tab) return;
          t.classList.toggle('x-active', on);
          t.setAttribute(isAccordion ? 'aria-expanded' : 'aria-selected', on ? 'true' : 'false');
          if (!isAccordion) t.setAttribute('tabindex', on ? '0' : '-1');
        });
        contents.forEach(function (c) {
          if (c.getAttribute('data-tab') !== tab) return;
          c.classList.toggle('x-active', on);
          c.style.display = on ? 'block' : 'none';
        });
      }
      function activeTab() {
        var a = titles.filter(function (t) { return t.classList.contains('x-active'); })[0];
        return a ? a.getAttribute('data-tab') : null;
      }
      titles.forEach(function (t) {
        t.addEventListener('click', function (e) {
          e.preventDefault();
          var tab = t.getAttribute('data-tab');
          var current = activeTab();
          if (current === tab) {
            if (isAccordion) set(tab, false);
            return;
          }
          if (current) set(current, false);
          set(tab, true);
        });
        t.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); t.click(); }
        });
      });
      if (titles.length) set(titles[0].getAttribute('data-tab'), true);
    });

    // Nested tabs: buttons with aria-selected, panels marked c-active. Until c-activated is set
    // the stylesheet keeps the first panel open whatever is selected.
    $all('.c-n-tabs').forEach(function (w) {
      w.classList.add('c-activated');
      var titles = $all('.c-n-tab-title', w);
      var panels = $all('.c-n-tabs-content > .c-con', w);
      titles.forEach(function (t, i) {
        t.addEventListener('click', function () {
          titles.forEach(function (x, j) {
            x.setAttribute('aria-selected', j === i ? 'true' : 'false');
            x.setAttribute('tabindex', j === i ? '0' : '-1');
            if (panels[j]) panels[j].classList.toggle('c-active', j === i);
          });
        });
      });
    });

    // Nested accordions are <details>; "one open at a time" where the page asked for it
    $all('.c-n-accordion').forEach(function (w) {
      var host = w.closest('[data-one]');
      if (!host) return;
      var items = $all(':scope > details', w);
      items.forEach(function (d) {
        d.addEventListener('toggle', function () {
          if (!d.open) return;
          items.forEach(function (o) { if (o !== d) o.open = false; });
        });
      });
    });
  }

  // ----- Counters -----
  function setupCounters() {
    var nums = $all('.x-counter-number[data-to-value]');
    if (!nums.length) return;
    function run(el) {
      var from = parseFloat(el.getAttribute('data-from-value')) || 0;
      var to = parseFloat(el.getAttribute('data-to-value')) || 0;
      var ms = parseInt(el.getAttribute('data-duration'), 10) || 2000;
      var sep = el.getAttribute('data-delimiter') || ',';
      var fmt = function (n) { return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, sep); };
      if (reduce) { el.textContent = fmt(to); return; }
      var t0 = null;
      requestAnimationFrame(function step(t) {
        t0 = t0 || t;
        var p = Math.min(1, (t - t0) / ms);
        el.textContent = fmt(from + (to - from) * (1 - Math.pow(1 - p, 3)));
        if (p < 1) requestAnimationFrame(step);
      });
    }
    if (!('IntersectionObserver' in window)) { nums.forEach(run); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        io.unobserve(e.target);
        run(e.target);
      });
    });
    nums.forEach(function (n) { io.observe(n); });
  }

  // ----- Enquiry forms: sent to /api/enquiry, answer shown under the form -----
  function setupForms() {
    $all('form.x-form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (form.classList.contains('x-form-waiting')) return;
        $all('.x-message', form).forEach(function (m) { m.remove(); });
        var data = new FormData(form);
        data.set('referer_title', document.title);
        form.classList.add('x-form-waiting');
        var button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        fetch('/api/enquiry', { method: 'POST', body: data, headers: { Accept: 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            var ok = res && res.success;
            var msg = document.createElement('div');
            msg.className = 'x-message ' + (ok ? 'x-message-success' : 'x-message-danger');
            msg.setAttribute('role', 'alert');
            msg.textContent = (res && res.data && res.data.message) || (ok ? 'Thank you!' : 'Something went wrong. Please try again.');
            form.appendChild(msg);
            if (ok) form.reset();
          })
          .catch(function () {
            var msg = document.createElement('div');
            msg.className = 'x-message x-message-danger';
            msg.setAttribute('role', 'alert');
            msg.textContent = 'Could not send. Please try again or message us on WhatsApp.';
            form.appendChild(msg);
          })
          .then(function () {
            form.classList.remove('x-form-waiting');
            if (button) button.disabled = false;
          });
      });
    });
  }

  function init() {
    setupHeader();
    setupInView();
    setupSlideshows();
    setupTabs();
    setupCounters();
    setupForms();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
