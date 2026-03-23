<?php

use App\Models\User;

it('user can register with valid data', function () {
    $this->post('/register', [
        'name' => 'Ana García',
        'email' => 'ana@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'slug' => 'ana-garcia',
    ])->assertRedirect('/dashboard');
    expect(User::where('email', 'ana@example.com')->exists())->toBeTrue();
});

it('a site is automatically created on registration', function () {
    $this->post('/register', [
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'slug' => 'ana',
    ]);
    $user = User::where('email', 'ana@example.com')->first();
    expect($user->site)->not->toBeNull();
});

it('auto-generated slug is url-safe', function () {
    $this->post('/register', [
        'name' => 'Ana García',
        'email' => 'a@b.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'slug' => 'ana-garcia-url',
    ]);
    $slug = User::where('email', 'a@b.com')->first()->site->slug;
    expect($slug)->toMatch('/^[a-z0-9\-]+$/');
});

it('user can login with correct credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);
    $this->post('/login', ['email' => $user->email, 'password' => 'secret'])
        ->assertRedirect('/dashboard');
});

it('login fails with wrong password', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);
    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
});

it('guest cannot access dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('redirects to dashboard after login when intended url is a draft preview', function () {
    $user = User::factory()->hasSite(['slug' => 'alice'])->create([
        'password' => bcrypt('secret'),
        'email_verified_at' => now(),
    ]);

    $this->get('/draft/@alice')->assertRedirect(route('login'));

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret',
    ])->assertRedirect('/dashboard');
});

it('login redirects to dashboard when intended draft is another users site', function () {
    User::factory()->hasSite(['slug' => 'bob'])->create();

    $viewer = User::factory()->hasSite(['slug' => 'victor'])->create([
        'password' => bcrypt('secret'),
        'email_verified_at' => now(),
    ]);

    $this->get('/draft/@bob')->assertRedirect(route('login'));

    $this->post('/login', [
        'email' => $viewer->email,
        'password' => 'secret',
    ])->assertRedirect('/dashboard');
});

it('login redirects to dashboard when redirect field targets another users draft', function () {
    User::factory()->hasSite(['slug' => 'bob'])->create();

    $viewer = User::factory()->hasSite(['slug' => 'victor'])->create([
        'password' => bcrypt('secret'),
        'email_verified_at' => now(),
    ]);

    $this->post('/login', [
        'email' => $viewer->email,
        'password' => 'secret',
        'redirect' => '/draft/@bob',
    ])->assertRedirect('/dashboard');
});

it('login ignores unsafe redirect query param', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret'),
        'email_verified_at' => now(),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret',
        'redirect' => 'https://evil.example/phishing',
    ])->assertRedirect('/dashboard');
});
