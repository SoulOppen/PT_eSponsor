<?php

use App\Models\Block;
use App\Models\User;
use App\Support\SitePublishState;

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

it('draft preview shows edit toolbar for site owner only', function () {
    $owner = User::factory()->hasSite(['slug' => 'owner-site'])->create();
    $other = User::factory()->hasSite(['slug' => 'other'])->create();

    $this->actingAs($owner)
        ->get('/draft/@owner-site')
        ->assertOk()
        ->assertSee('id="draft-edit-toolbar"', false)
        ->assertSee('id="draft-blocks"', false)
        ->assertSee('draft-edit-publish', false)
        ->assertSee('data-can-publish="0"', false);

    $this->actingAs($other)
        ->get('/draft/@owner-site')
        ->assertOk()
        ->assertDontSee('id="draft-edit-toolbar"', false)
        ->assertSee('Ir a tu editor');
});

it('draft preview enables publish when there are unpublished active blocks', function () {
    $owner = User::factory()->hasSite(['slug' => 'has-draft'])->create();
    Block::factory()->create([
        'site_id' => $owner->site->id,
        'type' => 'text',
        'props' => ['content' => 'Unpublished'],
        'is_active' => true,
        'is_published' => false,
    ]);

    $this->actingAs($owner)
        ->get('/draft/@has-draft')
        ->assertOk()
        ->assertSee('data-can-publish="1"', false);
});

it('draft preview disables publish when all active blocks are already published', function () {
    $owner = User::factory()->hasSite(['slug' => 'all-pub'])->create();
    Block::factory()->create([
        'site_id' => $owner->site->id,
        'type' => 'text',
        'props' => ['content' => 'Live'],
        'is_active' => true,
        'is_published' => true,
    ]);
    $site = $owner->site->fresh();
    $site->update([
        'published_at' => now(),
        'published_blocks_snapshot' => SitePublishState::snapshot($site->fresh()),
    ]);

    $this->actingAs($owner)
        ->get('/draft/@all-pub')
        ->assertOk()
        ->assertSee('data-can-publish="0"', false);
});

it('draft preview enables publish after reorder when all blocks were already published', function () {
    $owner = User::factory()->hasSite(['slug' => 'reorder-pub'])->create();
    Block::factory()->create([
        'site_id' => $owner->site->id,
        'order' => 0,
        'is_active' => true,
        'is_published' => true,
    ]);
    Block::factory()->create([
        'site_id' => $owner->site->id,
        'order' => 1,
        'is_active' => true,
        'is_published' => true,
    ]);
    $site = $owner->site->fresh();
    $site->update([
        'published_at' => now(),
        'published_blocks_snapshot' => SitePublishState::snapshot($site->fresh()),
    ]);

    $b1 = $site->blocks()->where('order', 0)->first();
    $b2 = $site->blocks()->where('order', 1)->first();

    $this->actingAs($owner)
        ->get('/draft/@reorder-pub')
        ->assertOk()
        ->assertSee('data-can-publish="0"', false);

    $this->actingAs($owner)
        ->postJson('/api/blocks/reorder', [
            'blocks' => [
                ['id' => $b2->id, 'order' => 0],
                ['id' => $b1->id, 'order' => 1],
            ],
        ])
        ->assertOk();

    $this->actingAs($owner)
        ->get('/draft/@reorder-pub')
        ->assertOk()
        ->assertSee('data-can-publish="1"', false);
});

it('draft preview disables publish after volver a lo publicado when snapshot is realigned', function () {
    $owner = User::factory()->hasSite(['slug' => 'prune-sync'])->create();
    Block::factory()->create([
        'site_id' => $owner->site->id,
        'order' => 0,
        'is_active' => true,
        'is_published' => true,
    ]);
    $site = $owner->site->fresh();
    $site->update([
        'published_at' => now(),
        'published_blocks_snapshot' => SitePublishState::snapshot($site->fresh()),
    ]);

    Block::factory()->create([
        'site_id' => $owner->site->id,
        'order' => 1,
        'is_active' => true,
        'is_published' => false,
    ]);

    $this->actingAs($owner)
        ->get('/draft/@prune-sync')
        ->assertOk()
        ->assertSee('data-can-publish="1"', false);

    $this->actingAs($owner)->deleteJson('/api/blocks/unpublished')->assertOk();

    $this->actingAs($owner)
        ->get('/draft/@prune-sync')
        ->assertOk()
        ->assertSee('data-can-publish="0"', false);
});
