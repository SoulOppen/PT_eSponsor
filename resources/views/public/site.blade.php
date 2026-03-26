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
        $canEditDraft = ! empty($canEditDraft ?? false);
        $canPublishDraft = ! empty($canPublishDraft ?? false);
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
    @vite(['resources/css/app.css'])
    @if($isDraftPreview && $canEditDraft)
        @vite(['resources/js/draft-preview.js'])
    @endif
</head>
<body>
@if($isDraftPreview && $canEditDraft)
    <div
        id="draft-edit-toolbar"
        class="draft-toolbar-unified"
        data-public-url="{{ url('/@'.$site->slug) }}"
        data-can-publish="{{ $canPublishDraft ? '1' : '0' }}"
        role="region"
        aria-label="Vista previa borrador y acciones"
    >
        <div class="draft-toolbar-unified__row">
            <div class="draft-toolbar-unified__preview">
                <strong>Vista previa (borrador)</strong>
                <span class="draft-toolbar-unified__muted"> — Solo usuarios conectados. Incluye bloques activos aún no publicados.</span>
                <a href="{{ url('/@'.$site->slug) }}" class="draft-toolbar-unified__public-link">Ver página pública ↗</a>
            </div>
            <div class="draft-toolbar-unified__actions">
                <button
                    type="button"
                    id="draft-edit-publish"
                    @if(! $canPublishDraft) disabled title="Nada pendiente: coincide con la última publicación (orden y contenido de bloques activos)." @endif
                >Publicar</button>
                <a class="editor-link" href="{{ route('dashboard') }}" title="Editor de tu página">Ir a tu editor</a>
                <span id="draft-edit-message" class="draft-toolbar-unified__message" aria-live="polite"></span>
            </div>
        </div>
        <p class="draft-toolbar-unified__drag-hint">
            Reordena con el icono <strong>⋮⋮</strong> a la izquierda de cada bloque (válido también para música y vídeos embebidos).
        </p>
    </div>
@elseif($isDraftPreview)
    <div role="status" class="draft-banner">
        <strong>Vista previa (borrador)</strong>
        — Solo usuarios conectados. Incluye bloques activos aún no publicados.
        <a href="{{ url('/@'.$site->slug) }}" class="draft-banner__public-link">Ver página pública ↗</a>
        <a href="{{ route('dashboard') }}" class="draft-banner__editor-link" title="Editor de tu página">Ir a tu editor</a>
    </div>
@endif
<main>
    <header class="site-header">
        @if($avatarUrl)
            <img
                src="{{ $avatarUrl }}"
                alt="Avatar de {{ $site->name }}"
                class="site-avatar"
            >
        @endif
        <div>
            <h1 class="site-title">{{ $site->name }}</h1>
            @if($site->bio)
                <p class="site-bio">{{ $site->bio }}</p>
            @endif
        </div>
    </header>

    @if($isDraftPreview && $canEditDraft)
        <div id="draft-blocks">
    @endif
    @forelse($blocks as $block)
        @php
            $partial = 'public.blocks._' . $block->type;
        @endphp
        @if(view()->exists($partial))
            @if($isDraftPreview && $canEditDraft)
                <div class="draft-block-wrap" data-block-id="{{ $block->id }}">
                    <div
                        class="draft-block-wrap__handle"
                        draggable="true"
                        title="Arrastrar para reordenar"
                        aria-label="Arrastrar para reordenar el bloque"
                    >
                        {{-- Icono “agarre” (no usar el mismo SVG que iconos sociales dentro del bloque) --}}
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M9 5h2v2H9V5zm0 6h2v2H9v-2zm0 6h2v2H9v-2zM13 5h2v2h-2V5zm0 6h2v2h-2v-2zm0 6h2v2h-2v-2z"/>
                        </svg>
                    </div>
                    <article class="draft-block-wrap__content">
                        @include($partial, ['block' => $block])
                    </article>
                </div>
            @else
                <article class="public-block-article">
                    @include($partial, ['block' => $block])
                </article>
            @endif
        @else
            {{-- Tipo sin plantilla Blade (p. ej. migración futura) --}}
        @endif
    @empty
        <p class="empty-blocks">
            @if($isDraftPreview)
                Aún no hay bloques activos en esta vista previa.
            @else
                Aún no hay bloques publicados en esta página.
            @endif
        </p>
    @endforelse
    @if($isDraftPreview && $canEditDraft)
        </div>
    @endif
</main>
</body>
</html>
