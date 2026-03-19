<?php

use App\Models\Block;
use App\Models\User;

it('publishing marks all active blocks as is_published', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $user->site->id, 'is_active' => true, 'is_published' => false]);
    $this->actingAs($user)->postJson('/api/site/publish')->assertOk();
    expect($block->fresh()->is_published)->toBeTrue();
});

it('inactive blocks are not published', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $user->site->id, 'is_active' => false]);
    $this->actingAs($user)->postJson('/api/site/publish')->assertOk();
    expect($block->fresh()->is_published)->toBeFalse();
});

it('publishing sets published_at on the site', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)->postJson('/api/site/publish')->assertOk();
    expect($user->site->fresh()->published_at)->not->toBeNull();
});

it('unpublished content is not visible on public page', function () {
    $user = User::factory()->hasSite(['slug' => 'jane'])->create();
    Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text',
        'props' => ['content' => 'Draft content'], 'is_published' => false, 'is_active' => true,
    ]);
    $this->get('/@jane')->assertDontSee('Draft content');
});

it('published content is visible on public page', function () {
    $user = User::factory()->hasSite(['slug' => 'jane'])->create();
    Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text',
        'props' => ['content' => 'Published content'], 'is_published' => true, 'is_active' => true,
    ]);
    $this->get('/@jane')->assertSee('Published content');
});
