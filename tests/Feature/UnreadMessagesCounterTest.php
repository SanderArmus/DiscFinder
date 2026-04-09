<?php

use App\Models\Message;
use App\Models\User;
use App\Services\UnreadMessagesCounter;

test('support messages do not count towards unread badge', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    Message::create([
        'sender_id' => $admin->id,
        'receiver_id' => $user->id,
        'match_id' => null,
        'content' => 'Support ping',
    ]);

    $count = app(UnreadMessagesCounter::class)->countForUser($user);

    expect($count)->toBe(0);
});
