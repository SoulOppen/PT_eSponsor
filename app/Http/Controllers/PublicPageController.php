<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class PublicPageController extends Controller
{
    public function show(string $slug): View
    {
        $site = Site::query()->where('slug', $slug)->first();

        if ($site === null) {
            abort(404);
        }

        $cached = Cache::remember(
            static::cacheKey($slug),
            3600,
            fn () => static::loadPublicPayload($slug)
        );

        return view('public.site', [
            'site' => $cached['site'],
            'blocks' => $cached['blocks'],
        ]);
    }

    public static function cacheKey(string $slug): string
    {
        return "public_site:{$slug}";
    }

    /**
     * @return array{site: Site, blocks: \Illuminate\Support\Collection<int, \App\Models\Block>}
     */
    public static function loadPublicPayload(string $slug): array
    {
        $site = Site::query()->where('slug', $slug)->firstOrFail();

        $blocks = $site->blocks()
            ->where('is_active', true)
            ->where('is_published', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return ['site' => $site, 'blocks' => $blocks];
    }
}
