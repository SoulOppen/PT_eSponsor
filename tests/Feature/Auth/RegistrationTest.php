<?php

namespace Tests\Feature\Auth;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'slug' => 'test-user',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('sites', [
            'slug' => 'test-user',
            'name' => 'Test User',
            'bio' => null,
        ]);
    }

    public function test_slug_must_be_unique_during_registration(): void
    {
        Site::factory()->create(['slug' => 'slug-usado']);

        $response = $this->post('/register', [
            'name' => 'Otro User',
            'slug' => 'slug-usado',
            'email' => 'otro@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('slug');
        $this->assertGuest();
    }

    public function test_registration_can_upload_avatar_and_store_public_url(): void
    {
        Storage::fake('public');
        $png1x1 = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlTH0QAAAAASUVORK5CYII=',
            true
        );

        $response = $this->post('/register', [
            'name' => 'Avatar User',
            'site_name' => 'Mi Sitio',
            'slug' => 'avatar-user',
            'bio' => 'Bio demo',
            'avatar' => UploadedFile::fake()->createWithContent('avatar.png', $png1x1 ?: 'png'),
            'email' => 'avatar@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $site = Site::query()->where('slug', 'avatar-user')->firstOrFail();
        $this->assertNotNull($site->avatar_url);
        $this->assertStringContainsString('/storage/avatars/', $site->avatar_url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', parse_url($site->avatar_url, PHP_URL_PATH)));
    }

    public function test_site_name_defaults_to_user_name_when_missing(): void
    {
        $this->post('/register', [
            'name' => 'Nombre De Usuario',
            'slug' => 'nombre-de-usuario',
            'email' => 'fallback@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('sites', [
            'slug' => 'nombre-de-usuario',
            'name' => 'Nombre De Usuario',
        ]);
    }
}
