/* Location pages: area tabs, active section in the in-page nav, mobile sticky CTA */
(function () {
  var page = document.querySelector('.lp');
  if (!page) return;

  // Area tabs (ARIA tabs pattern: click, arrow keys, Home/End)
  page.querySelectorAll('[data-lp-tabs]').forEach(function (box) {
    var tabs = Array.prototype.slice.call(box.querySelectorAll('[role="tab"]'));

    function select(tab, focus) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.tabIndex = on ? 0 : -1;
        var panel = document.getElementById(t.getAttribute('aria-controls'));
        if (panel) panel.hidden = !on;
      });
      if (focus) tab.focus();
      // Keep the chosen chip in view when the tabs scroll sideways on phones
      var list = tab.parentElement;
      if (tab.scrollIntoView && list.scrollWidth > list.clientWidth) {
        tab.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
      }
    }

    tabs.forEach(function (tab, i) {
      tab.addEventListener('click', function () { select(tab, false); });
      tab.addEventListener('keydown', function (e) {
        var next = { ArrowRight: i + 1, ArrowDown: i + 1, ArrowLeft: i - 1, ArrowUp: i - 1, Home: 0, End: tabs.length - 1 }[e.key];
        if (next === undefined) return;
        e.preventDefault();
        select(tabs[(next + tabs.length) % tabs.length], true);
      });
    });
  });

  if (!('IntersectionObserver' in window)) return;

  // Highlight the nav link of the section on screen
  var links = {};
  page.querySelectorAll('.lp-nav a[href^="#"]').forEach(function (a) {
    links[a.getAttribute('href').slice(1)] = a;
  });
  var visible = {};
  var spy = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) { visible[entry.target.id] = entry.isIntersecting; });
    var current = Object.keys(links).filter(function (id) { return visible[id]; })[0];
    Object.keys(links).forEach(function (id) {
      links[id].classList.toggle('is-active', id === current);
      if (id === current) links[id].setAttribute('aria-current', 'true');
      else links[id].removeAttribute('aria-current');
    });
  }, { rootMargin: '-30% 0px -60% 0px' });
  Object.keys(links).forEach(function (id) {
    var section = document.getElementById(id);
    if (section) spy.observe(section);
  });

  // Mobile sticky CTA: shown once the hero is gone, hidden again at the final call to action ([data-lp-final])
  var bar = page.querySelector('.lp-sticky');
  var hero = page.querySelector('.lp-hero');
  var final = page.querySelector('[data-lp-final]');
  if (!bar || !hero) return;
  var heroGone = false;
  var finalShown = false;

  function update() {
    var show = heroGone && !finalShown;
    bar.classList.toggle('is-visible', show);
    bar.setAttribute('aria-hidden', show ? 'false' : 'true');
    bar.querySelectorAll('a').forEach(function (a) { a.tabIndex = show ? 0 : -1; });
    document.body.classList.toggle('lp-sticky-on', show);
  }

  new IntersectionObserver(function (entries) {
    heroGone = !entries[0].isIntersecting;
    update();
  }).observe(hero);
  if (final) {
    new IntersectionObserver(function (entries) {
      finalShown = entries[0].isIntersecting;
      update();
    }).observe(final);
  }
})();
