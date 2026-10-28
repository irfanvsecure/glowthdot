/* Widgets inside the page (calculators, tabs, counters) */
(function(){
const WHATSAPP_NUMBER = "971588979925";

const API_ENDPOINT = "/api/audit";

const root = document.getElementById('free-growth-audit');

const answers = {
    url: '',
    hasSite: true,
    biz: '',
    goal: '',
    budget: '',
    emirate: '',
    name: '',
    bizName: '',
    wa: ''
};

let submitting = false;

// -------------------------
// STEP CONTROL
// -------------------------

function showStep(n){
    root.querySelectorAll('.step').forEach(step => step.classList.remove('on'));

    const current = root.querySelector(`.step[data-step="${n}"]`);
    if(current){
        current.classList.add('on');
    }

    document.getElementById('pbar').style.width = (Math.min(n,7) / 7 * 100) + '%';
    document.getElementById('pstep').textContent = n <= 7 ? `Step ${n} of 7` : 'Complete';

    // Bring the funnel back into view (below the sticky site header) if the visitor scrolled past it
    const top = root.getBoundingClientRect().top;
    if(top < 0){
        window.scrollTo({ top: top + window.scrollY - 80, behavior:'smooth' });
    }
}

// -------------------------
// BACK BUTTONS
// -------------------------

root.querySelectorAll('[data-back]').forEach(btn=>{
    btn.addEventListener('click',()=>{
        showStep(Number(btn.dataset.back));
    });
});

// -------------------------
// STEP 1
// -------------------------

function noSite(){
    answers.hasSite = false;
    answers.url = "No website yet";
    showStep(2);
}
document.getElementById('noSite').addEventListener('click', noSite);
document.getElementById('noSite').addEventListener('keydown', e=>{
    if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); noSite(); }
});

document.getElementById('next1').addEventListener('click',()=>{
    const website = document.getElementById('siteUrl').value.trim();
    answers.url = website || "Not provided";
    answers.hasSite = Boolean(website);
    showStep(2);
});

document.getElementById('siteUrl').addEventListener('keydown', e=>{
    if(e.key === 'Enter') document.getElementById('next1').click();
});

// -------------------------
// CHIP SELECT STEPS
// -------------------------

function wireChipStep(gridId, buttonId, key, nextStep){
    const grid = document.getElementById(gridId);
    const next = document.getElementById(buttonId);

    grid.querySelectorAll('.chip').forEach(chip=>{
        chip.addEventListener('click',()=>{
            grid.querySelectorAll('.chip').forEach(c => c.classList.remove('sel'));
            chip.classList.add('sel');
            answers[key] = chip.dataset.v;
            next.disabled = false;
        });
    });

    next.addEventListener('click',()=>{
        showStep(nextStep);
    });
}

wireChipStep('bizChips', 'next2', 'biz', 3);
wireChipStep('goalChips', 'next3', 'goal', 4);
wireChipStep('budgetChips', 'next4', 'budget', 5);
wireChipStep('emirateChips', 'next5', 'emirate', 6);

// -------------------------
// FINAL SUBMIT
// -------------------------

['userName','userWa'].forEach(id=>{
    document.getElementById(id).addEventListener('input', e=>e.target.classList.remove('err'));
});

document.getElementById('next6').addEventListener('click', async ()=>{
    if(submitting) return;

    const nameInput = document.getElementById('userName');
    const waInput = document.getElementById('userWa');

    answers.name = nameInput.value.trim();
    answers.bizName = document.getElementById('userBiz').value.trim();
    answers.wa = waInput.value.trim();

    if(!answers.name){
        nameInput.classList.add('err');
        nameInput.focus();
        return;
    }

    if(!answers.wa){
        waInput.classList.add('err');
        waInput.focus();
        return;
    }

    submitting = true;

    const btn = document.getElementById('next6');
    btn.disabled = true;
    btn.innerHTML = "Sending...";

    const payload = {
        name: answers.name,
        bizName: answers.bizName,
        wa: answers.wa,
        url: answers.url,
        biz: answers.biz,
        goal: answers.goal,
        budget: answers.budget,
        emirate: answers.emirate,
        website_hp: document.getElementById('websiteHp').value
    };

    try{
        const res = await fetch(API_ENDPOINT, {
            method:"POST",
            headers:{ "Content-Type":"application/json", "Accept":"application/json" },
            body: JSON.stringify(payload)
        });
        if(!res.ok) console.error("Lead Error:", res.status);
    }
    catch(error){
        // The done screen still offers the WhatsApp message as a backup
        console.error("Lead Error:", error);
    }

    showStep(7);
    runScan();
});

