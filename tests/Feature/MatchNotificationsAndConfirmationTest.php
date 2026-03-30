<?php

use App\Models\Confirmation;
use App\Models\Disc;
use App\Models\MatchThread;
use App\Models\Message;
use App\Models\User;
use App\Services\UnreadMessagesCounter;
use Inertia\Testing\AssertableInertia as Assert;

test('unread message count decreases when opening the match chat', function () {
    $lostOwner = User::factory()->create();
    $finderUser = User::factory()->create();

    $lostDisc = Disc::create([
        'user_id' => $lostOwner->id,
        'status' => 'lost',
        'occurred_at' => now(),
        'active' => true,
    ]);

    $foundDisc = Disc::create([
        'user_id' => $finderUser->id,
        'status' => 'found',
        'occurred_at' => now()->addDay(),
        'active' => true,
    ]);

    $thread = MatchThread::create([
        'lost_disc_id' => $lostDisc->id,
        'found_disc_id' => $foundDisc->id,
        'match_score' => 80.0,
        'status' => 'pending',
    ]);

    Message::create([
        'sender_id' => $finderUser->id,
        'receiver_id' => $lostOwner->id,
        'match_id' => $thread->id,
        'content' => 'Hi!',
    ]);

    $counter = app(UnreadMessagesCounter::class);
    expect($counter->countForUser($lostOwner))->toBe(1);

    $this->actingAs($lostOwner)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('unreadMessageCount', 1));

    $this->actingAs($lostOwner)->get(route('matches.chat', ['match' => $thread->id]))
        ->assertOk();

    $this->actingAs($lostOwner)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('unreadMessageCount', 0));

    expect($counter->countForUser($lostOwner))->toBe(0);
});

test('both sides confirming deactivates discs and confirms the match', function () {
    $lostOwner = User::factory()->create();
    $finderUser = User::factory()->create();

    $lostDisc = Disc::create([
        'user_id' => $lostOwner->id,
        'status' => 'lost',
        'active' => true,
    ]);

    $foundDisc = Disc::create([
        'user_id' => $finderUser->id,
        'status' => 'found',
        'active' => true,
    ]);

    $thread = MatchThread::create([
        'lost_disc_id' => $lostDisc->id,
        'found_disc_id' => $foundDisc->id,
        'match_score' => 80.0,
        'status' => 'pending',
    ]);

    $this->actingAs($lostOwner)
        ->post(route('matches.confirm', ['match' => $thread->id]))
        ->assertRedirect(route('matches.chat', ['match' => $thread->id]));

    $confirmation = Confirmation::query()->where('match_id', $thread->id)->first();
    expect($confirmation)->not->toBeNull();
    expect($confirmation->owner_confirmed)->toBeTrue();
    expect($confirmation->finder_confirmed)->toBeFalse();
    expect($confirmation->confirmed_at)->toBeNull();

    $lostDisc->refresh();
    $foundDisc->refresh();
    expect($lostDisc->active)->toBeTrue();
    expect($foundDisc->active)->toBeTrue();

    $this->actingAs($finderUser)
        ->post(route('matches.confirm', ['match' => $thread->id]))
        ->assertRedirect(route('matches.chat', ['match' => $thread->id]));

    $thread->refresh();
    $confirmation->refresh();
    $lostDisc->refresh();
    $foundDisc->refresh();

    expect($thread->status)->toBe('confirmed');
    expect($confirmation->owner_confirmed)->toBeTrue();
    expect($confirmation->finder_confirmed)->toBeTrue();
    expect($confirmation->confirmed_at)->not->toBeNull();

    expect($lostDisc->active)->toBeFalse();
    expect($foundDisc->active)->toBeFalse();

    expect($lostDisc->match_lifecycle)->toBe('confirmed');
    expect($foundDisc->match_lifecycle)->toBe('confirmed');
});

test('both sides handed over deactivates match and marks discs as handed over', function () {
    $lostOwner = User::factory()->create();
    $finderUser = User::factory()->create();

    $lostDisc = Disc::create([
        'user_id' => $lostOwner->id,
        'status' => 'lost',
        'active' => true,
    ]);

    $foundDisc = Disc::create([
        'user_id' => $finderUser->id,
        'status' => 'found',
        'active' => true,
    ]);

    $thread = MatchThread::create([
        'lost_disc_id' => $lostDisc->id,
        'found_disc_id' => $foundDisc->id,
        'match_score' => 80.0,
        'status' => 'pending',
    ]);

    $this->actingAs($lostOwner)
        ->post(route('matches.confirm', ['match' => $thread->id]))
        ->assertRedirect(route('matches.chat', ['match' => $thread->id]));

    $this->actingAs($finderUser)
        ->post(route('matches.confirm', ['match' => $thread->id]))
        ->assertRedirect(route('matches.chat', ['match' => $thread->id]));

    $this->actingAs($lostOwner)
        ->post(route('matches.handover', ['match' => $thread->id]))
        ->assertRedirect(route('matches.chat', ['match' => $thread->id]));

    $this->actingAs($finderUser)
        ->post(route('matches.handover', ['match' => $thread->id]))
        ->assertRedirect(route('matches.chat', ['match' => $thread->id]));

    $thread->refresh();
    $confirmation = Confirmation::query()->where('match_id', $thread->id)->first();
    expect($confirmation)->not->toBeNull();
    $confirmation->refresh();

    $lostDisc->refresh();
    $foundDisc->refresh();

    expect($thread->status)->toBe('handed_over');
    expect($confirmation->owner_handed_over)->toBeTrue();
    expect($confirmation->finder_handed_over)->toBeTrue();
    expect($confirmation->handed_over_at)->not->toBeNull();

    expect($lostDisc->match_lifecycle)->toBe('handed_over');
    expect($foundDisc->match_lifecycle)->toBe('handed_over');
});
