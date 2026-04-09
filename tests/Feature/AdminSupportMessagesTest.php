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
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/SupportMessages')
            ->has('messages.data', 1)
            ->where('messages.data.0.id', $incoming->id)
        );

    $this->actingAs($admin)
        ->post(route('admin.support-messages.reply', ['message' => $incoming->id]), [
            'content' => 'Thanks, we are looking into it.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('messages', [
        'sender_id' => $admin->id,
        'receiver_id' => $user->id,
        'match_id' => null,
        'content' => 'Thanks, we are looking into it.',
    ]);
});
