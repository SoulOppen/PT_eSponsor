<?php

namespace App\Http\Controllers;

use App\Services\BlockSchemaRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, BlockSchemaRegistry $registry): Response
    {
        $user = $request->user();
        $site = $user->site;
        abort_if($site === null, 404);

        $blocks = $site->blocks()->orderBy('order')->orderBy('id')->get();

        return Inertia::render('Dashboard/Index', [
            'site' => $site,
            'blocks' => $blocks,
            'blockSchemas' => $registry->all(),
        ]);
    }

    public function settings(Request $request): Response
    {
        $site = $request->user()->site;
        abort_if($site === null, 404);

        return Inertia::render('Dashboard/Settings', [
            'site' => $site,
        ]);
    }
}
