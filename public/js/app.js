document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.getElementById('site-nav');

  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  const price = document.getElementById('avg-price');
  const bookings = document.getElementById('bookings');
  const growth = document.getElementById('growth');
  const priceOut = document.getElementById('avg-price-out');
  const bookingsOut = document.getElementById('bookings-out');
  const growthOut = document.getElementById('growth-out');
  const revenueOut = document.getElementById('extra-revenue');
  const extraBookingsOut = document.getElementById('extra-bookings');
  const yearOut = document.getElementById('year-revenue');

  const formatAed = (n) =>
    'AED ' + Math.round(n).toLocaleString('en-US');

  const updateCalc = () => {
    if (!price || !bookings || !growth) return;

    const p = Number(price.value);
    const b = Number(bookings.value);
    const g = Number(growth.value) / 100;
    const extraBookings = Math.round(b * g);
    const monthly = extraBookings * p;
    const yearly = monthly * 12;

    if (priceOut) priceOut.textContent = 'AED ' + p.toLocaleString('en-US');
    if (bookingsOut) bookingsOut.textContent = String(b);
    if (growthOut) growthOut.textContent = growth.value + '%';
    if (revenueOut) revenueOut.textContent = formatAed(monthly);
    if (extraBookingsOut) extraBookingsOut.textContent = String(extraBookings);
    if (yearOut) yearOut.textContent = formatAed(yearly);
  };

  [price, bookings, growth].forEach((el) => {
    if (el) el.addEventListener('input', updateCalc);
  });
  updateCalc();
});
