@php($links = $block->props['links'] ?? [])
@if(count($links) > 0)
    @php($networkLabels = ['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'x' => 'X'])
    <nav class="block-social" aria-label="Redes sociales">
        @foreach($links as $item)
            @if(! empty($item['url']))
                @php($label = $item['label'] ?? $item['url'])
                @if(($item['network'] ?? null) === 'otra' && ! empty($item['custom_network']))
                    @php($label = $item['custom_network'])
                @elseif(! empty($item['network']) && isset($networkLabels[$item['network']]))
                    @php($label = $networkLabels[$item['network']])
                @endif
                <a href="{{ $item['url'] }}" rel="noopener noreferrer">{{ $label }}</a>
            @endif
        @endforeach
    </nav>
@endif
