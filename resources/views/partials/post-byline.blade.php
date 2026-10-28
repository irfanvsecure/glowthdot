<span class="posted-by vcard author">			<a title="View all posts by {{ $post->author_name }}"
				href="/our-blog/" rel="author"
				class="url fn n"				>
				<span
				class="author-name"				>
				{{ $post->author_name }}			</span>
			</a>
		</span>

		 / <span class="posted-on"><span class="published"> {{ $post->published_at?->format('F j, Y') }} </span></span>