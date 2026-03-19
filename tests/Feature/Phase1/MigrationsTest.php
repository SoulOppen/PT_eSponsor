<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('runs all migrations without errors', function () {
    expect(true)->toBeTrue(); // RefreshDatabase already ran them
});

it('has the expected tables after migration', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasTable('sites'))->toBeTrue();
    expect(Schema::hasTable('blocks'))->toBeTrue();
});

it('blocks table has required columns', function () {
    expect(Schema::hasColumns('blocks', [
        'id', 'site_id', 'type', 'props', 'order', 'is_active', 'is_published',
    ]))->toBeTrue();
});

it('sites table has required columns', function () {
    expect(Schema::hasColumns('sites', [
        'id', 'user_id', 'name', 'slug', 'bio', 'avatar_url', 'published_at',
    ]))->toBeTrue();
});

