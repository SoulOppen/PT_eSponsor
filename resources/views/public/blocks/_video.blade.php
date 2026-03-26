@php
    $url = $block->props['url'] ?? '';
    $embed = $url;
    if (preg_match('/[?&]v=([^&]+)/', $url, $m)) {
        $embed = 'https://www.youtube.com/embed/'.$m[1];
    } elseif (preg_match('#youtu\.be/([^?]+)#', $url, $m)) {
        $embed = 'https://www.youtube.com/embed/'.$m[1];
    }
    $isTikTok = $url !== '' && str_contains($url, 'tiktok.com');
@endphp
@if($url !== '')
    <div class="block-video">
        @if($isTikTok)
            <iframe src="{{ $url }}" title="Video" width="560" height="480"></iframe>
        @else
            <iframe src="{{ $embed }}" title="Video" width="560" height="480"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"></iframe>
        @endif
    </div>
@endif