// -------------------------
// SCAN ANIMATION
// -------------------------

function runScan(){
    const items = root.querySelectorAll('.scan-item');

    items.forEach((item,index)=>{
        setTimeout(()=>{ item.classList.add('done'); }, 600 + index*650);
    });

    setTimeout(()=>{
        document.getElementById('doneHeadline').textContent =
            `You're all set, ${answers.name.split(' ')[0]}!`;

        const parts = [answers.biz, answers.goal, answers.emirate, answers.budget].filter(Boolean);

        const pills = document.getElementById('summaryPills');
        pills.innerHTML = '';
        parts.forEach(item=>{
            const span = document.createElement('span');
            span.textContent = item;
            pills.appendChild(span);
        });

        const message = encodeURIComponent(
`Hi Glowth! I just completed the free audit form.

Business:
${answers.bizName || '-'}

Website:
${answers.url}

Goal:
${answers.goal}

Budget:
${answers.budget}

Emirate:
${answers.emirate}

Name:
${answers.name}`
        );

        document.getElementById('waBackup').href = `https://wa.me/${WHATSAPP_NUMBER}?text=${message}`;

        showStep(8);
        fireConfetti();
    }, 600 + items.length*650 + 400);
}

// -------------------------
// CONFETTI
// -------------------------

function fireConfetti(){
    if(window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const colors=['#FF2E7E','#7C3AED','#C6F432','#2EE6D6','#fff'];

    for(let i=0;i<70;i++){
        const c=document.createElement('div');
        c.className='gf-confetti';
        c.style.left = Math.random()*100+'vw';
        c.style.background = colors[Math.floor(Math.random()*colors.length)];
        c.style.animation = `gfConfettiFall ${2.2+Math.random()*1.6}s cubic-bezier(.3,.6,.7,1) forwards`;
        document.body.appendChild(c);
        setTimeout(()=>{ c.remove(); },4200);
    }
}

// -------------------------
// RESET FORM
// -------------------------

document.getElementById('redoBtn').addEventListener('click',()=>{
    Object.keys(answers).forEach(key=>{
        answers[key] = key==="hasSite" ? true : '';
    });

    ['siteUrl','userName','userBiz','userWa'].forEach(id=>{
        const el=document.getElementById(id);
        if(el) el.value='';
    });

    root.querySelectorAll('.chip.sel').forEach(c=> c.classList.remove('sel'));

    ['next2','next3','next4','next5'].forEach(id=>{
        document.getElementById(id).disabled=true;
    });

    root.querySelectorAll('.scan-item.done').forEach(item=> item.classList.remove('done'));

    const btn = document.getElementById('next6');
    btn.disabled = false;
    btn.innerHTML = "Get My Free Audit →";

    submitting=false;

    showStep(1);
});

// -------------------------
// ARRIVING FROM THE HOME PAGE
// ?goal=... (problem picker) picks the step 3 answer, ?site=... (hero card) answers step 1
// -------------------------

const params = new URLSearchParams(location.search);
const goal = params.get('goal');
if(goal){
    const chip = [...document.querySelectorAll('#goalChips .chip')].find(c => c.dataset.v === goal);
    if(chip) chip.click();
}
const fromHero = params.get('site');
if(fromHero !== null){
    const site = fromHero.trim().slice(0, 200);
    if(site === 'none'){
        noSite();
    } else if(site){
        document.getElementById('siteUrl').value = site;
        document.getElementById('next1').click();
    } else {
        document.getElementById('siteUrl').focus();
    }
}
})();;
