<?php

namespace App\Http\Controllers;

use App\Models\MatchThread;
use App\Models\MatchThreadRead;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ShowMatchChatController extends Controller
{
    public function __invoke(MatchThread $match): Response
    {
        $user = auth()->user();

        if ($user === null) {
            abort(403);
        }

        $match->load([
            'lostDisc.user',
            'foundDisc.user',
            'lostDisc.locations',
            'foundDisc.locations',
            'confirmation',
            'messages' => fn ($q) => $q
                ->orderBy('created_at')
                ->with('sender'),
        ]);

        if (
            $user->id !== $match->lostDisc->user_id
            && $user->id !== $match->foundDisc->user_id
        ) {
            abort(403);
        }

        if (Schema::hasTable('match_thread_reads')) {
            MatchThreadRead::updateOrCreate(
                [
                    'match_id' => $match->id,
                    'user_id' => $user->id,
                ],
                [
                    'last_read_at' => now(),
                ],
            );
        }

        $isLostOwner = $user->id === $match->lostDisc->user_id;
        $displayDisc = $isLostOwner ? $match->foundDisc : $match->lostDisc;
        $displayDiscId = $displayDisc->id;
        $otherUser = $isLostOwner ? $match->foundDisc->user : $match->lostDisc->user;

        $displayName = $displayDisc->model_name
            ?: ($displayDisc->manufacturer ?: '—');

        $ownerConfirmed = $match->confirmation?->owner_confirmed ?? false;
        $finderConfirmed = $match->confirmation?->finder_confirmed ?? false;
        $ownConfirmed = $isLostOwner ? $ownerConfirmed : $finderConfirmed;
        $otherConfirmed = $isLostOwner ? $finderConfirmed : $ownerConfirmed;

        $ownerHandedOver = $match->confirmation?->owner_handed_over ?? false;
        $finderHandedOver = $match->confirmation?->finder_handed_over ?? false;
        $ownHandedOver = $isLostOwner ? $ownerHandedOver : $finderHandedOver;
        $otherHandedOver = $isLostOwner ? $finderHandedOver : $ownerHandedOver;

        $matchStatus = $match->status ?: 'pending';

        $displayDate = $displayDisc->occurred_at !== null
            ? $displayDisc->occurred_at->format('M j, Y')
            : '—';

        $displayLocation = '—';
        if ($displayDisc->locations !== null) {
            if ($displayDisc->locations instanceof \Illuminate\Support\Collection) {
                $textLocation = $displayDisc->locations
                    ->first(fn ($l) => $l->location_text !== null && trim((string) $l->location_text) !== '');

                if ($textLocation !== null && $textLocation->location_text !== null) {
                    $displayLocation = trim((string) $textLocation->location_text);
                } else {
                    $coordsLocation = $displayDisc->locations
                        ->first(fn ($l) => $l->latitude !== null && $l->longitude !== null);

                    if ($coordsLocation !== null) {
                        $displayLocation = sprintf(
                            '%0.4f, %0.4f',
                            (float) $coordsLocation->latitude,
                            (float) $coordsLocation->longitude
                        );
                    }
                }
            }
        }

        return Inertia::render('MatchChat', [
            'match' => [
                'id' => $match->id,
                'name' => $displayName,
            ],
            'messages' => $match->messages->map(fn ($m) => [
                'id' => $m->id,
                'senderId' => $m->sender_id,
                'content' => $m->content,
                'createdAt' => $m->created_at?->format('M j, H:i') ?? '',
            ])->values(),
            'otherUserName' => $otherUser?->name ?? '',
            'authUserId' => $user->id,
            'displayDiscDate' => $displayDate,
            'displayDiscLocation' => $displayLocation,
            'displayDiscId' => $displayDiscId,
            'ownConfirmed' => $ownConfirmed,
            'otherConfirmed' => $otherConfirmed,
            'ownHandedOver' => $ownHandedOver,
            'otherHandedOver' => $otherHandedOver,
            'matchStatus' => $matchStatus,
        ]);
    }
}
