<?php

namespace App\Http\Controllers;

use App\Models\ChatBlock;
use App\Models\Confirmation;
use App\Models\MatchThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ConfirmMatchController extends Controller
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

        $confirmation = Confirmation::query()->firstOrCreate([
            'match_id' => $match->id,
        ]);

        if ($isOwner) {
            $confirmation->owner_confirmed = true;
        } else {
            $confirmation->finder_confirmed = true;
        }

        $bothConfirmed = $confirmation->owner_confirmed && $confirmation->finder_confirmed;

        if ($bothConfirmed) {
            $confirmation->confirmed_at = now();
            $match->status = 'confirmed';

            $match->lostDisc->active = false;
            $match->lostDisc->match_lifecycle = 'confirmed';
            $match->lostDisc->save();

            $match->foundDisc->active = false;
            $match->foundDisc->match_lifecycle = 'confirmed';
            $match->foundDisc->save();
        } else {
            if ($match->status === null) {
                $match->status = 'pending';
            }
        }

        $confirmation->save();
        $match->save();

        return redirect()->route('matches.chat', ['match' => $match->id]);
    }
}
