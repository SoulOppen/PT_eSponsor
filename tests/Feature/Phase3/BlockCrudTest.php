<?php

use App\Models\Block;
use App\Models\User;
use App\Support\SitePublishState;

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

it('user can delete all blocks on their site', function () {
    $user = User::factory()->hasSite()->create();
    Block::factory()->count(3)->create(['site_id' => $user->site->id]);
    $this->actingAs($user)
        ->deleteJson('/api/blocks/all')
        ->assertOk();
    expect($user->site->fresh()->blocks)->toHaveCount(0);
});

it('delete all keeps baseline snapshot so user can restore published blocks', function () {
    $user = User::factory()->hasSite()->create();
    $site = $user->site->fresh();

    $published = Block::factory()->create([
        'site_id' => $site->id,
        'type' => 'text',
        'props' => ['content' => 'Publicado'],
        'order' => 0,
        'is_active' => true,
        'is_published' => true,
    ]);

    $site->update([
        'published_at' => now(),
        'published_blocks_snapshot' => SitePublishState::snapshot($site->fresh()),
    ]);

    $publishedId = $published->id;

    $this->actingAs($user)->deleteJson('/api/blocks/all')->assertOk();
    expect($site->fresh()->blocks)->toHaveCount(0);

    $this->actingAs($user)->deleteJson('/api/blocks/unpublished')->assertOk();

    $restored = Block::find($publishedId);
    expect($restored)->not->toBeNull();
    expect($restored->props['content'])->toBe('Publicado');
    expect($restored->is_published)->toBeTrue();
});

it('user can delete only unpublished blocks', function () {
    $user = User::factory()->hasSite()->create();
    $siteId = $user->site->id;
    $draft = Block::factory()->create(['site_id' => $siteId, 'is_published' => false]);
    $live = Block::factory()->create(['site_id' => $siteId, 'is_published' => true]);

    $this->actingAs($user)
        ->deleteJson('/api/blocks/unpublished')
        ->assertOk();

    expect(Block::find($draft->id))->toBeNull();
    expect(Block::find($live->id))->not->toBeNull();
});

it('prune unpublished removes published blocks not listed in baseline snapshot', function () {
    $user = User::factory()->hasSite()->create();
    $a = Block::factory()->create([
        'site_id' => $user->site->id,
        'order' => 0,
        'is_active' => true,
        'is_published' => true,
    ]);
    $b = Block::factory()->create([
        'site_id' => $user->site->id,
        'order' => 1,
        'is_active' => true,
        'is_published' => true,
    ]);
    $site = $user->site->fresh();
    $site->update([
        'published_at' => now(),
        'published_blocks_snapshot' => SitePublishState::snapshot($site->fresh()),
    ]);
    $extra = Block::factory()->create([
        'site_id' => $user->site->id,
        'order' => 2,
        'is_active' => true,
        'is_published' => true,
    ]);

    $this->actingAs($user)->deleteJson('/api/blocks/unpublished')->assertOk();

    expect(Block::find($extra->id))->toBeNull();
    expect(Block::find($a->id))->not->toBeNull();
    expect(Block::find($b->id))->not->toBeNull();
});

it('prune unpublished with empty baseline snapshot deletes all blocks', function () {
    $user = User::factory()->hasSite()->create();
    Block::factory()->create([
        'site_id' => $user->site->id,
        'is_active' => true,
        'is_published' => true,
    ]);
    $user->site->update([
        'published_at' => now(),
        'published_blocks_snapshot' => '[]',
    ]);

    $this->actingAs($user)->deleteJson('/api/blocks/unpublished')->assertOk();

    expect($user->site->fresh()->blocks)->toHaveCount(0);
});

it('prune unpublished restores block props from baseline snapshot', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create([
        'site_id' => $user->site->id,
        'type' => 'text',
        'props' => ['content' => 'Texto publicado'],
        'order' => 0,
        'is_active' => true,
        'is_published' => true,
    ]);
    $site = $user->site->fresh();
    $site->update([
        'published_at' => now(),
        'published_blocks_snapshot' => SitePublishState::snapshot($site->fresh()),
    ]);

    $block->update(['props' => ['content' => 'Cambio local sin publicar']]);

    $this->actingAs($user)->deleteJson('/api/blocks/unpublished')->assertOk();

    expect($block->fresh()->props['content'])->toBe('Texto publicado');
});

it('prune unpublished recreates a published block deleted from DB using snapshot', function () {
    $user = User::factory()->hasSite()->create();
    $block = Block::factory()->create([
        'site_id' => $user->site->id,
        'type' => 'text',
        'props' => ['content' => 'Texto publicado'],
        'order' => 0,
        'is_active' => true,
        'is_published' => true,
    ]);
    $site = $user->site->fresh();
    $site->update([
        'published_at' => now(),
        'published_blocks_snapshot' => SitePublishState::snapshot($site->fresh()),
    ]);

    $blockId = $block->id;
    $block->delete();

    $this->actingAs($user)->deleteJson('/api/blocks/unpublished')->assertOk();

    $again = Block::find($blockId);
    expect($again)->not->toBeNull();
    expect($again->props['content'])->toBe('Texto publicado');
});

it('prune unpublished restores order of published blocks from snapshot', function () {
    $user = User::factory()->hasSite()->create();
    $b1 = Block::factory()->create([
        'site_id' => $user->site->id,
        'order' => 0,
        'is_active' => true,
        'is_published' => true,
    ]);
    $b2 = Block::factory()->create([
        'site_id' => $user->site->id,
        'order' => 1,
        'is_active' => true,
        'is_published' => true,
    ]);
    $site = $user->site->fresh();
    $site->update([
        'published_at' => now(),
        'published_blocks_snapshot' => SitePublishState::snapshot($site->fresh()),
    ]);

    $b1->update(['order' => 1]);
    $b2->update(['order' => 0]);
    Block::factory()->create([
        'site_id' => $site->id,
        'order' => 2,
        'is_active' => true,
        'is_published' => false,
    ]);

    $this->actingAs($user)->deleteJson('/api/blocks/unpublished')->assertOk();

    expect($b1->fresh()->order)->toBe(0);
    expect($b2->fresh()->order)->toBe(1);
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
