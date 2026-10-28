/* Widgets inside the page (calculators, tabs, counters) */
(function () {
  "use strict";

  // ---------- Data for each treatment ----------
  var ADS = {
    laser: {
      query: "laser hair removal dubai",
      url: "yourclinic.ae/laser-dubai",
      headline: "Laser Hair Removal Dubai from AED 99 | FDA-Approved Devices",
      description: "Painless sessions for all skin types \u00b7 Free patch test \u00b7 Flexible packages. See prices and book your slot online.",
      sitelinks: ["Prices & Offers", "Book Online", "Before & After", "Find Us"],
      cpc: "Est. CPC AED 14",
      vol: "~310 searches/day",
      intent: "Intent: ready to book"
    },
    botox: {
      query: "botox clinic dubai",
      url: "yourclinic.ae/botox-dubai",
      headline: "Botox in Dubai from AED 39/Unit | Licensed Practitioners",
      description: "Natural results by certified injectors \u00b7 Free consultation \u00b7 Transparent pricing. See prices and book your slot online.",
      sitelinks: ["Prices & Offers", "Book Online", "Before & After", "Find Us"],
      cpc: "Est. CPC AED 18",
      vol: "~220 searches/day",
      intent: "Intent: ready to book"
    },
    hydra: {
      query: "hydrafacial dubai offer",
      url: "yourclinic.ae/hydrafacial-dubai",
      headline: "Hydrafacial Dubai from AED 249 | Instant Glow, Zero Downtime",
      description: "Deep cleanse \u00b7 Extraction \u00b7 Hydration in one session. Certified therapists. See offers and book your slot online.",
      sitelinks: ["Today's Offers", "Book Online", "Before & After", "Find Us"],
      cpc: "Est. CPC AED 9",
      vol: "~180 searches/day",
      intent: "Intent: ready to book"
    },
    fillers: {
      query: "lip fillers dubai price",
      url: "yourclinic.ae/lip-fillers-dubai",
      headline: "Lip Fillers Dubai from AED 799 | Natural, Subtle Volume",
      description: "Premium hyaluronic fillers \u00b7 Certified aesthetic doctors \u00b7 Free consultation. See prices and book your slot online.",
      sitelinks: ["Prices & Offers", "Book Online", "Before & After", "Find Us"],
      cpc: "Est. CPC AED 22",
      vol: "~140 searches/day",
      intent: "Intent: ready to book"
    }
  };

  // ---------- Cached elements ----------
  var el = {
    card:   document.getElementById("adCard"),
    sel:    document.getElementById("adSel"),
    box:    document.getElementById("adBox"),
    q:      document.getElementById("adQ"),
    url:    document.getElementById("adUrl"),
    hd:     document.getElementById("adHd"),
    ds:     document.getElementById("adDs"),
    sl:     document.getElementById("adSl"),
    cpc:    document.getElementById("adCpc"),
    vol:    document.getElementById("adVol"),
    intent: document.getElementById("adIntent")
  };

  // ---------- Render selected treatment ----------
  function render(key) {
    var ad = ADS[key];
    if (!ad) return;

    el.q.textContent      = ad.query;
    el.url.textContent    = ad.url;
    el.hd.textContent     = ad.headline;
    el.ds.textContent     = ad.description;
    el.cpc.textContent    = ad.cpc;
    el.vol.textContent    = ad.vol;
    el.intent.textContent = ad.intent;

    // Rebuild sitelinks safely (textContent, no innerHTML)
    el.sl.textContent = "";
    ad.sitelinks.forEach(function (label) {
      var a = document.createElement("a");
      a.href = "#";
      a.textContent = label;
      a.addEventListener("click", function (e) { e.preventDefault(); });
      el.sl.appendChild(a);
    });

    // Replay swap animation
    el.box.classList.remove("swap");
    void el.box.offsetWidth; // force reflow so animation restarts
    el.box.classList.add("swap");
  }

  // ---------- Events ----------
  el.sel.addEventListener("change", function () {
    render(this.value);
  });

  // ---------- Init ----------
  render(el.sel.value);

  // Fade the card in on load
  requestAnimationFrame(function () {
    el.card.classList.add("in");
  });
})();;
const bxT = document.getElementById("bxT");
const bxR = document.getElementById("bxR");
const bxO = document.getElementById("bxO");

const bxClicks = document.getElementById("bxClicks");
const bxLeads  = document.getElementById("bxLeads");
const bxBook   = document.getElementById("bxBook");
const bxCpb    = document.getElementById("bxCpb");

// Typical UAE beauty CPCs (AED) per treatment
const bxCpc = {
    laser:   22,
    botox:   28,
    hydra:   14,
    fillers: 16,
    hair:    34,
    nails:   9
};

const BX_CONV    = 0.084;  // 8.4% landing-page conversion
const BX_BOOKING = 0.60;   // 60% of leads become booked appointments

function bxFormatAED(value) {
    return "AED " + Number(value).toLocaleString();
}

// Pink filled portion of the slider track
function bxUpdateFill(slider) {
    const min = Number(slider.min);
    const max = Number(slider.max);
    const val = Number(slider.value);
    const pct = ((val - min) / (max - min)) * 100;
    slider.style.setProperty("--fill", pct + "%");
}

function bxCalculate() {
    const budget = Number(bxR.value);
    const cpc    = bxCpc[bxT.value];

    bxO.textContent = bxFormatAED(budget);

    const clicks   = Math.round(budget / cpc);
    const leads    = Math.round(clicks * BX_CONV);
    const bookings = Math.round(leads * BX_BOOKING);
    const cpb      = bookings > 0 ? Math.round(budget / bookings) : 0;

    bxClicks.textContent = clicks.toLocaleString();
    bxLeads.textContent  = leads.toLocaleString();
    bxBook.textContent   = bookings.toLocaleString();
    bxCpb.textContent    = bxFormatAED(cpb);

    bxUpdateFill(bxR);
}

bxR.addEventListener("input", bxCalculate);
bxT.addEventListener("change", bxCalculate);

bxCalculate();;
