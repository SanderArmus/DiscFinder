<?php

use App\Models\ChatReport;
use App\Models\MatchThread;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;

test('report stores last 10 messages snapshot for match context', function () {
    $lostUser = User::factory()->create();
    $foundUser = User::factory()->create();

    $now = Carbon::parse('2026-04-08 12:00:00');

    $lostDisc = \App\Models\Disc::create([
        'status' => 'lost',
        'user_id' => $lostUser->id,
        'occurred_at' => $now->copy()->subDay(),
        'manufacturer' => 'Innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
    ]);

    $foundDisc = \App\Models\Disc::create([
        'status' => 'found',
        'user_id' => $foundUser->id,
        'occurred_at' => $now,
        'manufacturer' => 'Innova',
        'model_name' => 'Destroyer',
        'plastic_type' => 'Star',
    ]);

    $match = MatchThread::create([
        'lost_disc_id' => $lostDisc->id,
        'found_disc_id' => $foundDisc->id,
        'match_score' => 80,
        'status' => null,
    ]);

    for ($i = 1; $i <= 12; $i++) {
        Message::create([
            'sender_id' => $i % 2 ? $lostUser->id : $foundUser->id,
            'receiver_id' => $i % 2 ? $foundUser->id : $lostUser->id,
            'match_id' => $match->id,
            'content' => "msg {$i}",
        ]);
    }

    $this->actingAs($lostUser)
        ->post(route('chat-reports.store'), [
            'context' => 'match',
            'match_id' => $match->id,
            'reason' => 'spam',
            'details' => 'test details',
            'also_block' => false,
        ])
        ->assertRedirect();

    $report = ChatReport::query()->latest('id')->first();
    expect($report)->not->toBeNull();
    expect($report->messages_snapshot)->toBeArray()->and(count($report->messages_snapshot))->toBe(10);
    expect($report->messages_snapshot[0]['content'])->toBe('msg 3');
    expect($report->messages_snapshot[9]['content'])->toBe('msg 12');
});
