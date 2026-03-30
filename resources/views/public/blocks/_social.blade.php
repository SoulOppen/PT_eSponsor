@php
    $props = is_array($block->props ?? null) ? $block->props : [];
    $rawLinks = $props['links'] ?? ($props['items'] ?? []);
    if (is_string($rawLinks)) {
        $decoded = json_decode($rawLinks, true);
        $rawLinks = is_array($decoded) ? $decoded : [];
    }
    if (is_array($rawLinks)) {
        $links = $rawLinks;
    } elseif (is_iterable($rawLinks)) {
        $links = iterator_to_array($rawLinks);
    } else {
        $links = [];
    }
@endphp
@if(! empty($links))
    @php($networkLabels = ['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'x' => 'X'])
    <nav class="block-social" aria-label="Redes sociales">
        @foreach($links as $item)
            @php($entry = is_array($item) ? $item : (is_object($item) ? (array) $item : []))
            @if(! empty($entry['url']))
                @php
                    $href = trim((string) $entry['url']);
                    if ($href !== '' && ! preg_match('#^https?://#i', $href) && ! preg_match('#^[a-z][a-z0-9+.-]*:#i', $href)) {
                        if (str_starts_with($href, '//')) {
                            $href = 'https:'.$href;
                        } elseif (! str_starts_with($href, '/')) {
                            $href = 'https://'.$href;
                        }
                    }
                @endphp
                @php($label = $entry['label'] ?? $entry['url'])
                @php($network = $entry['network'] ?? null)
                @if(($entry['network'] ?? null) === 'otra' && ! empty($entry['custom_network']))
                    @php($label = $entry['custom_network'])
                @elseif(! empty($entry['network']) && isset($networkLabels[$entry['network']]))
                    @php($label = $networkLabels[$entry['network']])
                @endif
                <a
                    href="{{ $href }}"
                    class="block-social__link"
                    data-network="{{ is_string($network) && $network !== '' ? $network : 'other' }}"
                    target="_blank"
                    rel="noopener noreferrer nofollow"
                >
                    {{ $label }}
                </a>
            @endif
        @endforeach
    </nav>
@endif
