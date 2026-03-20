<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'site_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('sites', 'slug'),
            ],
            'bio' => ['sometimes', 'nullable', 'string'],
            'avatar' => ['sometimes', 'nullable', 'file', 'image', 'max:2048'],
        ]);

        $user = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $rawSlug = trim((string) ($validated['slug'] ?? ''));
            if ($rawSlug === '') {
                $rawSlug = Str::slug((string) ($validated['name'] ?? ''));
            }

            $slug = $this->resolveUniqueSlug($rawSlug, (string) ($validated['name'] ?? 'user'));

            $siteName = trim((string) ($validated['site_name'] ?? ''));
            if ($siteName === '') {
                $siteName = (string) $validated['name'];
            }

            $avatarUrl = null;
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $avatarUrl = Storage::disk('public')->url($path);
            }

            Site::create([
                'user_id' => $user->id,
                'name' => Str::limit($siteName, 100, ''),
                'slug' => $slug,
                'bio' => $validated['bio'] ?? null,
                'avatar_url' => $avatarUrl,
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function resolveUniqueSlug(string $slugInput, string $fallbackName): string
    {
        $baseSlug = Str::slug($slugInput);
        if ($baseSlug === '') {
            $baseSlug = Str::slug($fallbackName);
        }
        if ($baseSlug === '') {
            $baseSlug = 'user';
        }

        $baseSlug = substr($baseSlug, 0, 60);
        $slug = $baseSlug;
        $i = 1;
        while (Site::where('slug', $slug)->exists()) {
            $suffix = '-'.$i;
            $slug = substr($baseSlug, 0, max(1, 60 - strlen($suffix))).$suffix;
            $i++;
        }

        return $slug;
    }
}
