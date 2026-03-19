<?php

use App\Models\Block;
use App\Models\Site;
use App\Models\User;

it('user has one site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);
    expect($user->site->id)->toBe($site->id);
});

it('site belongs to user', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);
    expect($site->user->id)->toBe($user->id);
});

it('site has many blocks', function () {
    $site = Site::factory()->create();
    Block::factory()->count(3)->create(['site_id' => $site->id]);
    expect($site->blocks)->toHaveCount(3);
});

it('block belongs to site', function () {
    $block = Block::factory()->create();
    expect($block->site)->toBeInstanceOf(Site::class);
});

it('props column casts to array', function () {
    $block = Block::factory()->create(['props' => ['title' => 'Hello']]);
    expect($block->fresh()->props)->toBeArray();
    expect($block->fresh()->props['title'])->toBe('Hello');
});
