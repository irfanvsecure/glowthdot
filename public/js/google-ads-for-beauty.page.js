(function(){function c(){document.querySelectorAll(".cfmd-rc-host,.cfmd-rc-blur,[data-cfmd-instr],[data-cfmd-theme],[class*=cfmd-]").forEach(function(e){e.remove();});}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",c);}else{c();}new MutationObserver(c).observe(document.documentElement,{childList:true,subtree:true});})();
;
(function(){var mq=window.matchMedia('(max-width:921.99px)');function apply(isMobile){var b=document.body.classList;if(isMobile){b.add('ast-header-break-point');b.remove('ast-desktop');}else{b.remove('ast-header-break-point');b.add('ast-desktop');}}apply(mq.matches);if(mq.addEventListener){mq.addEventListener('change',function(e){apply(e.matches);});}else if(mq.addListener){mq.addListener(function(e){apply(e.matches);});}})();
;
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
})();
;
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

bxCalculate();
;
{"prefetch":[{"source":"document","where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/wp-*.php","/wp-admin/*","/images/*","/wp-content/*","/assets/plugins/*","/assets/themes/astra/*","/*\\?(.+)"]}},{"not":{"selector_matches":"a[rel~=\"nofollow\"]"}},{"not":{"selector_matches":".no-prefetch, .no-prefetch a"}}]},"eagerness":"conservative"}]}
;
( () => {
					const lazyloadRunObserver = () => {
						const lazyloadBackgrounds = document.querySelectorAll( `.e-con.e-parent:not(.e-lazyloaded)` );
						const lazyloadBackgroundObserver = new IntersectionObserver( ( entries ) => {
							entries.forEach( ( entry ) => {
								if ( entry.isIntersecting ) {
									let lazyloadBackground = entry.target;
									if( lazyloadBackground ) {
										lazyloadBackground.classList.add( 'e-lazyloaded' );
									}
									lazyloadBackgroundObserver.unobserve( entry.target );
								}
							});
						}, { rootMargin: '200px 0px 200px 0px' } );
						lazyloadBackgrounds.forEach( ( lazyloadBackground ) => {
							lazyloadBackgroundObserver.observe( lazyloadBackground );
						} );
					};
					const events = [
						'DOMContentLoaded',
						'elementor/lazyload/observe',
					];
					events.forEach( ( event ) => {
						document.addEventListener( event, lazyloadRunObserver );
					} );
				} )();
