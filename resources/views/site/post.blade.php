{{-- A blog post --}}
@extends('layouts.site')

@section('content')
<div class="h-container">

	<div id="primary" class="content-area primary">

					<main id="main" class="site-main">

<article
class="entry-{{ $post->entryId() }} post type-post st-publish format-standard{{ $post->featured_image ? ' has-post-thumbnail' : '' }} entry{{ $post->categories->map(fn ($c) => ' category-'.$c->slug)->implode('') }} h-article-single" id="entry-{{ $post->entryId() }}">

<div class="h-post-format- single-layout-1">

		<header class="entry-header">

			<h1 class="entry-title">{{ $post->title }}</h1><div class="entry-meta">By @include('partials.post-byline')</div>

		</header>

	<div class="entry-content clear"
	>{!! $post->content !!}</div>
</div>

</article>

@if ($previous || $next)
<nav class="navigation post-navigation" aria-label="Posts">
				<div class="nav-links">@if ($previous)<div class="nav-previous"><a title="{{ $previous->title }}" href="{{ $previous->url() }}" rel="prev"><span class="h-post-nav" aria-hidden="true"><span aria-hidden="true" class="h-svg-iconset h-inline-flex svg-baseline"><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'><path d='M134.059 296H436c6.627 0 12-5.373 12-12v-56c0-6.627-5.373-12-12-12H134.059v-46.059c0-21.382-25.851-32.09-40.971-16.971L7.029 239.029c-9.373 9.373-9.373 24.569 0 33.941l86.059 86.059c15.119 15.119 40.971 4.411 40.971-16.971V296z'></path></svg></span> Previous</span> <p> {{ $previous->title }} </p></a></div>@endif{{ '' }}@if ($next)<div class="nav-next"><a title="{{ $next->title }}" href="{{ $next->url() }}" rel="next"><span class="h-post-nav" aria-hidden="true">Next <span aria-hidden="true" class="h-svg-iconset h-inline-flex svg-baseline"><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'><path d='M313.941 216H12c-6.627 0-12 5.373-12 12v56c0 6.627 5.373 12 12 12h301.941v46.059c0 21.382 25.851 32.09 40.971 16.971l86.059-86.059c9.373-9.373 9.373-24.569 0-33.941l-86.059-86.059c-15.119-15.119-40.971-4.411-40.971 16.971V216z'></path></svg></span></span> <p> {{ $next->title }} </p></a></div>@endif</div>
		</nav>
@endif

			</main>

	</div>

	</div>
@endsection
