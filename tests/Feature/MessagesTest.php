<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('messages.index'));
    $response->assertRedirect(route('home'));
});

test('authenticated users can visit messages', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('messages.index'));
    $response->assertOk();
});
