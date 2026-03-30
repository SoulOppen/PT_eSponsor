<?php

use App\Models\User;
use App\Services\BlockSchemaRegistry;

it('music schema has platform and url fields', function () {
    $fields = collect((new BlockSchemaRegistry)->get('music')['fields']);
    expect($fields->pluck('key')->toArray())->toContain('platform', 'url');
    expect($fields->firstWhere('key', 'url')['type'])->toBe('text');
});

it('social schema has a links repeater', function () {
    $fields = collect((new BlockSchemaRegistry)->get('social')['fields']);
    $repeater = $fields->firstWhere('type', 'repeater');
    expect($repeater)->not->toBeNull();
    $urlSub = collect($repeater['subfields'] ?? [])->firstWhere('key', 'url');
    expect($urlSub['type'] ?? null)->toBe('text');
});

it('can create a music block via API', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->postJson('/api/blocks', [
            'type' => 'music',
            'props' => ['platform' => 'spotify', 'url' => 'https://open.spotify.com/track/123'],
        ])
        ->assertCreated();
});
