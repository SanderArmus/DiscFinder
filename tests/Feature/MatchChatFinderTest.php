<?php

use App\Models\Confirmation;
use App\Models\Disc;
use App\Models\Location;
use App\Models\MatchThread;
use App\Models\Message;
use App\Models\User;
use App\Services\MatchChatFinder;
use Illuminate\Support\Carbon;

test('match chat threads show correct other disc for both sides', function () {
    $lostOwner = User::factory()->create(['name' => 'Lost Owner']);
    $foundOwner = User::factory()->create(['name' => 'Found Owner']);

    $lostDisc = Disc::create([
        'user_id' => $lostOwner->id,
        'status' => 'lost',
        'occurred_at' => Carbon::now(),
        'manufacturer' => 'Innova',
        'model_name' => 'Glitch',
        'plastic_type' => 'Neutron',
        'back_text' => 'ABC',
        'condition_estimate' => 'good',
        'active' => true,
    ]);

    Location::create([
        'disc_id' => $lostDisc->id,
        'latitude' => null,
        'longitude' => null,
        'location_type' => 'lost',
        'location_text' => 'Lost Park',
    ]);

    $foundDisc = Disc::create([
        'user_id' => $foundOwner->id,
        'status' => 'found',
        'occurred_at' => Carbon::now()->addDay(),
        'manufacturer' => 'Innova',
        'model_name' => 'Glitch',
        'plastic_type' => 'Neutron',
        'back_text' => 'ABC',
        'condition_estimate' => 'good',
        'active' => true,
    ]);

    Location::create([
        'disc_id' => $foundDisc->id,
        'latitude' => null,
        'longitude' => null,
        'location_type' => 'found',
        'location_text' => 'Found Park',
    ]);

    $thread = MatchThread::create([
        'lost_disc_id' => $lostDisc->id,
        'found_disc_id' => $foundDisc->id,
        'match_score' => 80.0,
        'status' => 'pending',
    ]);

    // Simulate: lost owner confirmed, found owner not confirmed yet.
    Confirmation::create([
        'match_id' => $thread->id,
        'owner_confirmed' => true,
        'finder_confirmed' => false,
    ]);

    Message::create([
        'sender_id' => $lostOwner->id,
        'receiver_id' => $foundOwner->id,
        'match_id' => $thread->id,
        'content' => 'Hi!',
    ]);

    $finder = new MatchChatFinder;

    $lostThreads = $finder->findThreadsForUser($lostOwner, limit: 10);

    expect($lostThreads)->toHaveCount(1);
    expect($lostThreads[0]['id'])->toBe($thread->id);
    expect($lostThreads[0]['otherUserName'])->toBe('Found Owner');
    expect($lostThreads[0]['discName'])->toBe('Glitch');
    expect($lostThreads[0]['otherDiscLocation'])->toBe('Found Park');
    expect($lostThreads[0]['matchStatus'])->toBe('pending');
    expect($lostThreads[0]['otherConfirmed'])->toBeFalse();

    $foundThreads = $finder->findThreadsForUser($foundOwner, limit: 10);

    expect($foundThreads)->toHaveCount(1);
    expect($foundThreads[0]['otherUserName'])->toBe('Lost Owner');
    expect($foundThreads[0]['discName'])->toBe('Glitch');
    expect($foundThreads[0]['otherDiscLocation'])->toBe('Lost Park');
    expect($foundThreads[0]['matchStatus'])->toBe('pending');
    expect($foundThreads[0]['otherConfirmed'])->toBeTrue();
});
