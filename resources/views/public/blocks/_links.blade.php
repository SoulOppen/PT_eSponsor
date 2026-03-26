@php
    $p = $block->props ?? [];
    $rawItems = $p['items'] ?? [];
    if (is_string($rawItems)) {
        $decoded = json_decode($rawItems, true);
        $rawItems = is_array($decoded) ? $decoded : [];
    }
    $items = [];
    foreach ((is_iterable($rawItems) ? $rawItems : []) as $item) {
        $entry = is_array($item) ? $item : (is_object($item) ? (array) $item : []);
        $url = trim((string) ($entry['url'] ?? ''));
        if ($url === '') {
            continue;
        }
        $items[] = [
            'label' => $entry['label'] ?? $url,
            'href' => $url,
        ];
    }
    // Datos antiguos: un solo enlace en props.title + props.url
    if (count($items) === 0 && ! empty($p['url'])) {
        $legacyUrl = trim((string) $p['url']);
        $items = [['label' => $p['title'] ?? 'Enlace', 'href' => $legacyUrl]];
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
                <a href="{{ $item['href'] }}" class="block-links__link">{{ $item['label'] ?? $item['href'] }}</a>
            @endforeach
        </nav>
    </section>
@endif
