/* Widgets inside the page (calculators, tabs, counters) */
(function () {
  "use strict";

  // ---------- Before / after states ----------
  var STATES = {
    before: {
      stars: "\u2605\u2605\u2605\u2605\u2606",           // ★★★★☆
      rating: 4.2,
      reviews: 96,
      lastReview: "last review 3 months ago",
      calls: 31,
      dirs: 44,
      rank: 7
    },
    after: {
      stars: "\u2605\u2605\u2605\u2605\u2605",           // ★★★★★
      rating: 4.8,
      reviews: 312,
      lastReview: "last review 2 hours ago",
      calls: 78,
      dirs: 108,
      rank: 2
    }
  };

  var ANIM_MS = 700; // number count-up duration

  // ---------- Cached elements ----------
  var el = {
    card:   document.getElementById("toggle"),
    prof:   document.getElementById("gprof"),
    stars:  document.getElementById("gstars"),
    count:  document.getElementById("gcount"),
    calls:  document.getElementById("gcalls"),
    dirs:   document.getElementById("gdirs"),
    rank:   document.getElementById("grank"),
    sw:     document.getElementById("rsw"),
    lblB:   document.getElementById("lblB"),
    lblA:   document.getElementById("lblA")
  };

  var isAfter = false;
  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // ---------- Animated number count ----------
  function countTo(node, from, to, prefix, done) {
    prefix = prefix || "";
    if (reduceMotion || from === to) {
      node.textContent = prefix + to;
      if (done) done();
      return;
    }
    var start = null;
    function step(ts) {
      if (start === null) start = ts;
      var p = Math.min((ts - start) / ANIM_MS, 1);
      var eased = 1 - Math.pow(1 - p, 3); // ease-out cubic
      node.textContent = prefix + Math.round(from + (to - from) * eased);
      if (p < 1) {
        requestAnimationFrame(step);
      } else if (done) {
        done();
      }
    }
    requestAnimationFrame(step);
  }

  // ---------- Render a state ----------
  function render(prev, next) {
    // Stars line: rebuild safely with textContent
    el.stars.textContent = next.stars + " ";
    var small = document.createElement("small");
    small.id = "gcount";
    small.textContent = next.rating.toFixed(1) + " \u00b7 " + next.reviews +
                        " reviews \u00b7 " + next.lastReview;
    el.stars.appendChild(small);
    el.count = small;

    // Animated metrics
    countTo(el.calls, prev.calls, next.calls);
    countTo(el.dirs,  prev.dirs,  next.dirs);
    countTo(el.rank,  prev.rank,  next.rank, "#");
  }

  // ---------- Toggle handler ----------
  function toggle() {
    isAfter = !isAfter;

    var prev = isAfter ? STATES.before : STATES.after;
    var next = isAfter ? STATES.after  : STATES.before;

    render(prev, next);

    // Switch + profile styling
    el.sw.classList.toggle("on", isAfter);
    el.sw.setAttribute("aria-checked", String(isAfter));
    el.prof.classList.toggle("after", isAfter);

    // Active label colors
    el.lblB.style.color = isAfter ? "var(--grey)" : "var(--ink)";
    el.lblA.style.color = isAfter ? "var(--ink)"  : "var(--grey)";
  }

  // ---------- Events ---------- 
  el.sw.addEventListener("click", toggle);

  // Labels are clickable too
  el.lblB.addEventListener("click", function () { if (isAfter) toggle(); });
  el.lblA.addEventListener("click", function () { if (!isAfter) toggle(); });
  el.lblB.style.cursor = "pointer";
  el.lblA.style.cursor = "pointer";

  // ---------- Init ----------
  requestAnimationFrame(function () {
    el.card.classList.add("in");
  });
})();;
