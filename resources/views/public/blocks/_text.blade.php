@php($p = $block->props ?? [])
@if(! empty($p['content']))
    @php($align = in_array(($p['align'] ?? ''), ['left', 'center', 'right'], true) ? $p['align'] : 'left')
    <p class="block-text block-text--{{ $align }}">{{ $p['content'] }}</p>
@endif
