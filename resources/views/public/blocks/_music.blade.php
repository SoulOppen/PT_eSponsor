@php
    $platform = strtolower((string) ($block->props['platform'] ?? ''));
    $url = $block->props['url'] ?? '';
    $iframeSrc = $url;
    if ($platform === 'spotify' && $url !== '') {
        if (preg_match('#track/([a-zA-Z0-9]+)#', $url, $m)) {
            $iframeSrc = 'https://open.spotify.com/embed/track/'.$m[1];
        } elseif (! str_contains($url, 'embed')) {
            $iframeSrc = str_replace('open.spotify.com/track/', 'open.spotify.com/embed/track/', $url);
        }
    }
    if ($platform === 'bandcamp' && $url !== '') {
        $iframeSrc = 'https://bandcamp.com/EmbeddedPlayer/size=large/bgcol=ffffff/linkcol=0687f5/track=0/'.rawurlencode($url);
    }
@endphp
@if($url !== '')
    <div class="block-music" style="margin: 1rem 0;">
        <iframe src="{{ $iframeSrc }}" width="100%" height="380" style="border:0;max-width:100%" allow="encrypted-media"></iframe>
    </div>
@endif
