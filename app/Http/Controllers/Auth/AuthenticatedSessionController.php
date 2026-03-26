<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        /*
         * Needs: no input; optional session status and route availability.
         * Does: renders the login page with reset-password capability flags.
         * Returns: an Inertia response for Auth/Login.
         */
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        /*
         * Needs: valid login credentials in LoginRequest and optional redirect query.
         * Does: authenticates user, resolves safe intended URL, and normalizes draft redirects.
         * Returns: a redirect response to the final in-app destination.
         */
        $request->authenticate();

        $default = route('dashboard', absolute: false);

        $intended = $request->session()->pull('url.intended', $default);

        $fromQuery = $request->string('redirect')->toString();
        if ($intended === $default && $fromQuery !== '') {
            $candidate = urldecode($fromQuery);
            if ($this->isSafeRedirectTarget($candidate)) {
                $intended = $candidate;
            }
        }

        $request->session()->regenerate();

        if (! $this->isSafeRedirectTarget($intended)) {
            $intended = $default;
        }

        if ($this->shouldUseDashboardForDraftIntended($intended)) {
            $intended = $default;
        }

        return redirect()->to($intended);
    }

    private function isSafeRedirectTarget(string $url): bool
    {
        /*
         * Needs: a candidate URL string.
         * Does: validates that the target is relative or under configured APP_URL.
         * Returns: true when redirect target is safe; otherwise false.
         */
        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $base = rtrim((string) config('app.url'), '/');

        return str_starts_with($url, $base.'/') || $url === $base;
    }

    private function shouldUseDashboardForDraftIntended(string $intended): bool
    {
        /*
         * Needs: an intended redirect URL.
         * Does: checks whether target path matches draft preview route pattern.
         * Returns: true when dashboard fallback should be used.
         */
        $path = $this->intendedPath($intended);
        return preg_match('#^/draft/@[a-z0-9\-]+$#', $path) === 1;
    }

    private function intendedPath(string $url): string
    {
        /*
         * Needs: a relative or absolute URL string.
         * Does: extracts normalized path component from the URL.
         * Returns: a path string, defaulting to '/' when unavailable.
         */
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    public function destroy(Request $request): RedirectResponse
    {
        /*
         * Needs: an authenticated web session request.
         * Does: logs out user, invalidates session, and regenerates CSRF token.
         * Returns: a redirect response to home route.
         */
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
