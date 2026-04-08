<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

test('disc submissions are rate limited per user', function () {
    RateLimiter::for('disc-submissions', function (Request $request) {
        $user = $request->user();
        $key = $user ? 'test-user:'.$user->id : 'test-ip:'.$request->ip();

        return Limit::perMinute(2)->by($key);
    });

    $user = \App\Models\User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $payload = [
        'datetime' => now()->subHour()->format('Y-m-d\\TH:i'),
        'selectedColors' => ['#ea580c'],
        'condition' => 'good',
        'location' => 'Tallinn',
        'latitude' => 59.437,
        'longitude' => 24.7536,
    ];

    $this->actingAs($user)->post(route('lost-discs.store'), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('lost-discs.store'), $payload)->assertRedirect();

    $this->actingAs($user)
        ->post(route('lost-discs.store'), $payload)
        ->assertStatus(429);
});
