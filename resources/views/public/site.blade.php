<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $site->name }}</title>
    <meta property="og:title" content="{{ $site->name }}">
    @if($site->bio)
        <meta name="description" content="{{ $site->bio }}">
    @endif
    @if($site->avatar_url)
        <meta property="og:image" content="{{ $site->avatar_url }}">
    @endif
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, sans-serif; margin: 0; line-height: 1.5; color: #111; }
        main { margin: 0 auto; padding: 1.25rem; max-width: 40rem; }
        img { max-width: 100%; height: auto; display: block; }
        iframe { max-width: 100%; border: 0; }
    </style>
</head>
<body>
<main>
    <header style="margin-bottom: 1.5rem;">
        <h1 style="margin: 0 0 0.5rem; font-size: 1.75rem;">{{ $site->name }}</h1>
        @if($site->bio)
            <p style="margin: 0; color: #444;">{{ $site->bio }}</p>
        @endif
    </header>

    @forelse($blocks as $block)
        @php
            $partial = 'public.blocks._' . $block->type;
        @endphp
        @if(view()->exists($partial))
            <article style="margin-bottom: 1.5rem;">
                @include($partial, ['block' => $block])
            </article>
        @else
            {{-- Tipo sin plantilla Blade (p. ej. migración futura) --}}
        @endif
    @empty
        <p style="color: #666;">Aún no hay bloques publicados en esta página.</p>
    @endforelse
</main>
</body>
</html>
