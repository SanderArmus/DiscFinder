<?php

namespace App\Http\Controllers;

use App\Models\Confirmation;
use App\Models\MatchThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class HandOverMatchController extends Controller
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

        $confirmation = Confirmation::query()->firstOrCreate([
            'match_id' => $match->id,
        ]);

        $bothConfirmed = $confirmation->owner_confirmed && $confirmation->finder_confirmed;

        if (! $bothConfirmed) {
            abort(403);
        }

        if ($isOwner) {
            $confirmation->owner_handed_over = true;
        } else {
            $confirmation->finder_handed_over = true;
        }

        $bothHandedOver = $confirmation->owner_handed_over && $confirmation->finder_handed_over;

        if ($bothHandedOver) {
            $confirmation->handed_over_at = now();
            $match->status = 'handed_over';

            $match->lostDisc->match_lifecycle = 'handed_over';
            $match->lostDisc->active = false;
            $match->lostDisc->save();

            $match->foundDisc->match_lifecycle = 'handed_over';
            $match->foundDisc->active = false;
            $match->foundDisc->save();
        }

        $confirmation->save();
        $match->save();

        return redirect()->route('matches.chat', ['match' => $match->id]);
    }
}
