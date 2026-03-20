<?php

use App\Models\Block;
use App\Models\User;

it('owner can update their block per policy', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $user->site->id]);

    expect($user->can('update', $block))->toBeTrue();
});

it('other user cannot update block per policy', function () {
    $owner = User::factory()->hasSite()->create();
    $other = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $owner->site->id]);

    expect($other->can('update', $block))->toBeFalse();
});
