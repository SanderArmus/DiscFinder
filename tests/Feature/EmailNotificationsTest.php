<?php

use App\Models\Disc;
use App\Models\MatchThread;
use App\Models\Message;
use App\Models\User;
use App\Notifications\DiscExpiredNotification;
use App\Notifications\DiscExpiringSoonNotification;
use App\Notifications\NewMessageEmailNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

test('expiring soon command emails only once per disc and respects preference', function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00'));

    $user = User::factory()->create([
        'email_notify_disc_expiring' => true,
    ]);

    $disc = Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'active' => true,
        'expires_at' => now()->addDays(3),
    ]);

    $this->artisan('discs:notify-expiring-soon')->assertExitCode(0);

    Notification::assertSentTo($user, DiscExpiringSoonNotification::class);
    expect($disc->refresh()->expiring_soon_notified_at)->not->toBeNull();

    Notification::fake();
    $this->artisan('discs:notify-expiring-soon')->assertExitCode(0);
    Notification::assertNothingSent();

    $noEmailUser = User::factory()->create([
        'email_notify_disc_expiring' => false,
    ]);
    $disc2 = Disc::create([
        'user_id' => $noEmailUser->id,
        'status' => 'found',
        'active' => true,
        'expires_at' => now()->addDays(2),
    ]);

    Notification::fake();
    $this->artisan('discs:notify-expiring-soon')->assertExitCode(0);
    Notification::assertNothingSent();
    expect($disc2->refresh()->expiring_soon_notified_at)->not->toBeNull();
});

test('expire command emails disc expired notification when enabled', function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00'));

    $user = User::factory()->create([
        'email_notify_disc_expired' => true,
    ]);

    $disc = Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'active' => true,
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('discs:expire')->assertExitCode(0);

    expect($disc->refresh()->active)->toBeFalse();
    Notification::assertSentTo($user, DiscExpiredNotification::class);
});

test('sending match message emails receiver when enabled (throttled)', function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00'));

    $sender = User::factory()->create();
    $receiver = User::factory()->create(['email_notify_new_message' => true]);

    $lostDisc = Disc::create([
        'status' => 'lost',
        'user_id' => $sender->id,
        'occurred_at' => now()->subDay(),
        'manufacturer' => 'Innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
        'active' => true,
        'expires_at' => now()->addDays(90),
    ]);

    $foundDisc = Disc::create([
        'status' => 'found',
        'user_id' => $receiver->id,
        'occurred_at' => now(),
        'manufacturer' => 'Innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
        'active' => true,
        'expires_at' => now()->addDays(90),
    ]);

    $match = MatchThread::create([
        'lost_disc_id' => $lostDisc->id,
        'found_disc_id' => $foundDisc->id,
        'match_score' => 80,
        'status' => null,
    ]);

    $this->actingAs($sender)
        ->post(route('matches.messages.store', ['match' => $match->id]), [
            'content' => 'Hello',
        ])
        ->assertRedirect();

    Notification::assertSentTo($receiver, NewMessageEmailNotification::class);

    Notification::fake();
    $this->actingAs($sender)
        ->post(route('matches.messages.store', ['match' => $match->id]), [
            'content' => 'Hello again',
        ])
        ->assertRedirect();

    Notification::assertNothingSent();

    expect(Message::query()->where('match_id', $match->id)->count())->toBe(2);
});
