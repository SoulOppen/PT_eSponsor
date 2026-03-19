<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SitePublishController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $site = $request->user()->site;
        abort_if($site === null, 404);

        $site->blocks()->where('is_active', true)->update(['is_published' => true]);

        $site->update(['published_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
