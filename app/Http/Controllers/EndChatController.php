<?php

namespace App\Http\Controllers;

use App\Models\ChatBlock;
use App\Models\MatchThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EndChatController extends Controller
{
    public function __invoke(Request $request, MatchThread $match): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $match->loadMissing(['lostDisc.user', 'foundDisc.user']);

        if (
            $user->id !== $match->lostDisc->user_id
            && $user->id !== $match->foundDisc->user_id
        ) {
            abort(403);
        }

        $otherUserId = $user->id === $match->lostDisc->user_id
            ? $match->foundDisc->user_id
            : $match->lostDisc->user_id;

        ChatBlock::firstOrCreate([
            'blocker_id' => $user->id,
            'blocked_id' => $otherUserId,
            'match_id' => $match->id,
        ]);

        return redirect()->route('messages.index');
    }
}
