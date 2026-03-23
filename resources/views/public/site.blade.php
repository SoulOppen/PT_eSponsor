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
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, sans-serif; margin: 0; line-height: 1.5; color: #111; }
        main { margin: 0 auto; padding: 1.25rem; max-width: 40rem; }
        img { max-width: 100%; height: auto; display: block; }
        iframe { max-width: 100%; border: 0; }
        @if($isDraftPreview && $canEditDraft)
        #draft-edit-toolbar.draft-toolbar-unified {
            display: flex; flex-direction: column; gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, #fffbeb 0%, #eef2ff 50%, #e0e7ff 100%);
            color: #312e81;
            border-bottom: 1px solid #c7d2fe;
            font-size: 0.9rem;
        }
        #draft-edit-toolbar .draft-toolbar-unified__row {
            display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem 1rem;
        }
        #draft-edit-toolbar .draft-toolbar-unified__preview {
            flex: 1 1 14rem;
            color: #78350f;
            line-height: 1.4;
        }
        #draft-edit-toolbar .draft-toolbar-unified__preview strong { color: #92400e; }
        #draft-edit-toolbar .draft-toolbar-unified__muted { color: #a16207; font-size: 0.85rem; }
        #draft-edit-toolbar .draft-toolbar-unified__public-link {
            color: #b45309; font-weight: 600; margin-left: 0.35rem; white-space: nowrap;
        }
        #draft-edit-toolbar .draft-toolbar-unified__actions {
            display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem 0.75rem;
        }
        #draft-edit-toolbar .draft-toolbar-unified__drag-hint {
            margin: 0; font-size: 0.8rem; color: #4b5563; padding-top: 0.15rem;
            border-top: 1px dashed #c7d2fe;
        }
        #draft-edit-toolbar button {
            cursor: pointer; border: 0; border-radius: 0.375rem; padding: 0.5rem 1rem;
            font-weight: 600; font-size: 0.875rem; background: #4f46e5; color: #fff;
        }
        #draft-edit-toolbar button:disabled { opacity: 0.6; cursor: not-allowed; }
        #draft-edit-toolbar a.editor-link { color: #4338ca; font-weight: 500; text-decoration: underline; }
        .draft-block-wrap {
            display: flex; gap: 0.5rem; align-items: flex-start;
            margin-bottom: 1.5rem; border-radius: 0.375rem; transition: box-shadow 0.15s;
        }
        .draft-block-wrap__handle {
            flex-shrink: 0; width: 2.25rem; min-height: 2.25rem;
            display: flex; align-items: center; justify-content: center;
            margin-top: 0.15rem;
            cursor: grab; color: #6366f1; background: #eef2ff;
            border: 1px solid #c7d2fe; border-radius: 0.375rem;
            touch-action: none;
        }
        .draft-block-wrap__handle:active { cursor: grabbing; }
        .draft-block-wrap__handle svg { display: block; pointer-events: none; }
        .draft-block-wrap__content {
            flex: 1; min-width: 0;
        }
        .draft-block-wrap.draft-block--dragging { opacity: 0.65; }
        .draft-block-wrap.draft-block--over { box-shadow: 0 0 0 2px #6366f1; }
        @endif
    </style>
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
                <span id="draft-edit-message" style="flex: 1; min-width: 8rem;" aria-live="polite"></span>
            </div>
        </div>
        <p class="draft-toolbar-unified__drag-hint">
            Reordena con el icono <strong>⋮⋮</strong> a la izquierda de cada bloque (válido también para música y vídeos embebidos).
        </p>
    </div>
@elseif($isDraftPreview)
    <div
        role="status"
        style="background: #fef3c7; color: #92400e; padding: 0.65rem 1.25rem; font-size: 0.9rem; border-bottom: 1px solid #fcd34d;"
    >
        <strong>Vista previa (borrador)</strong>
        — Solo usuarios conectados. Incluye bloques activos aún no publicados.
        <a href="{{ url('/@'.$site->slug) }}" style="color: #b45309; margin-left: 0.5rem;">Ver página pública ↗</a>
        <a
            href="{{ route('dashboard') }}"
            style="display: inline-flex; align-items: center; margin-left: 0.5rem; color: #4338ca; font-weight: 600; text-decoration: underline;"
            title="Editor de tu página"
        >Ir a tu editor</a>
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
                <article style="margin-bottom: 1.5rem;">
                    @include($partial, ['block' => $block])
                </article>
            @endif
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
    @if($isDraftPreview && $canEditDraft)
        </div>
    @endif
</main>
</body>
</html>
