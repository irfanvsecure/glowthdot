{{-- A post in a category archive --}}
@php($image = $post->image())
@php($sized = $image?->sized(1024))
<article
class="entry-{{ $post->entryId() }} post type-post st-publish format-standard{{ $image ? ' has-post-thumbnail' : '' }} entry{{ $post->categories->map(fn ($c) => ' category-'.$c->slug)->implode('') }} h-grid-common-col h-full-width h-article-post remove-featured-img-padding" id="entry-{{ $post->entryId() }}">
		<div class="h-post-format- {{ $image ? '' : 'h-no-thumb ' }}blog-layout-4 h-article-inner">
	<div class="post-content h-grid-common-col" >
		<div class="h-blog-featured-section post-thumb h-blog-single-element">@if ($image)<div class="post-thumb-img-content post-thumb"><a href="{{ $post->url() }}" aria-label="Read: {{ $post->title }}" ><img {!! $first ? 'fetchpriority="high"' : 'loading="lazy"' !!} width="{{ $sized['width'] }}" height="{{ $sized['height'] }}" src="{{ $sized['path'] }}" class="p-post-image" alt="{{ $image->alt }}" decoding="async" srcset="{{ $image->srcset($sized['path']) }}" sizes="(max-width: {{ $sized['width'] }}px) 100vw, {{ $sized['width'] }}px" /></a></div>@endif</div>@if ($post->categories->isNotEmpty())<span class="h-blog-single-element h-taxonomy-container cat-links default">{!! $post->categories->map(fn ($c) => '<a href="'.e($c->url()).'" rel="category tag">'.e($c->name).'</a>')->implode(', ') !!}</span>@endif<h2 class="entry-title h-blog-single-element"><a href="{{ $post->url() }}" rel="bookmark">{{ $post->title }}</a></h2>		<header class="entry-header h-blog-single-element h-blog-meta-container">
			<div class="entry-meta">@include('partials.post-byline')</div>		</header>
					<div class="h-excerpt-container h-blog-single-element">
				<p>{{ $post->excerpt }}</p>
			</div>
				<div class="entry-content clear"
			>
					</div>
	</div>
</div>
	</article>
