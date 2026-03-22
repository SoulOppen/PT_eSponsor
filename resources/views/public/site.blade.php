<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $rawAvatar = $site->avatar_url;
        $avatarUrl = null;
        if (is_string($rawAvatar) && $rawAvatar !== '') {
            if (str_starts_with($rawAvatar, 'http://') || str_starts_with($rawAvatar, 'https://') || str_starts_with($rawAvatar, '/')) {
                $avatarUrl = $rawAvatar;
            } elseif (str_starts_with($rawAvatar, 'avatars/')) {
                $avatarUrl = '/storage/' . $rawAvatar;
            }
        }
        $personName = $site->user?->name ?? $site->name;
        $isDraftPreview = ! empty($isDraftPreview ?? false);
        $documentTitle = $personName.' - '.config('app.name').($isDraftPreview ? ' — Borrador' : '');
        $bioPlain = trim((string) ($site->bio ?? ''));
        $metaDescription = $bioPlain !== ''
            ? \Illuminate\Support\Str::limit(strip_tags($bioPlain), 320, '')
            : config('seo.default_description');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if($isDraftPreview)
        <meta name="robots" content="noindex, nofollow">
    @endif
    <title>{{ $documentTitle }}</title>
    <meta property="og:title" content="{{ $documentTitle }}">
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    @if($avatarUrl)
        <meta property="og:image" content="{{ $avatarUrl }}">
    @endif
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, sans-serif; margin: 0; line-height: 1.5; color: #111; }
        main { margin: 0 auto; padding: 1.25rem; max-width: 40rem; }
        img { max-width: 100%; height: auto; display: block; }
        iframe { max-width: 100%; border: 0; }
    </style>
</head>
<body>
@if($isDraftPreview)
    <div
        role="status"
        style="background: #fef3c7; color: #92400e; padding: 0.65rem 1.25rem; font-size: 0.9rem; border-bottom: 1px solid #fcd34d;"
    >
        <strong>Vista previa (borrador)</strong>
        — Solo usuarios conectados. Incluye bloques activos aún no publicados.
        <a href="{{ url('/@'.$site->slug) }}" style="color: #b45309; margin-left: 0.5rem;">Ver página pública ↗</a>
    </div>
@endif
<main>
    <header style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
        @if($avatarUrl)
            <img
                src="{{ $avatarUrl }}"
                alt="Avatar de {{ $site->name }}"
                style="width: 5.5rem; height: 5.5rem; border-radius: 9999px; object-fit: cover; flex-shrink: 0;"
            >
        @endif
        <div>
            <h1 style="margin: 0 0 0.5rem; font-size: 1.75rem;">{{ $site->name }}</h1>
            @if($site->bio)
                <p style="margin: 0; color: #444;">{{ $site->bio }}</p>
            @endif
        </div>
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
        <p style="color: #666;">
            @if($isDraftPreview)
                Aún no hay bloques activos en esta vista previa.
            @else
                Aún no hay bloques publicados en esta página.
            @endif
        </p>
    @endforelse
</main>
</body>
</html>
