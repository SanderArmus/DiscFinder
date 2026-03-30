<?php

use App\Models\Disc;
use App\Models\Location;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('owner can view disc details, update it, and delete it', function () {
    $owner = User::factory()->create();

    $disc = Disc::create([
        'user_id' => $owner->id,
        'status' => 'lost',
        'occurred_at' => now(),
        'manufacturer' => 'Innova',
        'model_name' => 'Glitch',
        'plastic_type' => 'Neutron',
        'back_text' => 'ABC',
        'condition_estimate' => 'good',
        'active' => true,
        'match_lifecycle' => null,
    ]);

    Location::create([
        'disc_id' => $disc->id,
        'latitude' => 59.437,
        'longitude' => 24.7536,
        'location_type' => 'lost',
        'location_text' => 'Old Location',
    ]);

    $this->actingAs($owner)->get(route('discs.show', ['disc' => $disc->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('DiscDetails')
            ->where('disc.id', $disc->id)
            ->where('canEdit', true));

    $payload = [
        'datetime' => now()->format('Y-m-d\\TH:i'),
        'manufacturer' => 'Innova',
        'name' => 'Glitch 2',
        'plastic' => 'Neutron',
        'selectedColors' => ['#dc2626'],
        'condition' => 'worn',
        'inscription' => 'NEW123',
        'location' => 'New Location',
        'latitude' => 59.5,
        'longitude' => 24.7,
    ];

    $this->actingAs($owner)->post(route('discs.update', ['disc' => $disc->id]), $payload)
        ->assertRedirect(route('discs.show', ['disc' => $disc->id]));

    $disc->refresh();
    expect($disc->model_name)->toBe('Glitch 2');
    expect($disc->condition_estimate)->toBe('worn');
    expect($disc->back_text)->toBe('NEW123');

    $location = Location::where('disc_id', $disc->id)->firstOrFail();
    expect($location->location_text)->toBe('New Location');
    expect((float) $location->latitude)->toBe(59.5);

    $this->actingAs($owner)->delete(route('discs.destroy', ['disc' => $disc->id]))
        ->assertRedirect(route('dashboard'));

    expect(Disc::find($disc->id))->toBeNull();
});

test('disc update is forbidden for non-owners and inactive discs', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $disc = Disc::create([
        'user_id' => $owner->id,
        'status' => 'found',
        'occurred_at' => now(),
        'manufacturer' => 'Innova',
        'model_name' => 'Glitch',
        'plastic_type' => 'Neutron',
        'back_text' => 'ABC',
        'condition_estimate' => 'good',
        'active' => false,
        'match_lifecycle' => 'confirmed',
    ]);

    Location::create([
        'disc_id' => $disc->id,
        'latitude' => 59.437,
        'longitude' => 24.7536,
        'location_type' => 'found',
        'location_text' => 'Some Location',
    ]);

    $payload = [
        'datetime' => now()->format('Y-m-d\\TH:i'),
        'manufacturer' => 'Innova',
        'name' => 'Glitch 2',
        'plastic' => 'Neutron',
        'selectedColors' => ['#dc2626'],
        'condition' => 'worn',
        'inscription' => 'NEW123',
        'location' => 'New Location',
        'latitude' => 59.5,
        'longitude' => 24.7,
    ];

    $this->actingAs($other)->post(route('discs.update', ['disc' => $disc->id]), $payload)
        ->assertForbidden();

    $this->actingAs($owner)->post(route('discs.update', ['disc' => $disc->id]), $payload)
        ->assertForbidden();
});
