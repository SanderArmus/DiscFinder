<?php

use App\Models\Message;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('non-admin cannot access admin support messages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.support-messages.index'))
        ->assertForbidden();
});

test('admin can view support messages and reply', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $incoming = Message::create([
        'sender_id' => $user->id,
        'receiver_id' => $admin->id,
        'match_id' => null,
        'content' => 'Something is broken.',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.support-messages.index'))
        ->assertRedirect(route('messages.index'));

    $this->actingAs($admin)
        ->get(route('admin.support-chat.show', ['user' => $user->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/SupportChat')
            ->where('receiverId', $user->id)
        );
});
