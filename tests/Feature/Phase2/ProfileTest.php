<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('authenticated user can update their profile', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->patch('/api/profile', ['name' => 'New Name', 'bio' => 'Bio', 'slug' => 'new-slug'])
        ->assertOk();
    expect($user->site->fresh()->slug)->toBe('new-slug');
});

it('slug must be unique across sites', function () {
    User::factory()->hasSite(['slug' => 'taken'])->create();
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->patch('/api/profile', ['slug' => 'taken'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
});

it('slug rejects invalid characters', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->patch('/api/profile', ['slug' => 'my slug!'])
        ->assertUnprocessable();
});

it('user cannot overwrite another users site', function () {
    $victim = User::factory()->hasSite(['slug' => 'victim-slug'])->create();
    $attacker = User::factory()->hasSite()->create();
    $this->actingAs($attacker)
        ->patch('/api/profile', ['slug' => 'new-for-attacker'])
        ->assertOk();
    expect($victim->site->fresh()->slug)->toBe('victim-slug');
});

it('user can upload an avatar image', function () {
    Storage::fake('public');
    $user = User::factory()->hasSite()->create();
    $jpeg = file_get_contents(base_path('tests/Fixtures/1x1.jpg'));
    $file = UploadedFile::fake()->createWithContent('avatar.jpg', $jpeg);
    $this->actingAs($user)->patch('/api/profile', ['avatar' => $file])->assertOk();
    Storage::disk('public')->assertExists('avatars/'.$file->hashName());
    expect($user->site->fresh()->avatar_url)->toContain('avatars/');
});

it('rejects non-image avatar uploads', function () {
    $user = User::factory()->hasSite()->create();
    $file = UploadedFile::fake()->create('doc.pdf', 100);
    $this->actingAs($user)
        ->patch('/api/profile', ['avatar' => $file])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('avatar');
});

it('avatar file size is capped at 2 MB', function () {
    $user = User::factory()->hasSite()->create();
    $jpeg = file_get_contents(base_path('tests/Fixtures/1x1.jpg'));
    $oversized = str_repeat($jpeg, 11000);
    $file = UploadedFile::fake()->createWithContent('big.jpg', $oversized);
    $this->actingAs($user)
        ->patch('/api/profile', ['avatar' => $file])
        ->assertUnprocessable();
});

it('remove_avatar clears avatar_url and deletes stored file', function () {
    Storage::fake('public');
    $user = User::factory()->hasSite()->create();
    Storage::disk('public')->put('avatars/existing.jpg', 'dummy');
    $user->site->update(['avatar_url' => '/storage/avatars/existing.jpg']);

    $this->actingAs($user)
        ->patch('/api/profile', ['remove_avatar' => true])
        ->assertOk();

    expect($user->site->fresh()->avatar_url)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/existing.jpg');
});
