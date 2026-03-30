<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatchMessageRequest;
use App\Models\MatchThread;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;

class StoreMatchMessageController extends Controller
{
    public function __invoke(StoreMatchMessageRequest $request, MatchThread $match): RedirectResponse
    {
        $user = $request->user();

        $match->loadMissing(['lostDisc.user', 'foundDisc.user']);

        if ($user === null) {
            abort(403);
        }

        if (
            $user->id !== $match->lostDisc->user_id
            && $user->id !== $match->foundDisc->user_id
        ) {
            abort(403);
        }

        $receiver = $user->id === $match->lostDisc->user_id
            ? $match->foundDisc->user
            : $match->lostDisc->user;

        Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'match_id' => $match->id,
            'content' => $request->validated('content'),
        ]);

        return redirect()->route('matches.chat', ['match' => $match->id]);
    }
}
