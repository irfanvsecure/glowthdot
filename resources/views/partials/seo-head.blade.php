{{-- The page's own head tags: seo_meta and schema_blocks --}}
@if ($seo?->title)
<title>{{ $seo->title }}</title>
@endif
@if ($seo?->description)
<meta name="description" content="{{ $seo->description }}" />
@endif
@foreach (['canonical', 'prev', 'next'] as $rel)
@if ($seo?->$rel)
<link rel="{{ $rel }}" href="{{ $seo->$rel }}" />
@endif
@endforeach
@foreach (['og_type' => 'og:type', 'og_title' => 'og:title', 'og_description' => 'og:description', 'og_url' => 'og:url', 'og_image' => 'og:image', 'og_image_width' => 'og:image:width', 'og_image_height' => 'og:image:height', 'og_image_type' => 'og:image:type', 'published_time' => 'article:published_time', 'modified_time' => 'article:modified_time'] as $field => $property)
@if ($seo?->$field)
<meta property="{{ $property }}" content="{{ $seo->$field }}" />
@endif
@endforeach
@if ($seo?->author)
<meta name="author" content="{{ $seo->author }}" />
@endif
@foreach ($schemas as $schema)
<script type="application/ld+json"@if ($schema->element_id) id="{{ $schema->element_id }}"@endif>{!! $schema->json !!}</script>
@endforeach
