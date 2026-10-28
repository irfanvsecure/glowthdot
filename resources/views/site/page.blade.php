{{-- A page: its visible sections, inside the page's wrapper markup. Each directive and echo sits on its
     own line: PHP drops the newline after a tag, so sections join back byte for byte. --}}
@extends('layouts.site')

@section('content')
{!! $page->wrapper_open !!}
@foreach ($sections as $section)
@if ($section->type === 'posts_grid')
{!! str_replace(\App\Models\PageSection::POSTS_MARKER, view('partials.post-grid-cards', ['posts' => $latestPosts($section->content['count'] ?? 6)])->render(), $section->html()) !!}
@else
{!! $section->html() !!}
@endif
@endforeach
{!! $page->wrapper_close !!}
@endsection
