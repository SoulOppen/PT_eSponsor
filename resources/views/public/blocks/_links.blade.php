@php($p = $block->props ?? [])
@if(! empty($p['title']))
    <section
        style="background-color: {{ $p['color'] ?? '#1e293b' }}; color: {{ $p['text_color'] ?? '#ffffff' }}; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;"
    >
        <h2 style="margin-top: 0;">{{ $p['title'] }}</h2>
        <nav style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            @foreach($p['items'] ?? [] as $item)
                @if(! empty($item['url']))
                    <a href="{{ $item['url'] }}" style="color: inherit;">{{ $item['label'] ?? $item['url'] }}</a>
                @endif
            @endforeach
        </nav>
    </section>
@endif
