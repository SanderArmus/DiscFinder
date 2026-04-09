<?php

use App\Models\MatchThread;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;

test('user can end a match chat and cannot send messages afterwards', function () {
    $lostUser = User::factory()->create();
    $foundUser = User::factory()->create();

    $now = Carbon::parse('2026-04-08 12:00:00');

    $lostDisc = \App\Models\Disc::create([
        'status' => 'lost',
        'user_id' => $lostUser->id,
        'occurred_at' => $now->copy()->subDay(),
        'manufacturer' => 'Innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
    ]);

    $foundDisc = \App\Models\Disc::create([
        'status' => 'found',
        'user_id' => $foundUser->id,
        'occurred_at' => $now,
        'manufacturer' => 'Innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
    ]);

    $match = MatchThread::create([
        'lost_disc_id' => $lostDisc->id,
        'found_disc_id' => $foundDisc->id,
        'match_score' => 80,
        'status' => null,
    ]);

    $this->actingAs($lostUser)
        ->post(route('matches.end', ['match' => $match->id]))
        ->assertRedirect(route('messages.index'));

    $this->actingAs($lostUser)
        ->post(route('matches.messages.store', ['match' => $match->id]), [
            'content' => 'Hi',
        ])
        ->assertForbidden();

    $this->actingAs($lostUser)
        ->post(route('matches.confirm', ['match' => $match->id]))
        ->assertForbidden();

    $this->actingAs($lostUser)
        ->post(route('matches.reject', ['match' => $match->id]))
        ->assertForbidden();
});

test('user can end support chat and cannot send messages afterwards', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('support.chat.end'))
        ->assertRedirect(route('messages.index'));

    $this->actingAs($user)
        ->post(route('support.messages.store'), [
            'content' => 'Help',
        ])
        ->assertForbidden();

    expect(Message::query()->count())->toBe(0);
});
