@php($links = $block->props['links'] ?? [])
@if(count($links) > 0)
    <nav style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin: 1rem 0;" aria-label="Redes sociales">
        @foreach($links as $item)
            @if(! empty($item['url']))
                <a href="{{ $item['url'] }}" rel="noopener noreferrer">{{ $item['label'] ?? $item['url'] }}</a>
            @endif
        @endforeach
    </nav>
@endif
