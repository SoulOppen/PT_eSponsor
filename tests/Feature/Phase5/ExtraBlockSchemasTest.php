<?php

use App\Models\User;
use App\Services\BlockSchemaRegistry;

it('music schema has platform and url fields', function () {
    $fields = collect((new BlockSchemaRegistry)->get('music')['fields']);
    expect($fields->pluck('key')->toArray())->toContain('platform', 'url');
});

it('social schema has a links repeater', function () {
    $fields = collect((new BlockSchemaRegistry)->get('social')['fields']);
    expect($fields->firstWhere('type', 'repeater'))->not->toBeNull();
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
