<?php

use App\Models\Color;
use App\Models\Disc;
use App\Models\Location;
use App\Models\MatchThread;
use App\Models\User;

test('creating a lost disc triggers matching', function () {
    $lostUser = User::factory()->create();
    $foundUser = User::factory()->create();

    $red = Color::query()->firstOrCreate(['name' => 'Red']);

    $foundDisc = Disc::create([
        'user_id' => $foundUser->id,
        'status' => 'found',
        'occurred_at' => now(),
        'manufacturer' => 'Innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
        'back_text' => 'ABC',
        'condition_estimate' => 'good',
        'active' => true,
        'match_lifecycle' => null,
    ]);
    $foundDisc->colors()->sync([$red->id]);
    Location::create([
        'disc_id' => $foundDisc->id,
        'latitude' => 59.437,
        'longitude' => 24.7536,
        'location_type' => 'found',
        'location_text' => 'Tallinn',
    ]);

    $this->actingAs($lostUser)
        ->post(route('lost-discs.store'), [
            'datetime' => now()->subHour()->format('Y-m-d\\TH:i'),
            'manufacturer' => 'Innova',
            'name' => 'Destroyer',
            'plastic' => 'Star',
            'selectedColors' => ['#dc2626'],
            'condition' => 'good',
            'inscription' => 'ABC',
            'location' => 'Tallinn',
            'latitude' => 59.437,
            'longitude' => 24.7536,
        ])
        ->assertRedirect(route('dashboard'));

    $lostDisc = Disc::query()
        ->where('user_id', $lostUser->id)
        ->where('status', 'lost')
        ->latest('id')
        ->firstOrFail();

    expect(MatchThread::query()
        ->where('lost_disc_id', $lostDisc->id)
        ->where('found_disc_id', $foundDisc->id)
        ->where('status', 'pending')
        ->exists())->toBeTrue();
});

test('creating a found disc triggers matching', function () {
    $lostUser = User::factory()->create();
    $foundUser = User::factory()->create();

    $red = Color::query()->firstOrCreate(['name' => 'Red']);

    $lostDisc = Disc::create([
        'user_id' => $lostUser->id,
        'status' => 'lost',
        'occurred_at' => now()->subHours(2),
        'manufacturer' => 'Innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
        'back_text' => 'ABC',
        'condition_estimate' => 'good',
        'active' => true,
        'match_lifecycle' => null,
    ]);
    $lostDisc->colors()->sync([$red->id]);
    Location::create([
        'disc_id' => $lostDisc->id,
        'latitude' => 59.437,
        'longitude' => 24.7536,
        'location_type' => 'lost',
        'location_text' => 'Tallinn',
    ]);

    $this->actingAs($foundUser)
        ->post(route('found-discs.store'), [
            'datetime' => now()->format('Y-m-d\\TH:i'),
            'manufacturer' => 'Innova',
            'name' => 'Destroyer',
            'plastic' => 'Star',
            'selectedColors' => ['#dc2626'],
            'condition' => 'good',
            'inscription' => 'ABC',
            'location' => 'Tallinn',
            'latitude' => 59.437,
            'longitude' => 24.7536,
        ])
        ->assertRedirect(route('dashboard'));

    $foundDisc = Disc::query()
        ->where('user_id', $foundUser->id)
        ->where('status', 'found')
        ->latest('id')
        ->firstOrFail();

    expect(MatchThread::query()
        ->where('lost_disc_id', $lostDisc->id)
        ->where('found_disc_id', $foundDisc->id)
        ->where('status', 'pending')
        ->exists())->toBeTrue();
});
