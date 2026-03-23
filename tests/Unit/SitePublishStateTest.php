<?php

use App\Models\Block;
use App\Models\User;
use App\Support\SitePublishState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('snapshot changes when block order changes', function () {
    $user = User::factory()->hasSite()->create();
    $b1 = Block::factory()->create(['site_id' => $user->site->id, 'order' => 0, 'is_active' => true, 'is_published' => true]);
    $b2 = Block::factory()->create(['site_id' => $user->site->id, 'order' => 1, 'is_active' => true, 'is_published' => true]);

    $before = SitePublishState::snapshot($user->site->fresh());

    $b1->update(['order' => 1]);
    $b2->update(['order' => 0]);

    $after = SitePublishState::snapshot($user->site->fresh());

    expect($before)->not->toBe($after);
});

it('restorePublishedBlocksFromSnapshot applies order from JSON', function () {
    $user = User::factory()->hasSite()->create();
    $b1 = Block::factory()->create([
        'site_id' => $user->site->id,
        'order' => 1,
        'is_active' => true,
        'is_published' => true,
    ]);
    $b2 = Block::factory()->create([
        'site_id' => $user->site->id,
        'order' => 0,
        'is_active' => true,
        'is_published' => true,
    ]);

    $json = json_encode([
        ['id' => $b1->id, 'order' => 0, 'p' => [], 'pub' => true],
        ['id' => $b2->id, 'order' => 1, 'p' => [], 'pub' => true],
    ], JSON_THROW_ON_ERROR);

    SitePublishState::restorePublishedBlocksFromSnapshot($user->site->fresh(), $json);

    expect($b1->fresh()->order)->toBe(0);
    expect($b2->fresh()->order)->toBe(1);
});

it('restorePublishedBlocksFromSnapshot restores props from snapshot p', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create([
        'site_id' => $user->site->id,
        'type' => 'text',
        'props' => ['content' => 'Editado'],
        'order' => 0,
        'is_active' => true,
        'is_published' => false,
    ]);

    $json = json_encode([
        [
            'id' => $block->id,
            'order' => 0,
            'p' => ['content' => 'Como en publicación'],
            'pub' => true,
        ],
    ], JSON_THROW_ON_ERROR);

    SitePublishState::restorePublishedBlocksFromSnapshot($user->site->fresh(), $json);

    $fresh = $block->fresh();
    expect($fresh->props['content'])->toBe('Como en publicación');
    expect($fresh->is_published)->toBeTrue();
    expect($fresh->order)->toBe(0);
});

it('hasPendingChanges is false when stored snapshot matches', function () {
    $user = User::factory()->hasSite()->create();
    Block::factory()->create(['site_id' => $user->site->id, 'is_active' => true, 'is_published' => true]);

    $site = $user->site->fresh();
    $snap = SitePublishState::snapshot($site);
    $site->update(['published_blocks_snapshot' => $snap, 'published_at' => now()]);

    expect(SitePublishState::hasPendingChanges($site->fresh()))->toBeFalse();
});

it('restorePublishedBlocksFromSnapshot recreates a deleted block when snapshot includes t', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create([
        'site_id' => $user->site->id,
        'type' => 'text',
        'props' => ['content' => 'Hola'],
        'order' => 0,
        'is_active' => true,
        'is_published' => true,
    ]);
    $id = $block->id;
    $json = json_encode([
        [
            'id' => $id,
            'order' => 0,
            't' => 'text',
            'p' => ['content' => 'Hola'],
            'pub' => true,
        ],
    ], JSON_THROW_ON_ERROR);

    $site = $user->site->fresh();
    $site->update(['published_blocks_snapshot' => $json]);

    $block->delete();

    expect(Block::find($id))->toBeNull();

    SitePublishState::restorePublishedBlocksFromSnapshot($site->fresh(), $json);

    $recreated = Block::find($id);
    expect($recreated)->not->toBeNull();
    expect($recreated->site_id)->toBe($site->id);
    expect($recreated->type)->toBe('text');
    expect($recreated->props['content'])->toBe('Hola');
    expect($recreated->order)->toBe(0);
    expect($recreated->is_published)->toBeTrue();
    expect($recreated->is_active)->toBeTrue();
});
