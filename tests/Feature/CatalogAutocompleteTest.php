<?php

use App\Models\Disc;
use App\Models\User;

test('catalog/plastics filters by manufacturer and query', function () {
    $user = User::factory()->create();

    Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'manufacturer' => 'innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'champion',
        'active' => true,
    ]);

    Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'manufacturer' => 'innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'star',
        'active' => true,
    ]);

    Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'manufacturer' => 'discraft',
        'model_name' => 'Buzzz',
        'plastic_type' => 'z',
        'active' => true,
    ]);

    $response = $this->actingAs($user)->getJson('/catalog/plastics?manufacturer=innova&q=champ');

    $response->assertOk();

    $items = $response->json('items');
    expect($items)->toContain('champion');
    expect($items)->not->toContain('z');
});

test('catalog/models depends on manufacturer + plastic query', function () {
    $user = User::factory()->create();

    Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'manufacturer' => 'innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'champion',
        'active' => true,
    ]);

    Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'manufacturer' => 'innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'star',
        'active' => true,
    ]);

    Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'manufacturer' => 'discraft',
        'model_name' => 'Firebird',
        'plastic_type' => 'champion',
        'active' => true,
    ]);

    $response = $this->actingAs($user)->getJson('/catalog/models?manufacturer=innova&plastic=champ&q=dest');

    $response->assertOk();

    $items = $response->json('items');
    expect($items)->toContain('Destroyer');
    expect($items)->not->toContain('Firebird');
});

test('catalog/manufacturers returns distinct manufacturers with query filter', function () {
    $user = User::factory()->create();

    Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'manufacturer' => 'innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'champion',
        'active' => true,
    ]);

    Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'manufacturer' => 'innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'star',
        'active' => true,
    ]);

    Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'manufacturer' => 'discraft',
        'model_name' => 'Buzzz',
        'plastic_type' => 'z',
        'active' => true,
    ]);

    $response = $this->actingAs($user)->getJson('/catalog/manufacturers?q=inn');

    $response->assertOk();

    $items = $response->json('items');

    expect($items)->toContain('innova');
    expect($items)->not->toContain('discraft');
});
