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
			<div class="h-header-html inner-link-style-"><div class="h-builder-html-element"><div class="topbar">{!! setting('topbar_html') !!}</div>
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
			<div class="h-main-header-bar-alignment"><div class="main-header-bar-navigation"><nav class="site-navigation h-flex-grow-1 navigation-accessibility site-header-focus-item" id="primary-site-navigation-desktop" aria-label="Primary Site Navigation"><div class="main-navigation h-inline-flex"><ul id="h-hf-menu-1" class="main-header-menu h-menu-shadow h-nav-menu h-flex submenu-with-border stack-on-mobile">
@include('partials.nav-menu', ['items' => setting('nav_menu', []), 'mobile' => false])
</ul></div></nav></div></div>		</div>
									</div>
																			<div class="site-header-primary-section-right site-header-section h-flex h-grid-right-section">
																	<div class="site-header-primary-section-right-center site-header-section h-flex h-grid-right-center-section">
																			</div>
											<div class="h-builder-layout-element h-flex site-header-focus-item h-header-button-1" data-section="section-hb-button-1">
			<div class="h-builder-button-wrap h-builder-button-size-">@php($cta = setting('header_cta', ['label' => '', 'url' => '/']))
			<a class="h-custom-button-link" href="{{ $cta['url'] }}" target="_self"  role="button" aria-label="{{ $cta['label'] }}" >
				<div class="h-custom-button">{{ $cta['label'] }}</div>
			</a><a class="nav-link" href="{{ $cta['url'] }}" target="_self" >{{ $cta['label'] }}</a></div>		</div>
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
			<div class="h-main-header-bar-alignment"><div class="main-header-bar-navigation"><nav class="site-navigation h-flex-grow-1 navigation-accessibility site-header-focus-item" id="h-mobile-site-navigation" aria-label="Site Navigation: Main Menu"><div class="main-navigation"><ul id="h-hf-mobile-menu" class="main-header-menu h-nav-menu h-flex submenu-with-border ha-menu-animation-fade stack-on-mobile">
@include('partials.nav-menu', ['items' => setting('nav_menu', []), 'mobile' => true])
</ul></div></nav></div></div>		</div>
					</div>
			</div>
		</header>
