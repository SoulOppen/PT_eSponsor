<?php

use App\Models\User;

it('public page has title meta with site name', function () {
    User::factory()->hasSite(['slug' => 'grace', 'name' => 'Grace Hopper'])->create();
    $this->get('/@grace')->assertSee('<title>Grace Hopper</title>', false);
});

it('public page has og:title', function () {
    User::factory()->hasSite(['slug' => 'grace', 'name' => 'Grace Hopper'])->create();
    $this->get('/@grace')
        ->assertSee('og:title', false)
        ->assertSee('Grace Hopper');
});

it('public page has meta description from bio', function () {
    User::factory()->hasSite(['slug' => 'grace', 'bio' => 'Pioneer of computing'])->create();
    $this->get('/@grace')->assertSee('Pioneer of computing');
});

it('public page has og:image when avatar is set', function () {
    User::factory()->hasSite(['slug' => 'grace', 'avatar_url' => 'https://cdn.test/avatar.jpg'])->create();
    $this->get('/@grace')->assertSee('https://cdn.test/avatar.jpg');
});
