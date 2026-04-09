<?php

namespace App\Http\Controllers;

use App\Models\ChatBlock;
use App\Models\MatchThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RejectMatchController extends Controller
{
    public function __invoke(MatchThread $match): RedirectResponse
    {
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        $match->loadMissing(['lostDisc', 'foundDisc']);

        if (
            $user->id !== $match->lostDisc->user_id
            && $user->id !== $match->foundDisc->user_id
        ) {
            abort(403);
        }

        $isOwner = $user->id === $match->lostDisc->user_id;
        $otherUserId = $isOwner ? $match->foundDisc->user_id : $match->lostDisc->user_id;

        $blocked = ChatBlock::query()
            ->where('match_id', $match->id)
            ->where(function ($q) use ($user, $otherUserId) {
                $q->where(function ($q2) use ($user, $otherUserId) {
                    $q2->where('blocker_id', $user->id)->where('blocked_id', $otherUserId);
                })->orWhere(function ($q2) use ($user, $otherUserId) {
                    $q2->where('blocker_id', $otherUserId)->where('blocked_id', $user->id);
                });
            })
            ->exists();

        if ($blocked) {
            abort(403);
        }

        if (in_array($match->status, ['confirmed', 'handed_over'], true)) {
            return redirect()->route('matches.chat', ['match' => $match->id]);
        }

        $match->status = 'rejected';
        $match->save();

        return redirect()->route('dashboard');
    }
}
