<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicPageController;
use App\Support\SitePublishState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SitePublishController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $site = $request->user()->site;
        abort_if($site === null, 404);

        $site->blocks()->where('is_active', true)->update(['is_published' => true]);

        $site->refresh();

        $snapshot = SitePublishState::snapshot($site);

        $site->update([
            'published_at' => now(),
            'published_blocks_snapshot' => $snapshot,
        ]);

        Cache::forget(PublicPageController::cacheKey($site->slug));

        return response()->json([
            'ok' => true,
            'published_blocks_snapshot' => $snapshot,
        ]);
    }
}
