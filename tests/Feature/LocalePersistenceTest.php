<?php

use App\Models\User;

test('switching locale stores it in the session for guests', function () {
    $this->get('/locale/et')->assertRedirect();

    expect(session('locale'))->toBe('et');
});

test('switching locale persists to the signed-in user', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)->get('/locale/et')->assertRedirect();

    expect($user->fresh()->locale)->toBe('et');
    expect(session('locale'))->toBe('et');
});

test('rejects unsupported locales', function () {
    $this->get('/locale/xx')->assertStatus(400);
});

test('signed-in user preferred locale is applied on later requests', function () {
    $user = User::factory()->create(['locale' => 'et']);

    $this->actingAs($user)->get('/dashboard');

    expect(app()->getLocale())->toBe('et');
});

test('user locale wins over a different session locale', function () {
    $user = User::factory()->create(['locale' => 'et']);

    $this->actingAs($user)
        ->withSession(['locale' => 'en'])
        ->get('/dashboard');

    expect(app()->getLocale())->toBe('et');
});
