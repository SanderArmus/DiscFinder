<?php

namespace App\Http\Controllers;

use App\Models\ChatBlock;
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

        $blocked = false;
        if ($otherUser !== null) {
            $blocked = ChatBlock::query()
                ->where('match_id', $match->id)
                ->where(function ($q) use ($user, $otherUser) {
                    $q->where(function ($q2) use ($user, $otherUser) {
                        $q2->where('blocker_id', $user->id)->where('blocked_id', $otherUser->id);
                    })->orWhere(function ($q2) use ($user, $otherUser) {
                        $q2->where('blocker_id', $otherUser->id)->where('blocked_id', $user->id);
                    });
                })
                ->exists();
        }

        $displayName = $displayDisc->model_name
            ? trim(implode(' ', array_filter([
                $displayDisc->plastic_type,
                $displayDisc->model_name,
            ])))
            : ($displayDisc->manufacturer ?: '—');

        $ownerConfirmed = $match->confirmation?->owner_confirmed ?? false;
        $finderConfirmed = $match->confirmation?->finder_confirmed ?? false;
        $ownConfirmed = $isLostOwner ? $ownerConfirmed : $finderConfirmed;
        $otherConfirmed = $isLostOwner ? $finderConfirmed : $ownerConfirmed;

        $ownerHandedOver = $match->confirmation?->owner_handed_over ?? false;
        $finderHandedOver = $match->confirmation?->finder_handed_over ?? false;
        $ownHandedOver = $isLostOwner ? $ownerHandedOver : $finderHandedOver;
        $otherHandedOver = $isLostOwner ? $finderHandedOver : $ownerHandedOver;

        $matchStatus = $match->status ?: 'pending';

        $formatDiscSummary = function ($disc): array {
            $name = $disc->model_name
                ? trim(implode(' ', array_filter([
                    $disc->plastic_type,
                    $disc->model_name,
                ])))
                : ($disc->manufacturer ?: '—');

            $date = $disc->occurred_at !== null
                ? $disc->occurred_at->format('M j, Y')
                : '—';

            $location = '—';
            if ($disc->locations instanceof \Illuminate\Support\Collection) {
                $textLocation = $disc->locations
                    ->first(fn ($l) => $l->location_text !== null && trim((string) $l->location_text) !== '');

                if ($textLocation !== null && $textLocation->location_text !== null) {
                    $location = trim((string) $textLocation->location_text);
                } else {
                    $coordsLocation = $disc->locations
                        ->first(fn ($l) => $l->latitude !== null && $l->longitude !== null);

                    if ($coordsLocation !== null) {
                        $location = sprintf(
                            '%0.4f, %0.4f',
                            (float) $coordsLocation->latitude,
                            (float) $coordsLocation->longitude
                        );
                    }
                }
            }

            $owner = $disc->user;
            $ownerName = $owner?->username ?: ($owner?->name ?? '');

            return [
                'id' => $disc->id,
                'name' => $name,
                'ownerName' => $ownerName,
                'date' => $date,
                'location' => $location,
            ];
        };

        $displayDate = $displayDisc->occurred_at !== null
            ? $displayDisc->occurred_at->format('M j, Y')
            : '—';

        $displayLocation = $formatDiscSummary($displayDisc)['location'];

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
            'lostDisc' => $formatDiscSummary($match->lostDisc),
            'foundDisc' => $formatDiscSummary($match->foundDisc),
            'ownConfirmed' => $ownConfirmed,
            'otherConfirmed' => $otherConfirmed,
            'ownHandedOver' => $ownHandedOver,
            'otherHandedOver' => $otherHandedOver,
            'matchStatus' => $matchStatus,
            'chatBlocked' => $blocked,
        ]);
    }
}
