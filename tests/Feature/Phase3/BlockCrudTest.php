<?php

use App\Models\Block;
use App\Models\User;

it('authenticated user can create a block', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->postJson('/api/blocks', ['type' => 'text', 'props' => ['content' => 'Hello']])
        ->assertCreated()
        ->assertJsonPath('data.type', 'text');
});

it('block is associated with the users site', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->postJson('/api/blocks', ['type' => 'text', 'props' => ['content' => 'Hi']]);
    expect($user->site->blocks)->toHaveCount(1);
});

it('creating a block with invalid props returns 422', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->postJson('/api/blocks', ['type' => 'text', 'props' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('props.content');
});

it('creating a block with unknown type returns 422', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->postJson('/api/blocks', ['type' => 'unknown', 'props' => []])
        ->assertUnprocessable();
});

it('user can list only their own blocks', function () {
    $user = User::factory()->hasSite()->create();
    $other = User::factory()->hasSite()->create();
    Block::factory()->count(3)->create(['site_id' => $other->site->id]);
    Block::factory()->count(2)->create(['site_id' => $user->site->id]);
    $this->actingAs($user)
        ->getJson('/api/blocks')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('user can update props of their own block', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text', 'props' => ['content' => 'Old'],
    ]);
    $this->actingAs($user)
        ->putJson("/api/blocks/{$block->id}", ['props' => ['content' => 'New']])
        ->assertOk();
    expect($block->fresh()->props['content'])->toBe('New');
});

it('user cannot update a block from another site', function () {
    $owner = User::factory()->hasSite()->create();
    $block = Block::factory()->create([
        'site_id' => $owner->site->id, 'type' => 'text', 'props' => ['content' => 'x'],
    ]);
    $other = User::factory()->hasSite()->create();
    $this->actingAs($other)
        ->putJson("/api/blocks/{$block->id}", ['props' => ['content' => 'hacked']])
        ->assertForbidden();
});

it('user can delete their own block', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $user->site->id]);
    $this->actingAs($user)
        ->deleteJson("/api/blocks/{$block->id}")
        ->assertNoContent();
    expect(Block::find($block->id))->toBeNull();
});

it('user can duplicate a block', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text', 'props' => ['content' => 'Original'],
    ]);
    $this->actingAs($user)
        ->postJson("/api/blocks/{$block->id}/duplicate")
        ->assertCreated();
    expect($user->site->blocks)->toHaveCount(2);
});

it('toggle flips is_active', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $user->site->id, 'is_active' => true]);
    $this->actingAs($user)->patchJson("/api/blocks/{$block->id}/toggle")->assertOk();
    expect($block->fresh()->is_active)->toBeFalse();
});
