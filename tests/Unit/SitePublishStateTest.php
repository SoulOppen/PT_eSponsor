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

it('restorePublishedBlockOrdersFromSnapshot applies order from JSON', function () {
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

    SitePublishState::restorePublishedBlockOrdersFromSnapshot($user->site->fresh(), $json);

    expect($b1->fresh()->order)->toBe(0);
    expect($b2->fresh()->order)->toBe(1);
});

it('hasPendingChanges is false when stored snapshot matches', function () {
    $user = User::factory()->hasSite()->create();
    Block::factory()->create(['site_id' => $user->site->id, 'is_active' => true, 'is_published' => true]);

    $site = $user->site->fresh();
    $snap = SitePublishState::snapshot($site);
    $site->update(['published_blocks_snapshot' => $snap, 'published_at' => now()]);

    expect(SitePublishState::hasPendingChanges($site->fresh()))->toBeFalse();
});
