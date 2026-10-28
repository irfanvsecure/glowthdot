/* Widgets inside the page (calculators, tabs, counters) */
(function () {
  "use strict";

  // ---------- Campaign data ----------
  // reachX  : reach per AED of budget
  // dmDiv   : AED per DM
  // bookDiv : AED per booking
  var CAMPAIGNS = {
    hydra: {
      title: "Glass Skin Season",
      sub: "Hydrafacial from AED 249 \u00b7 Zero downtime",
      cta: "Book Now",
      cap: "That lit-from-within glow in 45 minutes. Limited slots this week \u2014 tap to grab yours. \u2728",
      gradient: "linear-gradient(125deg,#1ec9e9 0%,#2874c0 50%,#3c4ce8 100%)",
      reachX: 48, dmDiv: 5.5, bookDiv: 26
    },
    laser: {
      title: "Smooth. Forever.",
      sub: "Laser hair removal packages \u00b7 Save 40%",
      cta: "Get Offer",
      cap: "Razors are a subscription you never signed up for. Cancel it \u2014 package prices inside. \uD83D\uDD25",
      gradient: "linear-gradient(125deg,#e94b1e 0%,#c02857 50%,#8b2fc9 100%)",
      reachX: 40, dmDiv: 6.5, bookDiv: 34
    },
    lips: {
      title: "Subtle. Not Secret.",
      sub: "Lip fillers from AED 799 \u00b7 Certified doctors",
      cta: "Free Consult",
      cap: "Natural volume that still looks like you. Book a free consult \u2014 this month only. \uD83D\uDC8B",
      gradient: "linear-gradient(125deg,#e91e75 0%,#b02fc9 55%,#6d3ce8 100%)",
      reachX: 36, dmDiv: 7.5, bookDiv: 42
    },
    salon: {
      title: "The Glow-Up Bundle",
      sub: "Hair + nails + brows \u00b7 Save 30%",
      cta: "Book Now",
      cap: "Main-character maintenance, one appointment. This month's bundle is going fast \u2014 tap to see slots. \uD83D\uDC85",
      gradient: "linear-gradient(125deg,#e91e75 0%,#c02895 45%,#7a3ce8 100%)",
      reachX: 42, dmDiv: 6, bookDiv: 30
    }
  };

  // ---------- Cached elements ----------
  var el = {
    card:  document.getElementById("planner"),
    img:   document.getElementById("igImg"),
    t:     document.getElementById("igT"),
    s:     document.getElementById("igS"),
    cta:   document.getElementById("igCta"),
    cap:   document.getElementById("igCap"),
    sel:   document.getElementById("igSel"),
    range: document.getElementById("igR"),
    out:   document.getElementById("igO"),
    reach: document.getElementById("igReach"),
    dm:    document.getElementById("igDm"),
    book:  document.getElementById("igBook")
  };

  // ---------- Helpers ----------
  function fmtAED(n) {
    return "AED " + n.toLocaleString("en-US");
  }

  function fmtReach(n) {
    if (n >= 1000000) return "~" + (n / 1000000).toFixed(1) + "M reach";
    if (n >= 1000)    return "~" + Math.round(n / 1000) + "k reach";
    return "~" + n + " reach";
  }

  // ---------- Render metrics from budget ----------
  function updateMetrics() {
    var c = CAMPAIGNS[el.sel.value];
    var budget = parseInt(el.range.value, 10);

    el.out.textContent   = fmtAED(budget);
    el.reach.textContent = fmtReach(Math.round(budget * c.reachX));
    el.dm.textContent    = "~" + Math.round(budget / c.dmDiv).toLocaleString("en-US") + " DMs";
    el.book.textContent  = "~" + Math.round(budget / c.bookDiv).toLocaleString("en-US") + " bookings";
  }

  // ---------- Render creative from campaign ----------
  function updateCreative() {
    var c = CAMPAIGNS[el.sel.value];

    el.t.textContent   = c.title;
    el.s.textContent   = c.sub;
    el.cta.textContent = c.cta;
    el.cap.textContent = c.cap;
    el.img.style.background = c.gradient;

    // Replay swap animation on the creative
    el.img.classList.remove("swap");
    void el.img.offsetWidth; // force reflow so animation restarts
    el.img.classList.add("swap");

    updateMetrics();
  }

  // ---------- Events ----------
  el.sel.addEventListener("change", updateCreative);
  el.range.addEventListener("input", updateMetrics);

  // ---------- Init ----------
  updateCreative();

  requestAnimationFrame(function () {
    el.card.classList.add("in");
  });
})();;
