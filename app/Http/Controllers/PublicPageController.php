<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Contracts\View\View;

class PublicPageController extends Controller
{
    public function show(string $slug): View
    {
        $payload = static::loadPublicPayload($slug);

        return view('public.site', [
            'site' => $payload['site'],
            'blocks' => $payload['blocks'],
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
