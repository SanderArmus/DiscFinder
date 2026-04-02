<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

test('google callback redirects new users to choose username', function () {
    $providerUser = Mockery::mock();
    $providerUser->shouldReceive('getId')->andReturn('g_123');
    $providerUser->shouldReceive('getEmail')->andReturn('new@example.com');

    $driverMock = Mockery::mock();
    $driverMock->shouldReceive('user')->andReturn($providerUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($driverMock);

    $response = $this->get(route('auth.google.callback', absolute: false));

    $response->assertRedirect(route('auth.google.username', absolute: false));
    $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);

    $this->assertEquals(
        ['id' => 'g_123', 'email' => 'new@example.com'],
        session('auth.google'),
    );
});

test('google callback logs in existing user by email and marks it verified', function () {
    $user = User::factory()
        ->unverified()
        ->create([
            'email' => 'existing@example.com',
            'google_id' => null,
        ]);

    $providerUser = Mockery::mock();
    $providerUser->shouldReceive('getId')->andReturn('g_123');
    $providerUser->shouldReceive('getEmail')->andReturn('existing@example.com');

    $driverMock = Mockery::mock();
    $driverMock->shouldReceive('user')->andReturn($providerUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($driverMock);

    $response = $this->get(route('auth.google.callback', absolute: false));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);

    $fresh = $user->fresh();
    expect($fresh->google_id)->toBe('g_123');
    expect($fresh->email_verified_at)->not->toBeNull();
});

test('storing chosen username creates a new user and logs them in', function () {
    $this->withSession([
        'auth.google' => [
            'id' => 'g_123',
            'email' => 'new@example.com',
        ],
    ]);

    $response = $this->post(route('auth.google.username.store', absolute: false), [
        'username' => 'chosenname',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'new@example.com')->firstOrFail();
    expect($user->username)->toBe('chosenname');
    expect($user->google_id)->toBe('g_123');
    expect($user->email_verified_at)->not->toBeNull();
});

test('google callback works even if email is missing', function () {
    $providerUser = Mockery::mock();
    $providerUser->shouldReceive('getId')->andReturn('g_999');
    $providerUser->shouldReceive('getEmail')->andReturn(null);

    $driverMock = Mockery::mock();
    $driverMock->shouldReceive('user')->andReturn($providerUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($driverMock);

    $response = $this->get(route('auth.google.callback', absolute: false));

    $response->assertRedirect(route('auth.google.username', absolute: false));
    expect(session('auth.google.email'))->toBe('google_g_999@discivo.local');
});
