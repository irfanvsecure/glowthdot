@php
    // The page being shown, as the menu links write it: / or /about-us/
    $here = trim(request()->path(), '/');
    $here = $here === '' ? '/' : '/'.$here.'/';
    $on = fn (string ...$urls) => in_array($here, $urls, true);
@endphp
<header
		class="site-header header-main-layout-1 h-primary-menu-enabled h-hide-custom-menu-mobile h-builder-menu-toggle-icon h-mobile-header-inline" id="masthead" itemid="#masthead"		>
			<div id="h-desktop-header" data-toggle-type="dropdown">
		<div class="h-above-header-wrap">
		<div class="h-above-header-bar h-above-header site-header-focus-item" data-section="section-above-header-builder">
						<div class="site-above-header-wrap h-builder-grid-row-container site-header-focus-item h-container" data-section="section-above-header-builder">
				<div class="h-builder-grid-row h-grid-center-col-layout-only h-flex h-grid-center-col-layout">
																		<div class="site-header-above-section-center site-header-section h-flex h-grid-section-center">
										<div class="h-builder-layout-element h-flex site-header-focus-item h-header-html-1" data-section="section-hb-html-1">
			<div class="h-header-html inner-link-style-"><div class="h-builder-html-element"><div class="topbar">🎁 <b>FREE</b> Salon &amp; Clinic Growth Audit (worth AED 2,000) — only <b>5 slots</b> left this week · <a href="/get-a-free-audit/">Claim yours →</a></div>
</div></div>		</div>
									</div>
																		</div>
					</div>
								</div>
			</div>
		<div class="h-main-header-wrap main-header-bar-wrap">
		<div class="h-primary-header-bar h-primary-header main-header-bar site-header-focus-item" data-section="section-primary-header-builder">
						<div class="site-primary-header-wrap h-builder-grid-row-container site-header-focus-item h-container" data-section="section-primary-header-builder">
				<div class="h-builder-grid-row h-builder-grid-row-has-sides h-grid-center-col-layout">
											<div class="site-header-primary-section-left site-header-section h-flex site-header-section-left">
									<div class="h-builder-layout-element h-flex site-header-focus-item" data-section="title_tagline">
							<div
				class="site-branding h-site-identity"				>
					<span class="site-logo-img"><a href="/" class="logo-link" rel="home" @if($on('/')) aria-current="page" @endif><img width="205" height="48" src="/images/2026/07/cropped-glowth-logo-transparent-205x48.png" class="logo" alt="Glowth" decoding="async" srcset="/images/2026/07/cropped-glowth-logo-transparent-205x48.png 205w, /images/2026/07/cropped-glowth-logo-transparent-410x95.png 410w" sizes="(max-width: 205px) 100vw, 205px" /></a></span><div class="h-site-title-wrap">
						<span class="site-title">
				<a href="/" rel="home" >
					Glowth
				</a>
			</span>

				</div>				</div>

					</div>
												<div class="site-header-primary-section-left-center site-header-section h-flex h-grid-left-center-section">
																			</div>
															</div>
																		<div class="site-header-primary-section-center site-header-section h-flex h-grid-section-center">
										<div class="h-builder-menu-1 h-builder-menu h-flex h-builder-menu-1-focus-item h-builder-layout-element site-header-focus-item" data-section="section-hb-menu-1">
			<div class="h-main-header-bar-alignment"><div class="main-header-bar-navigation"><nav class="site-navigation h-flex-grow-1 navigation-accessibility site-header-focus-item" id="primary-site-navigation-desktop" aria-label="Primary Site Navigation"><div class="main-navigation h-inline-flex"><ul id="h-hf-menu-1" class="main-header-menu h-menu-shadow h-nav-menu h-flex submenu-with-border stack-on-mobile"><li @class(['nav-item', 'nav-current' => $on('/')])><a href="/" class="nav-link" @if($on('/')) aria-current="page" @endif>Home</a></li>
