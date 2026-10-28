/* Widgets inside the page (calculators, tabs, counters) */
(function () {
    const slowT    = document.getElementById("slowT");
    const fastT    = document.getElementById("fastT");
    const slowBar  = document.getElementById("slowBar");
    const fastBar  = document.getElementById("fastBar");
    const slowLost = document.getElementById("slowLost");
    const fastLost = document.getElementById("fastLost");
    const raceBtn  = document.getElementById("raceBtn");
    const raceNote = document.getElementById("raceNote");

    const FAST_TIME = 1.2;   // seconds — Glowth build
    const SLOW_TIME = 7.4;   // seconds — typical salon site

    let racing = false;
    let rafId  = null;

    function reset() {
        slowT.textContent = "0.0s";
        fastT.textContent = "0.0s";
        slowBar.style.width = "0%";
        fastBar.style.width = "0%";
        slowLost.textContent = "Visitors waiting\u2026";
        fastLost.textContent = "Ready to race.";
        raceNote.textContent = "";
    }

    function run() {
        if (racing) return;
        racing = true;
        raceBtn.disabled = true;
        reset();

        const start = performance.now();
        let fastDone = false;
        let slowDone = false;

        function frame(now) {
            const t = (now - start) / 1000; // elapsed seconds

            /* ---- Fast site ---- */
            if (!fastDone) {
                const ft = Math.min(t, FAST_TIME);
                fastT.textContent = ft.toFixed(1) + "s";
                fastBar.style.width = (ft / FAST_TIME * 100) + "%";
                if (t >= FAST_TIME) {
                    fastDone = true;
                    fastBar.style.width = "100%";
                    fastLost.textContent = "Loaded. Booking button already on screen. \u2705";
                }
            }

            /* ---- Slow site ---- */
            if (!slowDone) {
                const st = Math.min(t, SLOW_TIME);
                slowT.textContent = st.toFixed(1) + "s";
                slowBar.style.width = (st / SLOW_TIME * 100) + "%";

                if (st >= SLOW_TIME) {
                    slowDone = true;
                    slowBar.style.width = "100%";
                    slowLost.textContent = "Finally loaded \u2014 53% of visitors already left.";
                } else if (st > 5) {
                    slowLost.textContent = "Most visitors have given up\u2026";
                } else if (st > 3) {
                    slowLost.textContent = "53% of mobile visitors abandon after 3s\u2026";
                } else if (st > FAST_TIME) {
                    slowLost.textContent = "Still loading\u2026 visitors getting impatient.";
                }
            }

            if (!fastDone || !slowDone) {
                rafId = requestAnimationFrame(frame);
            } else {
                racing = false;
                raceBtn.disabled = false;
                raceBtn.innerHTML = "&#8635; Run It Again";
                const diff = (SLOW_TIME / FAST_TIME).toFixed(0);
                raceNote.textContent = "The Glowth build finished " + diff + "\u00D7 faster \u2014 while the typical site was still loading, your page was already taking the booking.";
            }
        }

        rafId = requestAnimationFrame(frame);
    }

    raceBtn.addEventListener("click", run);
})();;
