<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BlockSchemaRegistry;
use Illuminate\Http\JsonResponse;

class BlockSchemaController extends Controller
{
    public function index(BlockSchemaRegistry $registry): JsonResponse
    {
        return response()->json($registry->all());
    }
}
