<?php

use App\Models\User;
use App\Services\BlockSchemaRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns all registered block types', function () {
    $registry = new BlockSchemaRegistry;
    expect(array_keys($registry->all()))
        ->toContain('links', 'text', 'image', 'video', 'social', 'music');
});

it('returns schema for a specific type', function () {
    $schema = (new BlockSchemaRegistry)->get('links');
    expect($schema)->toHaveKey('label')->toHaveKey('fields');
    expect($schema['fields'])->not->toBeEmpty();
});

it('throws on unknown block type', function () {
    expect(fn () => (new BlockSchemaRegistry)->get('unknown'))
        ->toThrow(InvalidArgumentException::class);
});

it('every field in every schema has key, type, and label', function () {
    foreach ((new BlockSchemaRegistry)->all() as $schema) {
        foreach ($schema['fields'] as $field) {
            expect($field)->toHaveKeys(['key', 'type', 'label']);
        }
    }
});

it('GET /api/block-schemas returns all schemas', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->getJson('/api/block-schemas')
        ->assertOk()
        ->assertJsonStructure(['links', 'text', 'image', 'video', 'social', 'music']);
});
