<?php

use App\Models\Block;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

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

it('inactive blocks are not rendered on public page', function () {
    $user = User::factory()->hasSite(['slug' => 'carol'])->create();
    Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text',
        'props' => ['content' => 'Hidden'], 'is_active' => false, 'is_published' => true,
    ]);
    $this->get('/@carol')->assertDontSee('Hidden');
});

it('public page is cached after first load', function () {
    Cache::spy();
    User::factory()->hasSite(['slug' => 'dave'])->create();
    $this->get('/@dave');
    Cache::shouldHaveReceived('remember')
        ->with(\Mockery::pattern('/dave/'), \Mockery::any(), \Mockery::any())
        ->once();
});

it('cache is invalidated after publishing', function () {
    Cache::spy();
    $user = User::factory()->hasSite(['slug' => 'eve'])->create();
    $this->actingAs($user)->postJson('/api/site/publish');
    Cache::shouldHaveReceived('forget')
        ->with(\Mockery::pattern('/eve/'))
        ->once();
});

it('public page does not load the vue dashboard bundle', function () {
    User::factory()->hasSite(['slug' => 'frank'])->create();
    $this->get('/@frank')->assertDontSee('app.js');
});
