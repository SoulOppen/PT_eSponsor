<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'avatar' => ['sometimes', 'file', 'image', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
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

        if (($validated['remove_avatar'] ?? false) === true && ! $request->hasFile('avatar')) {
            $this->deleteStoredAvatarIfPresent((string) ($site->avatar_url ?? ''));
            $site->update(['avatar_url' => null]);
        }

        if ($request->hasFile('avatar')) {
            $this->deleteStoredAvatarIfPresent((string) ($site->avatar_url ?? ''));
            $path = $request->file('avatar')->store('avatars', 'public');
            $site->update([
                'avatar_url' => Storage::disk('public')->url($path),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    private function deleteStoredAvatarIfPresent(string $avatarUrl): void
    {
        if ($avatarUrl === '') {
            return;
        }

        $path = parse_url($avatarUrl, PHP_URL_PATH) ?: '';
        if (is_string($path) && str_starts_with($path, '/storage/')) {
            $relative = ltrim(substr($path, strlen('/storage/')), '/');
            if ($relative !== '') {
                Storage::disk('public')->delete($relative);
            }
            return;
        }

        if (str_starts_with($avatarUrl, 'avatars/')) {
            Storage::disk('public')->delete($avatarUrl);
        }
    }
}