;
var astra = {"break_point":"921","isRtl":"","is_scroll_to_id":"1","is_scroll_to_top":"1","is_header_footer_builder_active":"1","responsive_cart_click":"flyout","is_dark_palette":""};
//# sourceURL=astra-theme-js-js-extra
;
jQuery.uiBackCompat = true;
//# sourceURL=jquery-ui-core-js-before
;
var PremiumSettings = {"ajaxurl":"#","nonce":"0faaaf305e"};
//# sourceURL=elementor-frontend-js-extra
;
var elementorFrontendConfig = {"environmentMode":{"edit":false,"wpPreview":false,"isScriptDebug":false},"i18n":{"shareOnFacebook":"Share on Facebook","shareOnX":"Share on X","pinIt":"Pin it","download":"Download","downloadImage":"Download image","fullscreen":"Fullscreen","zoom":"Zoom","share":"Share","playVideo":"Play Video","previous":"Previous","next":"Next","close":"Close","a11yCarouselPrevSlideMessage":"Previous slide","a11yCarouselNextSlideMessage":"Next slide","a11yCarouselFirstSlideMessage":"This is the first slide","a11yCarouselLastSlideMessage":"This is the last slide","a11yCarouselPaginationBulletMessage":"Go to slide"},"is_rtl":false,"breakpoints":{"xs":0,"sm":480,"md":768,"lg":1025,"xl":1440,"xxl":1600},"responsive":{"breakpoints":{"mobile":{"label":"Mobile Portrait","value":767,"default_value":767,"direction":"max","is_enabled":true},"mobile_extra":{"label":"Mobile Landscape","value":880,"default_value":880,"direction":"max","is_enabled":false},"tablet":{"label":"Tablet Portrait","value":1024,"default_value":1024,"direction":"max","is_enabled":true},"tablet_extra":{"label":"Tablet Landscape","value":1200,"default_value":1200,"direction":"max","is_enabled":false},"laptop":{"label":"Laptop","value":1366,"default_value":1366,"direction":"max","is_enabled":false},"widescreen":{"label":"Widescreen","value":2400,"default_value":2400,"direction":"min","is_enabled":false}},"hasCustomBreakpoints":false},"version":"4.3.5","is_static":false,"experimentalFeatures":{"e_font_icon_svg":true,"additional_custom_breakpoints":true,"container":true,"e_optimized_markup":true,"theme_builder_v2":true,"nested-elements":true,"e_atomic_elements":true,"e_opt_in_v4":true,"import-export-customization":true,"e_pro_atomic_form":true,"e_pro_collection_loop":true,"mega-menu":true},"urls":{"assets":"\/assets\/plugins\/elementor\/assets\/","ajaxurl":"#","uploadUrl":"\/wp-content\/uploads"},"nonces":{"floatingButtonsClickTracking":"5ccc746be5","atomicFormsSendForm":"d7f8163c64"},"swiperClass":"swiper","settings":{"page":[],"editorPreferences":[]},"kit":{"active_breakpoints":["viewport_mobile","viewport_tablet"],"global_image_lightbox":"yes","lightbox_enable_counter":"yes","lightbox_enable_fullscreen":"yes","lightbox_enable_zoom":"yes","lightbox_enable_share":"yes","lightbox_title_src":"title","lightbox_description_src":"description"},"post":{"id":446,"title":"Google%20Ads%20for%20Beauty%20Salons%20%26%20Clinics%20UAE%20%7C%20Glowth","excerpt":"","featuredImage":false}};
//# sourceURL=elementor-frontend-js-before
;
wp.i18n.setLocaleData( { 'text direction\u0004ltr': [ 'ltr' ] } );
//# sourceURL=wp-i18n-js-after
;
var ElementorProFrontendConfig = {"ajaxurl":"#","nonce":"8b3fee644d","urls":{"assets":"\/assets\/plugins\/elementor-pro\/assets\/","rest":"\/wp-json\/"},"settings":{"lazy_load_background_images":true},"popup":{"hasPopUps":false},"shareButtonsNetworks":{"facebook":{"title":"Facebook","has_counter":true},"twitter":{"title":"Twitter"},"linkedin":{"title":"LinkedIn","has_counter":true},"pinterest":{"title":"Pinterest","has_counter":true},"reddit":{"title":"Reddit","has_counter":true},"vk":{"title":"VK","has_counter":true},"odnoklassniki":{"title":"OK","has_counter":true},"tumblr":{"title":"Tumblr"},"digg":{"title":"Digg"},"skype":{"title":"Skype"},"stumbleupon":{"title":"StumbleUpon","has_counter":true},"mix":{"title":"Mix"},"telegram":{"title":"Telegram"},"pocket":{"title":"Pocket","has_counter":true},"xing":{"title":"XING","has_counter":true},"whatsapp":{"title":"WhatsApp"},"email":{"title":"Email"},"print":{"title":"Print"},"x-twitter":{"title":"X"},"threads":{"title":"Threads"}},"facebook_sdk":{"lang":"en_US","app_id":""},"lottie":{"defaultAnimationUrl":"\/assets\/plugins\/elementor-pro\/modules\/lottie\/assets\/animations\/default.json"}};
//# sourceURL=elementor-pro-frontend-js-before
;
/(trident|msie)/i.test(navigator.userAgent)&&document.getElementById&&window.addEventListener&&window.addEventListener("hashchange",function(){var t,e=location.hash.substring(1);/^[A-z0-9_-]+$/.test(e)&&(t=document.getElementById(e))&&(/^(?:a|select|input|button|textarea)$/i.test(t.tagName)||(t.tabIndex=-1),t.focus())},!1);
;
{"baseUrl":"https://s.w.org/images/core/emoji/17.0.2/72x72/","ext":".png","svgUrl":"https://s.w.org/images/core/emoji/17.0.2/svg/","svgExt":".svg","source":{"concatemoji":"/assets/includes/js/wp-emoji-release.min.js?ver=7.1.3"}}
;
/*! This file is auto-generated */
var e="script#wp-emoji-settings",t=document.querySelector(e);if(!(t instanceof HTMLScriptElement))throw new Error("Element missing: "+e);const r=JSON.parse(t.text),s=(window._wpemojiSettings=r,"wpEmojiSettingsSupports"),o=["flag","emoji"];function i(e){try{var t={supportTests:e,timestamp:(new Date).valueOf()};sessionStorage.setItem(s,JSON.stringify(t))}catch(e){}}function c(e,t,n){e.clearRect(0,0,e.canvas.width,e.canvas.height),e.fillText(t,0,0);t=new Uint32Array(e.getImageData(0,0,e.canvas.width,e.canvas.height).data);e.clearRect(0,0,e.canvas.width,e.canvas.height),e.fillText(n,0,0);const r=new Uint32Array(e.getImageData(0,0,e.canvas.width,e.canvas.height).data);return t.every((e,t)=>e===r[t])}function p(e,t){e.clearRect(0,0,e.canvas.width,e.canvas.height),e.fillText(t,0,0);var n=e.getImageData(16,16,1,1);for(let e=0;e<n.data.length;e++)if(0!==n.data[e])return!1;return!0}function u(e,t,n,r){switch(t){case"flag":return n(e,"\ud83c\udff3\ufe0f\u200d\u26a7\ufe0f","\ud83c\udff3\ufe0f\u200b\u26a7\ufe0f")?!1:!n(e,"\ud83c\udde8\ud83c\uddf6","\ud83c\udde8\u200b\ud83c\uddf6")&&!n(e,"\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc65\udb40\udc6e\udb40\udc67\udb40\udc7f","\ud83c\udff4\u200b\udb40\udc67\u200b\udb40\udc62\u200b\udb40\udc65\u200b\udb40\udc6e\u200b\udb40\udc67\u200b\udb40\udc7f");case"emoji":return!r(e,"\ud83e\u1fac8")}return!1}function f(e,t,n,r){let a;const s=(a="undefined"!=typeof WorkerGlobalScope&&self instanceof WorkerGlobalScope?new OffscreenCanvas(300,150):document.createElement("canvas")).getContext("2d",{willReadFrequently:!0}),o=(s.textBaseline="top",s.font="600 32px Arial",{});return e.forEach(e=>{o[e]=t(s,e,n,r)}),o}function a(e){var t=document.createElement("script");t.src=e,t.defer=!0,document.head.appendChild(t)}r.supports={everything:!0,everythingExceptFlag:!0},new Promise(t=>{let n=function(){try{var e=JSON.parse(sessionStorage.getItem(s));if("object"==typeof e&&"number"==typeof e.timestamp&&(new Date).valueOf()<e.timestamp+604800&&"object"==typeof e.supportTests)return e.supportTests}catch(e){}return null}();if(!n){if("undefined"!=typeof Worker&&"undefined"!=typeof OffscreenCanvas&&"undefined"!=typeof URL&&URL.createObjectURL&&"undefined"!=typeof Blob)try{var e="postMessage("+f.toString()+"("+[JSON.stringify(o),u.toString(),c.toString(),p.toString()].join(",")+"));",r=new Blob([e],{type:"text/javascript"});const a=new Worker(URL.createObjectURL(r),{name:"wpTestEmojiSupports"});return void(a.onmessage=e=>{i(n=e.data),a.terminate(),t(n)})}catch(e){}i(n=f(o,u,c,p))}t(n)}).then(e=>{for(const n in e)r.supports[n]=e[n],r.supports.everything=r.supports.everything&&r.supports[n],"flag"!==n&&(r.supports.everythingExceptFlag=r.supports.everythingExceptFlag&&r.supports[n]);var t;r.supports.everythingExceptFlag=r.supports.everythingExceptFlag&&!r.supports.flag,r.supports.everything||((t=r.source||{}).concatemoji?a(t.concatemoji):t.wpemoji&&t.twemoji&&(a(t.twemoji),a(t.wpemoji)))});
//# sourceURL=/assets/includes/js/wp-emoji-loader.min.js
;
