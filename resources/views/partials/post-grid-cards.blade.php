{{-- Latest posts in the blog page's grid (a posts_grid section) --}}
@foreach ($posts as $post)
@php($image = $post->image())
<article class="x-post x-grid-item entry-{{ $post->entryId() }} post type-post st-publish format-standard{{ $image ? ' has-post-thumbnail' : '' }} entry{{ $post->categories->map(fn ($c) => ' category-'.$c->slug)->implode('') }}" role="listitem">
@if ($image)
				<a class="x-post__thumbnail__link" href="{{ $post->url() }}" tabindex="-1" >
			<div class="x-post__thumbnail"><img {!! $loop->first ? 'fetchpriority="high"' : 'loading="lazy"' !!} decoding="async" width="{{ $image->width }}" height="{{ $image->height }}" src="{{ $image->path }}" class="p-image-{{ $image->id }}" alt="{{ $image->alt }}" srcset="{{ $image->srcset() }}" sizes="(max-width: {{ $image->width }}px) 100vw, {{ $image->width }}px" /></div>
		</a>
@endif
				<div class="x-post__text">
				<h3 class="x-post__title">
			<a href="{{ $post->url() }}" >
				{{ $post->title }}			</a>
		</h3>

		<a class="x-post__read-more" href="{{ $post->url() }}" aria-label="Read more about {{ $post->title }}" tabindex="-1" >
			Read More »		</a>

				</div>
				</article>
@endforeach
