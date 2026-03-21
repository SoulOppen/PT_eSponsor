<?php

use App\Models\User;

it('public page title is person name hyphen platform name', function () {
    User::factory()
        ->state(['name' => 'Ada Lovelace'])
        ->hasSite(['slug' => 'grace', 'name' => 'Mi página pública'])
        ->create();
    $expected = '<title>Ada Lovelace - '.e(config('app.name')).'</title>';
    $this->get('/@grace')->assertSee($expected, false);
});

it('public page og:title matches document title', function () {
    User::factory()
        ->state(['name' => 'Ada Lovelace'])
        ->hasSite(['slug' => 'grace', 'name' => 'Mi página pública'])
        ->create();
    $this->get('/@grace')
        ->assertSee('property="og:title"', false)
        ->assertSee('Ada Lovelace - '.config('app.name'), false);
});

it('public page uses bio as meta and og:description when bio is set', function () {
    User::factory()->hasSite(['slug' => 'grace', 'bio' => 'Pioneer of computing'])->create();
    $this->get('/@grace')
        ->assertSee('name="description"', false)
        ->assertSee('Pioneer of computing', false)
        ->assertSee('property="og:description"', false);
});

it('public page meta description reflects bio after JSON profile patch', function () {
    $user = User::factory()->hasSite(['slug' => 'grace', 'bio' => null])->create();
    $this->actingAs($user)
        ->patchJson('/api/profile', [
            'name' => $user->name,
            'slug' => 'grace',
            'bio' => 'Texto de bio para meta description',
            'remove_avatar' => false,
        ])
        ->assertOk();

    $this->get('/@grace')
        ->assertSee('name="description"', false)
        ->assertSee('Texto de bio para meta description', false)
        ->assertSee('property="og:description"', false);
});

it('public page uses default description when bio is empty', function () {
    User::factory()->hasSite(['slug' => 'grace', 'bio' => null])->create();
    $this->get('/@grace')
        ->assertSee('name="description"', false)
        ->assertSee(e(config('seo.default_description')), false);
});

it('public page has og:image when avatar is set', function () {
    User::factory()->hasSite(['slug' => 'grace', 'avatar_url' => 'https://cdn.test/avatar.jpg'])->create();
    $this->get('/@grace')->assertSee('https://cdn.test/avatar.jpg');
});
