<?php

use App\Models\Color;
use App\Models\Disc;
use App\Models\Location;
use App\Models\User;
use App\Services\MatchScorer;

test('distance does not zero the score for far-away discs', function () {
    $lostUser = User::factory()->create();
    $foundUser = User::factory()->create();
    $orange = Color::query()->firstOrCreate(['name' => 'Orange']);

    $lost = Disc::create([
        'user_id' => $lostUser->id,
        'status' => 'lost',
        'occurred_at' => now()->subDays(2),
        'manufacturer' => 'Innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
        'back_text' => 'ABC123',
        'condition_estimate' => 'good',
        'active' => true,
        'match_lifecycle' => null,
    ]);
    $lost->colors()->sync([$orange->id]);
    Location::create([
        'disc_id' => $lost->id,
        'latitude' => 59.437,
        'longitude' => 24.7536,
        'location_type' => 'lost',
        'location_text' => 'Tallinn',
    ]);

    $found = Disc::create([
        'user_id' => $foundUser->id,
        'status' => 'found',
        'occurred_at' => now()->subDay(),
        'manufacturer' => 'Innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
        'back_text' => 'ABC123',
        'condition_estimate' => 'good',
        'active' => true,
        'match_lifecycle' => null,
    ]);
    $found->colors()->sync([$orange->id]);
    Location::create([
        'disc_id' => $found->id,
        'latitude' => 58.3776,
        'longitude' => 26.7290,
        'location_type' => 'found',
        'location_text' => 'Tartu',
    ]);

    $lost->loadMissing(['colors', 'locations']);
    $found->loadMissing(['colors', 'locations']);

    $scored = (new MatchScorer)->score($lost, $found);

    expect($scored)->not->toBeNull();
    expect($scored['distance_score'])->toBeGreaterThan(0.0);
    expect($scored['match_score'])->toBeGreaterThan(60.0);
});
