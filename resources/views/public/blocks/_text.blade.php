@php($p = $block->props ?? [])
@if(! empty($p['content']))
    <p @if(! empty($p['align'])) style="text-align: {{ $p['align'] }}" @endif>{{ $p['content'] }}</p>
@endif
