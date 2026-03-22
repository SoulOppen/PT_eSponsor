<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Contracts\View\View;

/**
 * Vista previa a pantalla completa de los bloques activos (incluye no publicados).
 * Requiere sesión; por ahora cualquier usuario autenticado puede abrir el borrador de cualquier slug.
 */
class DraftPageController extends Controller
{
    public function show(string $slug): View
    {
        $site = Site::query()->where('slug', $slug)->with('user')->firstOrFail();

        $blocks = $site->blocks()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $userId = auth()->id();

        $hasUnpublishedActive = $site->blocks()
            ->where('is_active', true)
            ->where('is_published', false)
            ->exists();

        $publishedAt = $site->published_at;
        $hasChangesSincePublish = $publishedAt !== null && $site->blocks()
            ->where('is_active', true)
            ->where('updated_at', '>', $publishedAt)
            ->exists();

        $canPublishDraft = $hasUnpublishedActive || $hasChangesSincePublish;

        return view('public.site', [
            'site' => $site,
            'blocks' => $blocks,
            'isDraftPreview' => true,
            'canEditDraft' => $userId !== null && (int) $userId === (int) $site->user_id,
            'canPublishDraft' => $canPublishDraft,
        ]);
    }
}
