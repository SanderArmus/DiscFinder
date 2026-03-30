<?php

use App\Models\Color;
use App\Models\Disc;
use App\Models\Location;
use App\Models\Message;
use App\Models\User;
use App\Services\MatchFinder;

test('users can leave a message in a match chat', function () {
    $lostOwner = User::factory()->create();
    $finderUser = User::factory()->create();

    $color = Color::create(['name' => 'Blue']);

    $lost = Disc::create([
        'user_id' => $lostOwner->id,
        'status' => 'lost',
        'occurred_at' => now()->subDay(),
        'manufacturer' => 'Innova',
        'model_name' => 'Glitch',
        'plastic_type' => 'Neutron',
        'back_text' => 'ABC',
        'condition_estimate' => 'good',
        'active' => true,
    ]);
    $lost->colors()->attach($color->id);
    Location::create([
        'disc_id' => $lost->id,
        'latitude' => 59.437,
        'longitude' => 24.7536,
        'location_type' => 'lost',
    ]);

    $found = Disc::create([
        'user_id' => $finderUser->id,
        'status' => 'found',
        'occurred_at' => now()->subDay()->addDay(),
        'manufacturer' => 'Innova',
        'model_name' => 'Glitch',
        'plastic_type' => 'Neutron',
        'back_text' => 'ABC',
        'condition_estimate' => 'good',
        'active' => true,
    ]);
    $found->colors()->attach($color->id);
    Location::create([
        'disc_id' => $found->id,
        'latitude' => 59.437,
        'longitude' => 24.7536,
        'location_type' => 'found',
    ]);

    $finder = new MatchFinder;
    $matches = $finder->findForUser($lostOwner, limit: 10, minScore: 60.0);

    expect($matches)->toHaveCount(1);
    $matchId = $matches[0]['id'];

    $this
        ->actingAs($lostOwner)
        ->post(route('matches.messages.store', ['match' => $matchId]), [
            'content' => 'Hi! I think we found the right disc.',
        ])
        ->assertRedirect(route('matches.chat', ['match' => $matchId]));

    $message = Message::query()->where('match_id', $matchId)->latest()->first();

    expect($message)->not->toBeNull();
    expect($message->sender_id)->toBe($lostOwner->id);
    expect($message->receiver_id)->toBe($finderUser->id);
    expect($message->content)->toBe('Hi! I think we found the right disc.');
});
