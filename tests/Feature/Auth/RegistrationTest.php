<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('username must be unique', function () {
    \App\Models\User::factory()->create([
        'username' => 'takenname',
        'email' => 'first@example.com',
    ]);

    $response = $this->from(route('register'))->post(route('register.store'), [
        'username' => 'takenname',
        'email' => 'second@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['username']);
});
