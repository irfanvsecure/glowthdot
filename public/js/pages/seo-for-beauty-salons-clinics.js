/* Widgets inside the page (calculators, tabs, counters) */
const kxT = document.getElementById("kxT");
const kxC = document.getElementById("kxC");

const kxVol    = document.getElementById("kxVol");
const kxClicks = document.getElementById("kxClicks");
const kxCpc    = document.getElementById("kxCpc");
const kxWorth  = document.getElementById("kxWorth");
const kxKw     = document.getElementById("kxKw");
const kxLong   = document.getElementById("kxLong");

// Illustrative UAE search data per treatment
const kxData = {
    laser:   { kw: "laser hair removal", cpc: 22, long: 120, vol: { dxb: 8100, auh: 2400, shj: 1300 } },
    botox:   { kw: "botox",              cpc: 28, long: 95,  vol: { dxb: 5400, auh: 1600, shj: 880  } },
    hydra:   { kw: "hydrafacial",        cpc: 14, long: 70,  vol: { dxb: 3600, auh: 1000, shj: 590  } },
    fillers: { kw: "lip fillers",        cpc: 16, long: 85,  vol: { dxb: 2900, auh: 1000, shj: 590  } },
    hair:    { kw: "hair transplant",    cpc: 34, long: 140, vol: { dxb: 6600, auh: 1900, shj: 720  } },
    nails:   { kw: "nail salon",         cpc: 9,  long: 60,  vol: { dxb: 9900, auh: 2900, shj: 1600 } }
};

const kxCities = {
    dxb: "dubai",
    auh: "abu dhabi",
    shj: "sharjah"
};

// Compact format: AED 2.8k / AED 1.2M
function kxFormat(value) {
    if (value >= 1000000) {
        const m = value / 1000000;
        return "AED " + (m % 1 === 0 ? m : m.toFixed(1)) + "M";
    }
    if (value >= 1000) {
        const k = value / 1000;
        return "AED " + (k % 1 === 0 ? k : k.toFixed(1)) + "k";
    }
    return "AED " + value;
}

function kxUpdate() {
    const t = kxData[kxT.value];
    const city = kxC.value;

    const vol    = t.vol[city];
    const clicks = Math.round(vol * 0.30);      // ~30% CTR for #1 result
    const worth  = clicks * t.cpc;

    kxVol.textContent    = vol.toLocaleString();
    kxClicks.textContent = "~" + clicks.toLocaleString();
    kxCpc.textContent    = "AED " + t.cpc;
    kxWorth.textContent  = kxFormat(worth);
    kxKw.textContent     = '"' + t.kw + " " + kxCities[city] + '"';
    kxLong.textContent   = t.long + "+";
}

[kxT, kxC].forEach(sel => {
    sel.addEventListener("change", kxUpdate);
});

kxUpdate();;
