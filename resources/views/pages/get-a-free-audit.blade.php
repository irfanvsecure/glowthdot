@extends('layouts.site')

@section('head')
<title>Free Salon &amp; Clinic Growth Audit | Glowth</title>
<meta name="description" content="Get a free growth audit for your UAE salon or clinic: Google rankings, Business Profile, website speed and competitors, sent on WhatsApp within 48 hours." />
<link rel="canonical" href="/get-a-free-audit/" />
<meta property="og:type" content="website" />
<meta property="og:title" content="Free Salon &amp; Clinic Growth Audit | Glowth" />
<meta property="og:description" content="Get a free growth audit for your UAE salon or clinic: Google rankings, Business Profile, website speed and competitors, sent on WhatsApp within 48 hours." />
<meta property="og:url" content="/get-a-free-audit/" />
<meta property="og:image" content="/images/2026/07/photo-1570172619644-dfd03ed5d881.jpg" />
<meta property="og:image:width" content="700" />
<meta property="og:image:height" content="467" />
<meta property="og:image:type" content="image/jpeg" />
@endsection

@section('styles')
<link rel="stylesheet" href="{{ versioned('/css/pages/get-a-free-audit.css') }}">
@endsection

@section('late_styles')
<link rel="stylesheet" href="{{ versioned('/css/pages/get-a-free-audit-widgets.css') }}">
@endsection

@section('body_class', 'page h-desktop h-page-builder-template h-no-sidebar h-single-post h-hfb-header x-template-full-width x-kit-6 x-page')

@section('content')
<div class="h-container">
<section class="gf" id="free-growth-audit" aria-label="Free Growth Audit">
<div class="bg-mesh">
  <div class="blob blob1"></div>
  <div class="blob blob2"></div>
  <div class="blob blob3"></div>
  <div class="blob blob4"></div>
</div>
<div class="grain"></div>

