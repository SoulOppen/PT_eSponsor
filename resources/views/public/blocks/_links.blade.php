@php
    $p = $block->props ?? [];
    $items = $p['items'] ?? [];
    // Datos antiguos: un solo enlace en props.title + props.url
    if (count($items) === 0 && ! empty($p['url'])) {
        $items = [['label' => $p['title'] ?? 'Enlace', 'url' => $p['url']]];
    }
@endphp
@if(! empty($p['title']) || count($items) > 0)
    <section
        class="block-links"
        style="--block-links-bg: {{ $p['color'] ?? '#1e293b' }}; --block-links-color: {{ $p['text_color'] ?? '#ffffff' }};"
    >
        @if(! empty($p['title']))
            <h2 class="block-links__title">{{ $p['title'] }}</h2>
        @endif
        <nav class="block-links__nav">
            @foreach($items as $item)
                @if(! empty($item['url']))
                    <a href="{{ $item['url'] }}" class="block-links__link">{{ $item['label'] ?? $item['url'] }}</a>
                @endif
            @endforeach
        </nav>
    </section>
@endif
