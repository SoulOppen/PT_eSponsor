<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class PublicPageController extends Controller
{
    public function show(string $slug): View
    {
        $payload = static::loadPublicPayload($slug);

        return view('public.site', [
            'site' => $payload['site'],
            'blocks' => $payload['blocks'],
            'canEditDraft' => false,
            'canPublishDraft' => false,
        ]);
    }

    public static function cacheKey(string $slug): string
    {
        return "public_site:{$slug}";
    }

    /**
     * @return array{site: Site, blocks: Collection<int, Block>}
     */
    public static function loadPublicPayload(string $slug): array
    {
        $site = Site::query()->where('slug', $slug)->with('user')->firstOrFail();

        $blocks = $site->blocks()
            ->where('is_active', true)
            ->where('is_published', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return ['site' => $site, 'blocks' => $blocks];
    }
}
