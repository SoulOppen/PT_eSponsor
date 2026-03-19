<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $site = $user->site;

        abort_if($site === null, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string'],
            'slug' => [
                'sometimes',
                'string',
                'max:60',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('sites', 'slug')->ignore($site->id),
            ],
        ]);

        if (array_key_exists('name', $validated)) {
            $user->update(['name' => $validated['name']]);
            $site->update([
                'name' => Str::limit($validated['name'], 100, ''),
            ]);
        }

        if (array_key_exists('bio', $validated)) {
            $site->update(['bio' => $validated['bio']]);
        }

        if (array_key_exists('slug', $validated)) {
            $site->update(['slug' => $validated['slug']]);
        }

        return response()->json(['ok' => true]);
    }
}
