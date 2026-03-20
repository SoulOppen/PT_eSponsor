<?php

use App\Models\Block;
use App\Models\User;

it('public page returns 200 for a valid slug', function () {
    User::factory()->hasSite(['slug' => 'alice'])->create();
    $this->get('/@alice')->assertOk();
});

it('public page returns 404 for unknown slug', function () {
    $this->get('/@nobody')->assertNotFound();
});

it('public page shows site name and bio', function () {
    User::factory()->hasSite(['slug' => 'bob', 'name' => 'Bob Builder', 'bio' => 'I can fix it'])->create();
    $this->get('/@bob')->assertSee('Bob Builder')->assertSee('I can fix it');
});

it('public page does not render avatar image or og:image when avatar is missing', function () {
    User::factory()->hasSite(['slug' => 'no-avatar', 'avatar_url' => null])->create();

    $this->get('/@no-avatar')
        ->assertOk()
        ->assertDontSee('Avatar de')
        ->assertDontSee('property="og:image"', false);
});

it('inactive blocks are not rendered on public page', function () {
    $user = User::factory()->hasSite(['slug' => 'carol'])->create();
    Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text',
        'props' => ['content' => 'Hidden'], 'is_active' => false, 'is_published' => true,
    ]);
    $this->get('/@carol')->assertDontSee('Hidden');
});

it('public page reflects latest site avatar without caching', function () {
    $user = User::factory()->hasSite(['slug' => 'dave', 'avatar_url' => null])->create();
    $this->get('/@dave')->assertDontSee('Avatar de');

    $user->site->update(['avatar_url' => '/storage/avatars/new-avatar.png']);

    $this->get('/@dave')
        ->assertSee('Avatar de', false)
        ->assertSee('/storage/avatars/new-avatar.png');
});

it('public page does not load the vue dashboard bundle', function () {
    User::factory()->hasSite(['slug' => 'frank'])->create();
    $this->get('/@frank')->assertDontSee('app.js');
});
