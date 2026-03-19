<?php

use App\Models\Block;
use App\Models\User;

it('user can reorder their blocks', function () {
    $user = User::factory()->hasSite()->create();
    $first = Block::factory()->create(['site_id' => $user->site->id, 'order' => 0]);
    $second = Block::factory()->create(['site_id' => $user->site->id, 'order' => 1]);

    $this->actingAs($user)
        ->postJson('/api/blocks/reorder', [
            'blocks' => [
                ['id' => $first->id, 'order' => 1],
                ['id' => $second->id, 'order' => 0],
            ],
        ])
        ->assertOk();

    expect($first->fresh()->order)->toBe(1);
    expect($second->fresh()->order)->toBe(0);
});

it('cannot reorder blocks from another site', function () {
    $owner = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $owner->site->id, 'order' => 0]);
    $other = User::factory()->hasSite()->create();
    $this->actingAs($other)
        ->postJson('/api/blocks/reorder', ['blocks' => [['id' => $block->id, 'order' => 99]]])
        ->assertForbidden();
});
