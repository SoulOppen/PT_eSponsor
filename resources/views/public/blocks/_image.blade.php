@php($p = $block->props ?? [])
@if(! empty($p['url']))
    <figure>
        <img src="{{ $p['url'] }}" alt="{{ $p['alt'] ?? '' }}" loading="lazy">
    </figure>
@endif
