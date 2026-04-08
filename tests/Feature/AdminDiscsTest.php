<?php

use App\Models\Disc;
use App\Models\MatchThread;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('non-admin users cannot access admin discs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.discs.index'))
        ->assertForbidden();
});

test('admin users can view all discs', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();

    $owner = User::factory()->create();
    $foundDisc = Disc::create([
        'user_id' => $owner->id,
        'status' => 'found',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
        'condition_estimate' => 'good',
        'active' => true,
    ]);
    $foundDisc->forceFill([
        'created_at' => now()->subMinutes(10),
        'updated_at' => now()->subMinutes(10),
    ])->save();

    $disc = Disc::create([
        'user_id' => $owner->id,
        'status' => 'lost',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
        'condition_estimate' => 'good',
        'active' => true,
    ]);

    $match = MatchThread::create([
        'lost_disc_id' => $disc->id,
        'found_disc_id' => $foundDisc->id,
        'status' => 'confirmed',
        'match_score' => 0.9,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.discs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Discs')
            ->where('discs.data.0.id', $disc->id)
            ->where('matches.data.0.id', $match->id)
            ->where('matches.data.0.status', 'confirmed'));
});

test('admin can update disc status', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();

    $owner = User::factory()->create();
    $disc = Disc::create([
        'user_id' => $owner->id,
        'status' => 'lost',
        'model_name' => 'Destroyer',
        'condition_estimate' => 'good',
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.discs.update', ['disc' => $disc->id]), [
            'status' => 'found',
        ])
        ->assertRedirect();

    expect($disc->fresh()->status)->toBe('found');
});

test('admin can update match status', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();

    $owner = User::factory()->create();

    $lostDisc = Disc::create([
        'user_id' => $owner->id,
        'status' => 'lost',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
        'condition_estimate' => 'good',
        'active' => true,
        'match_lifecycle' => null,
    ]);

    $foundDisc = Disc::create([
        'user_id' => $owner->id,
        'status' => 'found',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
        'condition_estimate' => 'good',
        'active' => true,
        'match_lifecycle' => null,
    ]);

    $match = MatchThread::create([
        'lost_disc_id' => $lostDisc->id,
        'found_disc_id' => $foundDisc->id,
        'status' => null,
        'match_score' => 0.9,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.matches.update', ['match' => $match->id]), [
            'status' => 'confirmed',
        ])
        ->assertRedirect();

    $match->refresh();

    expect($match->status)->toBe('confirmed');
    expect($lostDisc->fresh()->active)->toBeFalse();
    expect($lostDisc->fresh()->match_lifecycle)->toBe('confirmed');
    expect($foundDisc->fresh()->active)->toBeFalse();
    expect($foundDisc->fresh()->match_lifecycle)->toBe('confirmed');

    $this->actingAs($admin)
        ->patch(route('admin.matches.update', ['match' => $match->id]), [
            'status' => 'rejected',
        ])
        ->assertRedirect();

    $match->refresh();
    expect($match->status)->toBe('rejected');

    // Reject should not reactivate or change discs.
    expect($lostDisc->fresh()->active)->toBeFalse();
    expect($lostDisc->fresh()->match_lifecycle)->toBe('confirmed');

    $this->actingAs($admin)
        ->patch(route('admin.matches.update', ['match' => $match->id]), [
            'status' => null,
        ])
        ->assertRedirect();

    $match->refresh();

    expect($match->status)->toBeNull();
    expect($lostDisc->fresh()->active)->toBeTrue();
    expect($lostDisc->fresh()->match_lifecycle)->toBeNull();
});
