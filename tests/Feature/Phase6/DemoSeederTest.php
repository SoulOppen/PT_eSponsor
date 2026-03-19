<?php

use App\Models\User;

it('demo seeder creates a user with a complete site', function () {
    $this->artisan('db:seed', ['--class' => 'DemoSeeder'])->assertSuccessful();
    $user = User::where('email', 'demo@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->site)->not->toBeNull();
    expect($user->site->blocks->count())->toBeGreaterThanOrEqual(4);
});

it('demo site has at least one block of each MVP type', function () {
    $this->artisan('db:seed', ['--class' => 'DemoSeeder']);
    $types = User::where('email', 'demo@example.com')->first()
        ->site->blocks->pluck('type')->unique()->toArray();
    foreach (['links', 'text', 'image', 'video'] as $required) {
        expect($types)->toContain($required);
    }
});

it('demo site is published and publicly accessible', function () {
    $this->artisan('db:seed', ['--class' => 'DemoSeeder']);
    $slug = User::where('email', 'demo@example.com')->first()->site->slug;
    $this->get("/@{$slug}")->assertOk();
});
