<?php

use App\Models\Message;
use App\Models\User;

test('user can send message to admin from help', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('help.admin-message.store'), [
            'content' => 'Hello admin, I need help.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('messages', [
        'sender_id' => $user->id,
        'receiver_id' => $admin->id,
        'match_id' => null,
        'content' => 'Hello admin, I need help.',
    ]);

    expect(Message::query()->count())->toBe(1);
});
