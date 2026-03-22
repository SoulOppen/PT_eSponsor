<?php

use App\Models\Block;
use App\Models\User;
use App\Support\SitePublishState;

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

it('publishing stores published_blocks_snapshot aligned with active blocks', function () {
    $user = User::factory()->hasSite()->create();
    Block::factory()->create(['site_id' => $user->site->id, 'is_active' => true, 'is_published' => false]);

    $this->actingAs($user)->postJson('/api/site/publish')->assertOk();

    $site = $user->site->fresh();
    expect($site->published_blocks_snapshot)->not->toBeNull()
        ->and($site->published_blocks_snapshot)->toBe(SitePublishState::snapshot($site));
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
