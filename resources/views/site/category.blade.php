{{-- A category archive: its posts, newest first, PER_PAGE to a page --}}
@extends('layouts.site')

@section('content')
<div class="h-container">

	<div id="primary" class="content-area primary h-grid-3 h-blog-layout-4-grid">

				<section class="h-archive-description">
			<h1 class="page-title h-archive-title">{{ $category->name }}</h1>@if ($category->description)<p>{{ $category->description }}</p>@endif
		</section>

					<main id="main" class="site-main">
				<div class="h-row">@foreach ($posts as $post)@include('partials.post-card', ['first' => $loop->first])
@endforeach
</div>			</main>

@if ($posts->lastPage() > 1)
		<div class='h-pagination'><nav class="navigation pagination" aria-label="Post pagination">
				<div class="nav-links">@if ($posts->currentPage() > 1)<a class="prev page-numbers" href="{{ $category->url($posts->currentPage() - 1) }}"><span class="h-left-arrow" aria-hidden="true">&larr;</span> Previous</a>
@endif
@for ($n = 1; $n <= $posts->lastPage(); $n++)
@if ($n === $posts->currentPage())<span aria-current="page" class="page-numbers current">{{ $n }}</span>
@else<a class="page-numbers" href="{{ $category->url($n) }}">{{ $n }}</a>
@endif
@endfor
@if ($posts->hasMorePages())<a class="next page-numbers" href="{{ $category->url($posts->currentPage() + 1) }}">Next <span class="h-right-arrow" aria-hidden="true">&rarr;</span></a>@endif</div>
		</nav></div>
@endif

	</div>

	</div>
@endsection
