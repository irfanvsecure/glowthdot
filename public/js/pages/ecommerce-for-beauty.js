/* Widgets inside the page (calculators, tabs, counters) */
const cartCount = document.getElementById("cartN");
const orderFeed = document.getElementById("ordfeed");
const addButtons = document.querySelectorAll(".addb");

let cart = 0;

const messages = [
  "📦 Aftercare Kit added to cart.",
  "✨ Glow Serum added to cart.",
  "🧴 At-Home Duo Bundle added to cart.",
  "✅ Item successfully added.",
  "🛍️ Ready for checkout."
];

addButtons.forEach((button, index) => {

  button.addEventListener("click", function () {

    cart++;

    // Update cart number
    cartCount.innerText = cart;

    // Button state
    this.innerText = "Added ✓";
    this.disabled = true;
    this.style.background = "#C6F432";
    this.style.color = "#111";
    this.style.cursor = "default";

    // Add activity message
    const item = document.createElement("div");
    item.innerHTML = messages[index];
    orderFeed.prepend(item);

  });

});;
const rCl = document.getElementById("rCl");
const rP  = document.getElementById("rP");
const rB  = document.getElementById("rB");

const oCl = document.getElementById("oCl");
const oP  = document.getElementById("oP");
const oB  = document.getElementById("oB");

const sOrders  = document.getElementById("sOrders");
const sMonthly = document.getElementById("sMonthly");
const sYearly  = document.getElementById("sYearly");

function formatAED(value) {
    return "AED " + Number(value).toLocaleString();
}

// Compact format: AED 22k / AED 1.2M
function formatAEDCompact(value) {
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

// Pink filled portion of the slider track
function updateFill(slider) {
    const min = Number(slider.min);
    const max = Number(slider.max);
    const val = Number(slider.value);
    const pct = ((val - min) / (max - min)) * 100;
    slider.style.setProperty("--fill", pct + "%");
}

function calculate() {
    const clients = Number(rCl.value);
    const pct     = Number(rP.value) / 100;
    const basket  = Number(rB.value);

    oCl.textContent = clients.toLocaleString();
    oP.textContent  = Math.round(pct * 100) + "%";
    oB.textContent  = formatAED(basket);

    const orders  = Math.round(clients * pct);
    const monthly = orders * basket;
    const yearly  = monthly * 12;

    sOrders.textContent  = orders.toLocaleString();
    sMonthly.textContent = formatAEDCompact(monthly);
    sYearly.textContent  = formatAEDCompact(yearly);

    // Update slider fill colors
    [rCl, rP, rB].forEach(updateFill);
}

[rCl, rP, rB].forEach(slider => {
    slider.addEventListener("input", calculate);
});

calculate();;
