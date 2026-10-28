/* Widgets inside the page (calculators, tabs, counters) */
const price=document.getElementById('price');
const bookings=document.getElementById('bookings');
const growth=document.getElementById('growth');

function number(x){

return x.toLocaleString("en-US");

}

function sliderFill(slider){

let min=slider.min;

let max=slider.max;

let val=slider.value;

let percent=((val-min)/(max-min))*100;

slider.style.background=

`linear-gradient(to right,#7345ff 0%,#7345ff ${percent}%,#d9d9d9 ${percent}%,#d9d9d9 100%)`;

}

function update(){

let treatment=Number(price.value);

let currentBookings=Number(bookings.value);

let growthPercent=Number(growth.value);

let extra=Math.round(currentBookings*(growthPercent/100));

let monthly=extra*treatment;

let yearly=monthly*12;

document.getElementById('priceValue').innerHTML="AED "+number(treatment);

document.getElementById('bookingValue').innerHTML=number(currentBookings);

document.getElementById('growthValue').innerHTML=growthPercent+"%";

document.getElementById('extraBookings').innerHTML=number(extra);

document.getElementById('monthly').innerHTML="AED "+number(monthly);

document.getElementById('yearly').innerHTML="AED "+number(yearly);

sliderFill(price);

sliderFill(bookings);

sliderFill(growth);

}

price.oninput=update;
bookings.oninput=update;
growth.oninput=update;

update();;
(function () {
    var root = document.getElementById('find-your-fix');
    if (!root) return;
    var tabs = [].slice.call(root.querySelectorAll('[role="tab"]'));
    function select(tab, focus) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.tabIndex = on ? 0 : -1;
        document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
      });
      if (focus) tab.focus();
    }
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { select(t); });
      t.addEventListener('keydown', function (e) {
        var k = e.key, n = null;
        if (k === 'ArrowDown' || k === 'ArrowRight') n = tabs[(i + 1) % tabs.length];
        if (k === 'ArrowUp' || k === 'ArrowLeft') n = tabs[(i - 1 + tabs.length) % tabs.length];
        if (k === 'Home') n = tabs[0];
        if (k === 'End') n = tabs[tabs.length - 1];
        if (n) { e.preventDefault(); select(n, true); }
      });
    });
  })();;
