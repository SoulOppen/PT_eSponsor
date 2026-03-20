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
        style="background-color: {{ $p['color'] ?? '#1e293b' }}; color: {{ $p['text_color'] ?? '#ffffff' }}; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;"
    >
        @if(! empty($p['title']))
            <h2 style="margin-top: 0;">{{ $p['title'] }}</h2>
        @endif
        <nav style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            @foreach($items as $item)
                @if(! empty($item['url']))
                    <a href="{{ $item['url'] }}" style="color: inherit;">{{ $item['label'] ?? $item['url'] }}</a>
                @endif
            @endforeach
        </nav>
    </section>
@endif
