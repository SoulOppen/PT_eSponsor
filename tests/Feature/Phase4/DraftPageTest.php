<?php

use App\Models\Block;
use App\Models\User;

it('draft preview redirects guests to login', function () {
    User::factory()->hasSite(['slug' => 'alice'])->create();
    $this->get('/draft/@alice')->assertRedirect();
});

it('authenticated user can open draft preview for any slug', function () {
    User::factory()->hasSite(['slug' => 'bob'])->create();
    $viewer = User::factory()->hasSite(['slug' => 'viewer'])->create();

    $this->actingAs($viewer)
        ->get('/draft/@bob')
        ->assertOk();
});

it('draft preview shows active unpublished blocks hidden from public page', function () {
    $owner = User::factory()->hasSite(['slug' => 'carol'])->create();
    Block::factory()->create([
        'site_id' => $owner->site->id,
        'type' => 'text',
        'props' => ['content' => 'Draft only'],
        'is_active' => true,
        'is_published' => false,
    ]);

    $this->get('/@carol')->assertDontSee('Draft only');

    $viewer = User::factory()->hasSite()->create();
    $this->actingAs($viewer)
        ->get('/draft/@carol')
        ->assertOk()
        ->assertSee('Draft only')
        ->assertSee('Vista previa (borrador)', false);
});

it('draft preview returns 404 for unknown slug', function () {
    $viewer = User::factory()->hasSite()->create();
    $this->actingAs($viewer)->get('/draft/@nobody')->assertNotFound();
});