<li @class(['nav-item', 'nav-item-has-children', 'nav-current' => $on('/services/'), 'nav-current-ancestor' => $on('/seo-for-beauty-salons-clinics/', '/websites-landing-pages-for-beauty/', '/social-media-management-for-beauty/', '/google-ads-for-beauty/', '/instagram-facebook-ads-for-beauty/', '/ai-appointment-booking-bot/', '/ecommerce-for-beauty/', '/reviews-reputation/')])><a aria-expanded="false" href="/services/" class="nav-link" @if($on('/services/')) aria-current="page" @endif>Services<span role="button" class="dropdown-menu-toggle h-header-navigation-arrow" tabindex="0" aria-expanded="false" aria-label="Menu Toggle" aria-haspopup="true"><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span></span></a><button class="h-menu-toggle" aria-expanded="false" aria-label="Toggle Menu"><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span></button>
<ul class="nav-sub">
	<li @class(['nav-item', 'nav-current' => $on('/seo-for-beauty-salons-clinics/')])><a href="/seo-for-beauty-salons-clinics/" class="nav-link" @if($on('/seo-for-beauty-salons-clinics/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Search Engine Optimization</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/websites-landing-pages-for-beauty/')])><a href="/websites-landing-pages-for-beauty/" class="nav-link" @if($on('/websites-landing-pages-for-beauty/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Websites &#038; Landing Pages</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/social-media-management-for-beauty/')])><a href="/social-media-management-for-beauty/" class="nav-link" @if($on('/social-media-management-for-beauty/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Social Media Management</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/google-ads-for-beauty/')])><a href="/google-ads-for-beauty/" class="nav-link" @if($on('/google-ads-for-beauty/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Google Ads</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/instagram-facebook-ads-for-beauty/')])><a href="/instagram-facebook-ads-for-beauty/" class="nav-link" @if($on('/instagram-facebook-ads-for-beauty/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Meta Ads</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/ai-appointment-booking-bot/')])><a href="/ai-appointment-booking-bot/" class="nav-link" @if($on('/ai-appointment-booking-bot/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>AI Booking System</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/ecommerce-for-beauty/')])><a href="/ecommerce-for-beauty/" class="nav-link" @if($on('/ecommerce-for-beauty/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Ecommerce</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/reviews-reputation/')])><a href="/reviews-reputation/" class="nav-link" @if($on('/reviews-reputation/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Reviews &#038; Reputation</a></li>
</ul>
</li>
<li @class(['nav-item', 'nav-current' => $on('/industries/')])><a href="/industries/" class="nav-link" @if($on('/industries/')) aria-current="page" @endif>Industries</a></li>
<li @class(['nav-item', 'nav-current' => $on('/about-us/')])><a href="/about-us/" class="nav-link" @if($on('/about-us/')) aria-current="page" @endif>About Us</a></li>
<li @class(['nav-item', 'nav-item-has-children', 'nav-current-ancestor' => $on('/sharjah/', '/abu-dhabi/', '/dubai/')])><a aria-expanded="false" href="/" class="nav-link">Location<span role="button" class="dropdown-menu-toggle h-header-navigation-arrow" tabindex="0" aria-expanded="false" aria-label="Menu Toggle" aria-haspopup="true"><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span></span></a><button class="h-menu-toggle" aria-expanded="false" aria-label="Toggle Menu"><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span></button>
<ul class="nav-sub">
	<li @class(['nav-item', 'nav-current' => $on('/sharjah/')])><a href="/sharjah/" class="nav-link" @if($on('/sharjah/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Sharjah</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/abu-dhabi/')])><a href="/abu-dhabi/" class="nav-link" @if($on('/abu-dhabi/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Abu Dhabi</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/dubai/')])><a href="/dubai/" class="nav-link" @if($on('/dubai/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Dubai</a></li>
</ul>
</li>
<li @class(['nav-item', 'nav-item-terms', 'nav-current' => $on('/terms-of-service/')])><a href="/terms-of-service/" class="nav-link" @if($on('/terms-of-service/')) aria-current="page" @endif>Terms of Service</a></li>
<li @class(['nav-item', 'nav-current' => $on('/faq/')])><a href="/faq/" class="nav-link" @if($on('/faq/')) aria-current="page" @endif>FAQ</a></li>
<li @class(['nav-item', 'nav-current' => $on('/our-blog/')])><a href="/our-blog/" class="nav-link" @if($on('/our-blog/')) aria-current="page" @endif>Our Blog</a></li>
</ul></div></nav></div></div>		</div>
									</div>
																			<div class="site-header-primary-section-right site-header-section h-flex h-grid-right-section">
																	<div class="site-header-primary-section-right-center site-header-section h-flex h-grid-right-center-section">
																			</div>
											<div class="h-builder-layout-element h-flex site-header-focus-item h-header-button-1" data-section="section-hb-button-1">
			<div class="h-builder-button-wrap h-builder-button-size-"><a class="h-custom-button-link" href="/get-a-free-audit/" target="_self"  role="button" aria-label="Free Growth Audit" >
				<div class="h-custom-button">Free Growth Audit</div>
			</a><a class="nav-link" href="/contact-us/" target="_self" >Free Growth Audit</a></div>		</div>
									</div>
												</div>
					</div>
								</div>
			</div>
	</div>
<div id="h-mobile-header" class="h-mobile-header-wrap" data-type="dropdown">
		<div class="h-main-header-wrap main-header-bar-wrap" >
		<div class="h-primary-header-bar h-primary-header main-header-bar site-primary-header-wrap site-header-focus-item h-builder-grid-row-layout-default h-builder-grid-row-tablet-layout-default h-builder-grid-row-mobile-layout-default" data-section="section-primary-header-builder">
									<div class="h-builder-grid-row h-builder-grid-row-has-sides h-builder-grid-row-no-center">
													<div class="site-header-primary-section-left site-header-section h-flex site-header-section-left">
										<div class="h-builder-layout-element h-flex site-header-focus-item" data-section="title_tagline">
							<div
				class="site-branding h-site-identity"				>
					<span class="site-logo-img"><a href="/" class="logo-link" rel="home" @if($on('/')) aria-current="page" @endif><img width="205" height="48" src="/images/2026/07/cropped-glowth-logo-transparent-205x48.png" class="logo" alt="Glowth" decoding="async" srcset="/images/2026/07/cropped-glowth-logo-transparent-205x48.png 205w, /images/2026/07/cropped-glowth-logo-transparent-410x95.png 410w" sizes="(max-width: 205px) 100vw, 205px" /></a></span><div class="h-site-title-wrap">
						<span class="site-title">
				<a href="/" rel="home" >
					Glowth
				</a>
			</span>

				</div>				</div>

					</div>
									</div>
																									<div class="site-header-primary-section-right site-header-section h-flex h-grid-right-section">
										<div class="h-builder-layout-element h-flex site-header-focus-item" data-section="section-header-mobile-trigger">
						<div class="h-button-wrap">
				<button
					type="button"
					class="nav-toggle main-header-menu-toggle h-mobile-menu-trigger-minimal"
					aria-expanded="false"
					aria-label="Main menu toggle"
														>
					<span class="mobile-menu-toggle-icon">
						<span aria-hidden="true" class="h-svg-iconset h-inline-flex svg-baseline"><svg class='h-mobile-svg h-menu-svg' fill='currentColor' version='1.1' xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><path d='M3 13h18c0.552 0 1-0.448 1-1s-0.448-1-1-1h-18c-0.552 0-1 0.448-1 1s0.448 1 1 1zM3 7h18c0.552 0 1-0.448 1-1s-0.448-1-1-1h-18c-0.552 0-1 0.448-1 1s0.448 1 1 1zM3 19h18c0.552 0 1-0.448 1-1s-0.448-1-1-1h-18c-0.552 0-1 0.448-1 1s0.448 1 1 1z'></path></svg></span><span aria-hidden="true" class="h-svg-iconset h-inline-flex svg-baseline"><svg class='h-mobile-svg h-close-svg' fill='currentColor' version='1.1' xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><path d='M5.293 6.707l5.293 5.293-5.293 5.293c-0.391 0.391-0.391 1.024 0 1.414s1.024 0.391 1.414 0l5.293-5.293 5.293 5.293c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414l-5.293-5.293 5.293-5.293c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z'></path></svg></span>					</span>
									</button>
			</div>
					</div>
									</div>
											</div>
						</div>
	</div>
				<div class="h-mobile-header-content content-align-flex-start">
						<div class="h-builder-menu-mobile h-builder-menu h-builder-menu-mobile-focus-item h-builder-layout-element site-header-focus-item" data-section="section-header-mobile-menu">
			<div class="h-main-header-bar-alignment"><div class="main-header-bar-navigation"><nav class="site-navigation h-flex-grow-1 navigation-accessibility site-header-focus-item" id="h-mobile-site-navigation" aria-label="Site Navigation: Main Menu"><div class="main-navigation"><ul id="h-hf-mobile-menu" class="main-header-menu h-nav-menu h-flex submenu-with-border ha-menu-animation-fade stack-on-mobile"><li @class(['nav-item', 'nav-current' => $on('/')])><a href="/" class="nav-link" @if($on('/')) aria-current="page" @endif>Home</a></li>
<li @class(['nav-item', 'nav-item-has-children', 'nav-current' => $on('/services/'), 'nav-current-ancestor' => $on('/seo-for-beauty-salons-clinics/', '/websites-landing-pages-for-beauty/', '/social-media-management-for-beauty/', '/google-ads-for-beauty/', '/instagram-facebook-ads-for-beauty/', '/ai-appointment-booking-bot/', '/ecommerce-for-beauty/', '/reviews-reputation/')])><a aria-expanded="false" href="/services/" class="nav-link" @if($on('/services/')) aria-current="page" @endif>Services<span role="button" class="dropdown-menu-toggle h-header-navigation-arrow" tabindex="0" aria-expanded="false" aria-label="Menu Toggle" aria-haspopup="true"><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span></span></a><button class="h-menu-toggle" aria-expanded="false" aria-label="Toggle Menu"><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span></button>
<ul class="nav-sub">
	<li @class(['nav-item', 'nav-current' => $on('/seo-for-beauty-salons-clinics/')])><a href="/seo-for-beauty-salons-clinics/" class="nav-link" @if($on('/seo-for-beauty-salons-clinics/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Search Engine Optimization</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/websites-landing-pages-for-beauty/')])><a href="/websites-landing-pages-for-beauty/" class="nav-link" @if($on('/websites-landing-pages-for-beauty/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Websites &#038; Landing Pages</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/social-media-management-for-beauty/')])><a href="/social-media-management-for-beauty/" class="nav-link" @if($on('/social-media-management-for-beauty/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Social Media Management</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/google-ads-for-beauty/')])><a href="/google-ads-for-beauty/" class="nav-link" @if($on('/google-ads-for-beauty/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Google Ads</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/instagram-facebook-ads-for-beauty/')])><a href="/instagram-facebook-ads-for-beauty/" class="nav-link" @if($on('/instagram-facebook-ads-for-beauty/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Meta Ads</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/ai-appointment-booking-bot/')])><a href="/ai-appointment-booking-bot/" class="nav-link" @if($on('/ai-appointment-booking-bot/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>AI Booking System</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/ecommerce-for-beauty/')])><a href="/ecommerce-for-beauty/" class="nav-link" @if($on('/ecommerce-for-beauty/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Ecommerce</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/reviews-reputation/')])><a href="/reviews-reputation/" class="nav-link" @if($on('/reviews-reputation/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Reviews &#038; Reputation</a></li>
</ul>
</li>
<li @class(['nav-item', 'nav-current' => $on('/about-us/')])><a href="/about-us/" class="nav-link" @if($on('/about-us/')) aria-current="page" @endif>About Us</a></li>
<li @class(['nav-item', 'nav-item-has-children', 'nav-current-ancestor' => $on('/sharjah/', '/abu-dhabi/', '/dubai/')])><a aria-expanded="false" href="/" class="nav-link">Location<span role="button" class="dropdown-menu-toggle h-header-navigation-arrow" tabindex="0" aria-expanded="false" aria-label="Menu Toggle" aria-haspopup="true"><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span></span></a><button class="h-menu-toggle" aria-expanded="false" aria-label="Toggle Menu"><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span></button>
<ul class="nav-sub">
	<li @class(['nav-item', 'nav-current' => $on('/sharjah/')])><a href="/sharjah/" class="nav-link" @if($on('/sharjah/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Sharjah</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/abu-dhabi/')])><a href="/abu-dhabi/" class="nav-link" @if($on('/abu-dhabi/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Abu Dhabi</a></li>
	<li @class(['nav-item', 'nav-current' => $on('/dubai/')])><a href="/dubai/" class="nav-link" @if($on('/dubai/')) aria-current="page" @endif><span class="h-icon icon-arrow"><svg class="h-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewbox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
                <path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z" />
                </svg></span>Dubai</a></li>
</ul>
</li>
<li @class(['nav-item', 'nav-item-terms', 'nav-current' => $on('/terms-of-service/')])><a href="/terms-of-service/" class="nav-link" @if($on('/terms-of-service/')) aria-current="page" @endif>Terms of Service</a></li>
<li @class(['nav-item', 'nav-current' => $on('/faq/')])><a href="/faq/" class="nav-link" @if($on('/faq/')) aria-current="page" @endif>FAQ</a></li>
<li @class(['nav-item', 'nav-current' => $on('/our-blog/')])><a href="/our-blog/" class="nav-link" @if($on('/our-blog/')) aria-current="page" @endif>Our Blog</a></li>
</ul></div></nav></div></div>		</div>
					</div>
			</div>
		</header>
