/* Widgets inside the page (calculators, tabs, counters) */
(function () {
  "use strict";

  /* ---------------- Data ---------------- */

  var BEFORE = {
    subline: 'Posting "when we remember"',
    tiles: [
      { text: "Blurry mirror pic" },
      { text: "Repost #7" },
      { text: "Eid post (last year's)" },
      { text: "Random quote" },
      { text: "Dark room photo" },
      { text: "Price list screenshot" },
      { text: "Blurry mirror pic 2" },
      { text: "\u201CWe\u2019re hiring\u201D" },
      { text: "3-week gap" }
    ],
    stats: ["Reach: 1.2k/mo", "+4 followers", "2 DMs"]
  };

  var AFTER = {
    subline: "Posting daily \u00B7 managed by Glowth",
    tiles: [
      { text: "Laser results: 6 sessions",       theme: "t-pink"   },
      { text: "THIS MONTH: Hydrafacial AED 450", theme: "t-dark"   },
      { text: "POV: your first visit",           theme: "t-violet" },
      { text: "Meet your injector",              theme: "t-violet" },
      { text: "Bridal glow timeline",            theme: "t-pink"   },
      { text: "Real client review \u2B50\u2B50\u2B50\u2B50\u2B50", theme: "t-dark" },
      { text: "Brows: before / after",           theme: "t-pink"   },
      { text: "3 slots left Saturday",           theme: "t-violet" },
      { text: "Behind the treatment room",       theme: "t-pink"   }
    ],
    stats: ["Reach: 8.4k/mo", "+840 followers", "96 booking DMs"]
  };

  /* ---------------- Elements ---------------- */

  var card      = document.getElementById("gridCard");
  var grid      = document.getElementById("grid9");
  var statsWrap = document.getElementById("stats");
  var subline   = document.getElementById("subline");
  var sw        = document.getElementById("gridSwitch");
  var lblBefore = document.getElementById("lblBefore");
  var lblAfter  = document.getElementById("lblAfter");

  var isOn = false;
  var STAGGER = 45; // ms delay between each tile swap

  /* ---------------- Render ---------------- */

  function buildTiles(state) {
    grid.innerHTML = "";
    state.tiles.forEach(function (t) {
      var el = document.createElement("div");
      el.className = "tile" + (t.theme ? " " + t.theme : "");
      el.textContent = t.text;
      grid.appendChild(el);
    });
  }

  function buildStats(state) {
    statsWrap.innerHTML = "";
    state.stats.forEach(function (s) {
      var el = document.createElement("span");
      el.className = "stat-pill";
      el.textContent = s;
      statsWrap.appendChild(el);
    });
  }

  /* ---------------- Animated swap ---------------- */

  function swapTo(state) {
    var tiles = grid.querySelectorAll(".tile");

    tiles.forEach(function (tile, i) {
      setTimeout(function () {
        tile.classList.add("swapping");

        setTimeout(function () {
          var data = state.tiles[i];
          tile.textContent = data.text;
          tile.className = "tile" + (data.theme ? " " + data.theme : "");
          // force reflow so the un-scale transition plays
          void tile.offsetWidth;
        }, 180);
      }, i * STAGGER);
    });

    // Stats + subline update after the wave finishes
    var totalDelay = tiles.length * STAGGER + 200;
    setTimeout(function () {
      subline.textContent = state.subline;
      buildStats(state);
      statsWrap.querySelectorAll(".stat-pill").forEach(function (pill, i) {
        setTimeout(function () {
          pill.classList.add("bump");
          setTimeout(function () { pill.classList.remove("bump"); }, 300);
        }, i * 90);
      });
    }, totalDelay);
  }

  /* ---------------- Toggle logic ---------------- */

  function setState(on) {
    if (on === isOn) return;
    isOn = on;

    sw.setAttribute("aria-checked", String(on));
    card.classList.toggle("is-on", on);
    lblBefore.classList.toggle("act", !on);
    lblAfter.classList.toggle("act", on);

    swapTo(on ? AFTER : BEFORE);
  }

  sw.addEventListener("click", function () { setState(!isOn); });
  lblBefore.addEventListener("click", function () { setState(false); });
  lblAfter.addEventListener("click",  function () { setState(true);  });

  /* ---------------- Init ---------------- */

  buildTiles(BEFORE);
  buildStats(BEFORE);
})();;
