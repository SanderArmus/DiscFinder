<?php

use App\Models\Disc;
use App\Models\User;

test('dashboard passes matchLifecycle for confirmed/handed_over discs', function () {
    $user = User::factory()->create();

    Disc::create([
        'user_id' => $user->id,
        'status' => 'lost',
        'match_lifecycle' => 'confirmed',
        'occurred_at' => now(),
        'condition_estimate' => 'good',
        'active' => false,
        'manufacturer' => 'Innova',
        'model_name' => 'Glitch',
        'plastic_type' => 'Neutron',
        'back_text' => 'ABC',
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(
        fn ($page) => $page->where('discs.0.matchLifecycle', 'confirmed'),
    );
});
