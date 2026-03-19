<?php

use App\Models\User;

it('authenticated user can update their profile', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->patch('/api/profile', ['name' => 'New Name', 'bio' => 'Bio', 'slug' => 'new-slug'])
        ->assertOk();
    expect($user->site->fresh()->slug)->toBe('new-slug');
});

it('slug must be unique across sites', function () {
    User::factory()->hasSite(['slug' => 'taken'])->create();
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->patch('/api/profile', ['slug' => 'taken'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
});

it('slug rejects invalid characters', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
        ->patch('/api/profile', ['slug' => 'my slug!'])
        ->assertUnprocessable();
});

it('user cannot overwrite another users site', function () {
    $victim = User::factory()->hasSite(['slug' => 'victim-slug'])->create();
    $attacker = User::factory()->hasSite()->create();
    $this->actingAs($attacker)
        ->patch('/api/profile', ['slug' => 'new-for-attacker'])
        ->assertOk();
    expect($victim->site->fresh()->slug)->toBe('victim-slug');
});
