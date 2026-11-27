{{-- One menu of the header: settings nav_menu. $mobile leaves out the items marked "mobile": false. --}}
@php
$arrow = <<<'SVG'
<svg class="h-arrow-svg" viewBox="0 0 26 16.043" aria-hidden="true"><use href="#gl-arrow"/></svg>
SVG;
@endphp
@foreach ($items as $item)
@continue($mobile && ($item['mobile'] ?? true) === false)
@php($kids = $item['children'] ?? [])
@if ($kids)
<li @class(['nav-item', $item['class'] ?? null, 'nav-item-has-children', 'nav-current' => $item['url'] !== '/' && $on($item['url']), 'nav-current-ancestor' => $on(...array_column($kids, 'url'))])><a aria-expanded="false" href="{{ $item['url'] }}" class="nav-link" @if($item['url'] !== '/' && $on($item['url'])) aria-current="page" @endif>{{ $item['label'] }}<span role="button" class="dropdown-menu-toggle h-header-navigation-arrow" tabindex="0" aria-expanded="false" aria-label="Menu Toggle" aria-haspopup="true"><span class="h-icon icon-arrow">{!! $arrow !!}</span></span></a><button class="h-menu-toggle" aria-expanded="false" aria-label="Toggle Menu"><span class="h-icon icon-arrow">{!! $arrow !!}</span></button>
<ul class="nav-sub">
@foreach ($kids as $kid)
	<li @class(['nav-item', 'nav-current' => $on($kid['url'])])><a href="{{ $kid['url'] }}" class="nav-link" @if($on($kid['url'])) aria-current="page" @endif><span class="h-icon icon-arrow">{!! $arrow !!}</span>{{ $kid['label'] }}</a></li>
@endforeach
</ul>
</li>
@else
<li @class(['nav-item', $item['class'] ?? null, 'nav-current' => $on($item['url'])])><a href="{{ $item['url'] }}" class="nav-link" @if($on($item['url'])) aria-current="page" @endif>{{ $item['label'] }}</a></li>
@endif
@endforeach
