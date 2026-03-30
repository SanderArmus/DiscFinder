<?php

use App\Models\Color;
use App\Models\Disc;
use App\Models\Location;
use App\Models\User;
use App\Services\MatchFinder;
use Illuminate\Support\Carbon;

test('does not match lost and found discs from the same user', function () {
    $user = User::factory()->create();

    $color = Color::create(['name' => 'Blue']);

    $lost = Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'occurred_at' => Carbon::now()->subDay(),
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

    // Same user "found" should never be considered a match.
    $found = Disc::create([
        'user_id' => $user->id,
        'status' => 'found',
        'occurred_at' => Carbon::now()->subHours(12)->addDay(),
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
    $matches = $finder->findForUser($user, limit: 10, minScore: 60.0);

    expect($matches)->toBeArray()->toHaveCount(0);
});

test('finds matches from other users when time order, color, and condition match', function () {
    $lostOwner = User::factory()->create();
    $finderUser = User::factory()->create();

    $color = Color::create(['name' => 'Blue']);

    $lost = Disc::create([
        'user_id' => $lostOwner->id,
        'status' => 'lost',
        'occurred_at' => Carbon::now()->subDay(),
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
        'occurred_at' => Carbon::now()->subDay()->addDay(),
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

    expect($matches)->toBeArray()->toHaveCount(1);
    expect($matches[0]['confidence'])->toBeGreaterThanOrEqual(60);
    expect($matches[0]['foundDiscId'])->toBe($found->id);
});

test('discard pairs when found time is before lost time', function () {
    $lostOwner = User::factory()->create();
    $finderUser = User::factory()->create();

    $color = Color::create(['name' => 'Blue']);

    $lost = Disc::create([
        'user_id' => $lostOwner->id,
        'status' => 'lost',
        'occurred_at' => Carbon::now()->subDay(),
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

    // found before lost => should be discarded.
    $found = Disc::create([
        'user_id' => $finderUser->id,
        'status' => 'found',
        'occurred_at' => Carbon::now()->subDay()->subHours(2),
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

    expect($matches)->toBeArray()->toHaveCount(0);
});