<div class="wrap">
  <div class="progress-wrap">
    <div class="progress-track"><div class="progress-bar" id="pbar"></div></div>
    <div class="progress-lbl"><span>Free Growth Audit</span><b id="pstep">Step 1 of 7</b></div>
    <div class="trust-strip">
      <span class="trust-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Secure &amp; Private</span>
      <span class="trust-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 5.64L5.64 18.36M12 2v2M12 20v2M2 12h2M20 12h2"/><circle cx="12" cy="12" r="6"/></svg>No Spam, Ever</span>
      <span class="trust-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6M12 15V3M8 7l4-4 4 4"/></svg>100% Free</span>
    </div>
  </div>

  <div class="card">

    <div class="step on" data-step="1">
      <span class="kicker">Let's start here</span>
      <h1>What's your website?</h1>
      <p class="hint">We'll check your rankings, speed and Google presence — takes 90 seconds total.</p>
      <input class="url-input" id="siteUrl" placeholder="yourclinic.ae or @yourinstagram" type="text" aria-label="Your website or Instagram">
      <span class="no-site" id="noSite" role="button" tabindex="0">I don't have a website yet →</span>
      <div class="nav-row"><span></span><button class="next-btn" id="next1" type="button">Continue →</button></div>
    </div>

    <div class="step" data-step="2">
      <span class="kicker">Step 2 of 7</span>
      <h2>What best describes your business?</h2>
      <p class="hint">This helps us benchmark you against the right competitors.</p>
      <div class="chip-grid" id="bizChips">
        <button class="chip" type="button" data-v="Beauty Salon"><span class="ic">💇</span>Beauty Salon</button>
        <button class="chip" type="button" data-v="Aesthetic Clinic"><span class="ic">💉</span>Aesthetic Clinic</button>
        <button class="chip" type="button" data-v="Medspa"><span class="ic">✨</span>Medspa</button>
        <button class="chip" type="button" data-v="Barber Shop"><span class="ic">💈</span>Barber Shop</button>
        <button class="chip" type="button" data-v="Nail Studio"><span class="ic">💅</span>Nail Studio</button>
        <button class="chip" type="button" data-v="Other"><span class="ic">🏢</span>Other</button>
      </div>
      <div class="nav-row"><button class="back-btn" type="button" data-back="1">← Back</button><button class="next-btn" id="next2" type="button" disabled>Continue →</button></div>
    </div>

    <div class="step" data-step="3">
      <span class="kicker">Step 3 of 7</span>
      <h2>What's your main goal right now?</h2>
      <p class="hint">We'll prioritise your audit around this.</p>
      <div class="chip-grid" id="goalChips">
        <button class="chip" type="button" data-v="More bookings">📅 More bookings</button>
        <button class="chip" type="button" data-v="Better Google rankings">🔍 Better rankings</button>
        <button class="chip" type="button" data-v="Fix my ad spend">🎯 Fix my ad spend</button>
        <button class="chip" type="button" data-v="A new website">🖥️ A new website</button>
        <button class="chip" type="button" data-v="AI booking bot">🤖 AI booking bot</button>
        <button class="chip" type="button" data-v="Not sure yet">🤔 Not sure yet</button>
      </div>
      <div class="nav-row"><button class="back-btn" type="button" data-back="2">← Back</button><button class="next-btn" id="next3" type="button" disabled>Continue →</button></div>
    </div>

    <div class="step" data-step="4">
      <span class="kicker">Step 4 of 7</span>
      <h2>Current monthly marketing spend?</h2>
      <p class="hint">No judgement — this just shapes our recommendations.</p>
      <div class="chip-grid" id="budgetChips">
        <button class="chip" type="button" data-v="Not currently spending">AED 0 — not yet</button>
        <button class="chip" type="button" data-v="Under AED 3,000">Under AED 3,000</button>
        <button class="chip" type="button" data-v="AED 3,000–7,000">AED 3,000–7,000</button>
        <button class="chip" type="button" data-v="AED 7,000–15,000">AED 7,000–15,000</button>
        <button class="chip" type="button" data-v="AED 15,000+">AED 15,000+</button>
        <button class="chip" type="button" data-v="Not sure">Not sure</button>
      </div>
      <div class="nav-row"><button class="back-btn" type="button" data-back="3">← Back</button><button class="next-btn" id="next4" type="button" disabled>Continue →</button></div>
    </div>

    <div class="step" data-step="5">
      <span class="kicker">Step 5 of 7</span>
      <h2>Which emirate are you in?</h2>
      <p class="hint">Local search data varies a lot by area.</p>
      <div class="chip-grid" id="emirateChips">
        <button class="chip" type="button" data-v="Dubai">Dubai</button>
        <button class="chip" type="button" data-v="Abu Dhabi">Abu Dhabi</button>
        <button class="chip" type="button" data-v="Sharjah">Sharjah</button>
        <button class="chip" type="button" data-v="Ajman">Ajman</button>
        <button class="chip" type="button" data-v="Ras Al Khaimah">Ras Al Khaimah</button>
        <button class="chip" type="button" data-v="Other Emirate">Other Emirate</button>
      </div>
      <div class="nav-row"><button class="back-btn" type="button" data-back="4">← Back</button><button class="next-btn" id="next5" type="button" disabled>Continue →</button></div>
    </div>

    <div class="step" data-step="6">
      <span class="kicker">Last step</span>
      <h2>Where should we send it?</h2>
      <p class="hint">Your audit arrives on WhatsApp within 48 hours.</p>
      <input class="form-input" id="userName" placeholder="Your name" type="text" autocomplete="name" aria-label="Your name">
      <input class="form-input" id="userBiz" placeholder="Salon / clinic name" type="text" autocomplete="organization" aria-label="Salon / clinic name">
      <input class="form-input" id="userWa" placeholder="WhatsApp number (+971...)" type="tel" autocomplete="tel" aria-label="WhatsApp number">
      <input type="text" id="websiteHp" name="website_hp" style="position:absolute;left:-9999px;opacity:0;" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="privacy-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <span>Your number is only used to send your audit — never shared, sold, or added to a marketing list without your permission.</span>
      </div>
      <div class="nav-row"><button class="back-btn" type="button" data-back="5">← Back</button><button class="next-btn" id="next6" type="button">Get My Free Audit →</button></div>
    </div>

    <div class="step analyzing" data-step="7">
      <div class="spin-wrap"><div class="spin-glow"></div><div class="spin"></div></div>
      <h2 style="margin-bottom:26px">Analyzing your business...</h2>
      <div class="scan-list" id="scanList">
        <div class="scan-item"><span class="dot"></span> Checking Google rankings</div>
        <div class="scan-item"><span class="dot"></span> Reviewing Google Business Profile</div>
        <div class="scan-item"><span class="dot"></span> Testing website speed</div>
        <div class="scan-item"><span class="dot"></span> Benchmarking your competitors</div>
        <div class="scan-item"><span class="dot"></span> Compiling your audit</div>
      </div>
    </div>

    <div class="step done-screen" data-step="8">
      <div class="done-icon">✓</div>
      <h2 id="doneHeadline">You're all set!</h2>
      <p>Your free growth audit is being prepared. We'll message you on WhatsApp within 48 hours with your results and a prioritised action plan.</p>
      <a class="wa-btn" href="#" id="waBackup" target="_blank" rel="noopener">
        <svg viewBox="0 0 32 32"><path d="M16 3C9.4 3 4 8.4 4 15c0 2.6.8 5 2.3 7L4 29l7.2-2.2c1.9 1 4 1.6 4.8 1.6 6.6 0 12-5.4 12-12S22.6 3 16 3zm6.9 17c-.3.8-1.7 1.6-2.4 1.7-.6.1-1.4.1-2.2-.1-.5-.2-1.2-.4-2-.8-3.6-1.5-5.9-5.1-6.1-5.4-.2-.2-1.4-1.9-1.4-3.6s.9-2.5 1.2-2.9c.3-.3.7-.4 1-.4h.7c.2 0 .5-.1.8.6.3.8 1.1 2.6 1.2 2.8.1.2.2.4 0 .7-.1.2-.2.4-.4.6l-.6.7c-.2.2-.4.4-.2.8.2.4 1 1.7 2.2 2.7 1.5 1.4 2.8 1.8 3.2 2 .4.2.6.1.9-.1.2-.3 1-1.2 1.3-1.6.3-.4.5-.3.9-.2.4.1 2.4 1.1 2.8 1.3.4.2.7.3.8.5.1.2.1.9-.2 1.7z"/></svg>
        Message Us Now Instead
      </a>
      <div class="summary-pills" id="summaryPills"></div>
      <div class="done-actions">
        <button class="redo-btn" id="redoBtn" type="button">
          <svg viewBox="0 0 24 24"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
          Entered something wrong? Start over
        </button>
      </div>
    </div>

  </div>
</div>
</section>

</div>
@endsection

@section('scripts')
<script src="{{ versioned('/js/pages/get-a-free-audit.js') }}" defer></script>
@endsection
