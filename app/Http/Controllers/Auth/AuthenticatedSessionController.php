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
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $default = route('dashboard', absolute: false);

        // Guardar destino antes de regenerar la sesión (si no, puede perderse url.intended).
        $intended = $request->session()->pull('url.intended', $default);

        // Respaldo: ?redirect=/ruta (misma app) si no hubo sesión previa.
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

        return redirect()->to($intended);
    }

    /**
     * Evita redirecciones abiertas: solo rutas relativas de esta app o URLs bajo APP_URL.
     */
    private function isSafeRedirectTarget(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $base = rtrim((string) config('app.url'), '/');

        return str_starts_with($url, $base.'/') || $url === $base;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
