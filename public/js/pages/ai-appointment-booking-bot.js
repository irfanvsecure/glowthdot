/* Widgets inside the page (calculators, tabs, counters) */
const questions = [
  "Do clients message your salon/clinic after closing hours?",
  "Do calls go unanswered when your team is busy with clients?",
  "Do patients leave without booking after visiting your website?",
  "Are follow-ups missed with potential patients?"
];

let current = 0;
let leaks = 0;

const qbox = document.getElementById("qbox");
const qq = document.getElementById("qq");

const qres = document.getElementById("qres");
const qscore = document.getElementById("qscore");
const qmsg = document.getElementById("qmsg");

const progress = document.querySelectorAll("#qprog i");
const buttons = document.querySelectorAll(".qbtn");

// Hide result initially
qbox.style.display = "block";
qres.style.display = "none";

// Load first question
loadQuestion();

function loadQuestion() {

  // Show current question
  qq.innerHTML = questions[current];

  // Keep previous progress bars active
  progress.forEach((item, index) => {

    if (index <= current) {
      item.classList.add("active");
    } else {
      item.classList.remove("active");
    }

  });

}

buttons.forEach(button => {

  button.addEventListener("click", function () {

    // Count leaks if user clicked Yes
    if (Number(this.dataset.v) === 1) {
      leaks++;
    }

    current++;

    if (current < questions.length) {

      loadQuestion();

    } else {

      showResult();

    }

  });

});

function showResult() {

  qbox.style.display = "none";
  qres.style.display = "block";

  // Make all bars active
  progress.forEach(item => {
    item.classList.add("active");
  });

  qscore.innerHTML = leaks + "/4 leaks";

  if (leaks === 0) {

    qmsg.innerHTML =
      "Great! No obvious leaks found. Keep optimizing your patient journey.";

  } else if (leaks <= 2) {

    qmsg.innerHTML =
      "Some leaks found. Small gaps can quietly cost bookings — worth fixing.";

  } else {

    qmsg.innerHTML =
      "Multiple leaks found. Your clinic may be losing valuable bookings every week.";

  }

};
const switchBtn = document.getElementById("sw");
const lblOff = document.getElementById("lblOff");
const lblOn = document.getElementById("lblOn");

const values = document.querySelectorAll(".tg b");

let botOn = false;


switchBtn.addEventListener("click", () => {

  botOn = !botOn;

  switchBtn.classList.toggle("active", botOn);

  lblOff.classList.toggle("act", !botOn);
  lblOn.classList.toggle("act", botOn);


  values.forEach(item => {

    if(botOn){

      item.innerHTML = item.dataset.on;
      item.classList.remove("bad");
      item.classList.add("good");

    } else {

      item.innerHTML = item.dataset.off;
      item.classList.remove("good");
      item.classList.add("bad");

    }

  });

});;
const rM = document.getElementById("rM");
const rC = document.getElementById("rC");
const rV = document.getElementById("rV");

const oM = document.getElementById("oM");
const oC = document.getElementById("oC");
const oV = document.getElementById("oV");

const mBook = document.getElementById("mBook");
const mRev = document.getElementById("mRev");
const mYear = document.getElementById("mYear");

function formatAED(value) {
    return "AED " + Number(value).toLocaleString();
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
    const missed = Number(rM.value);
    const conversion = Number(rC.value) / 100;
    const value = Number(rV.value);

    oM.textContent = missed;
    oC.textContent = Math.round(conversion * 100) + "%";
    oV.textContent = formatAED(value);

    // 4.33 weeks per month
    const monthlyBookings = Math.round(missed * conversion * 4.33);
    const monthlyRevenue = monthlyBookings * value;
    const yearlyRevenue = monthlyRevenue * 12;

    mBook.textContent = monthlyBookings;
    mRev.textContent = formatAED(monthlyRevenue);
    mYear.textContent = formatAED(yearlyRevenue);

    // Update slider fill colors
    [rM, rC, rV].forEach(updateFill);
}

[rM, rC, rV].forEach(slider => {
    slider.addEventListener("input", calculate);
});

calculate();;
