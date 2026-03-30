<?php

namespace App\Services;

use App\Models\MatchThread;
use App\Models\User;
use Illuminate\Support\Str;

final class MatchChatFinder
{
    /**
     * @return array<int, array{
     *     id: int,
     *     discName: string,
     *     otherDiscDate: string,
     *     otherDiscLocation: string,
     *     otherUserName: string,
     *     matchStatus: string,
     *     otherConfirmed: bool,
     *     otherHandedOver: bool,
     *     lastMessagePreview: string,
     *     lastMessageAt: string
     * }>
     */
    public function findThreadsForUser(User $user, int $limit = 10): array
    {
        $userId = $user->id;

        $threads = MatchThread::query()
            ->where(function ($q) use ($userId) {
                $q->whereHas('lostDisc', fn ($dQ) => $dQ->where('user_id', $userId))
                    ->orWhereHas('foundDisc', fn ($dQ) => $dQ->where('user_id', $userId));
            })
            ->whereHas('messages')
            ->with([
                'lostDisc.user:id,name',
                'foundDisc.user:id,name',
                'lostDisc.locations',
                'foundDisc.locations',
                'confirmation',
                'messages' => fn ($mQ) => $mQ
                    ->latest('created_at')
                    ->limit(1)
                    ->with('sender:id,name'),
            ])
            ->latest('id')
            ->limit($limit)
            ->get();

        return $threads->map(function (MatchThread $thread) use ($userId): array {
            $isLostOwner = $thread->lostDisc?->user_id === $userId;
            $otherDisc = $isLostOwner ? $thread->foundDisc : $thread->lostDisc;
            $otherUser = $isLostOwner ? $thread->foundDisc?->user : $thread->lostDisc?->user;
            $confirmation = $thread->confirmation;

            $label = $this->discLabel($otherDisc);
            $otherUserName = $otherUser?->name ?? '';

            $otherDiscDate = $this->discDateLabel($otherDisc);
            $otherDiscLocation = $this->discLocationLabel($otherDisc);

            $matchStatus = $thread->status ?: 'pending';

            $ownerConfirmed = $confirmation?->owner_confirmed ?? false;
            $finderConfirmed = $confirmation?->finder_confirmed ?? false;
            $ownerHandedOver = $confirmation?->owner_handed_over ?? false;
            $finderHandedOver = $confirmation?->finder_handed_over ?? false;

            $otherConfirmed = $isLostOwner ? $finderConfirmed : $ownerConfirmed;
            $otherHandedOver = $isLostOwner ? $finderHandedOver : $ownerHandedOver;

            $lastMessage = $thread->messages->first();
            $lastMessageContent = $lastMessage?->content ?? '';
            $lastMessagePreview = $lastMessageContent !== ''
                ? (Str::length($lastMessageContent) > 80
                    ? Str::limit($lastMessageContent, 80, '...')
                    : $lastMessageContent)
                : '';

            $lastMessageAt = $lastMessage?->created_at?->format('M j, H:i') ?? '';

            return [
                'id' => $thread->id,
                'discName' => $label,
                'otherDiscDate' => $otherDiscDate,
                'otherDiscLocation' => $otherDiscLocation,
                'otherUserName' => $otherUserName,
                'matchStatus' => $matchStatus,
                'otherConfirmed' => $otherConfirmed,
                'otherHandedOver' => $otherHandedOver,
                'lastMessagePreview' => $lastMessagePreview,
                'lastMessageAt' => $lastMessageAt,
            ];
        })->values()->all();
    }

    private function discLabel(?\App\Models\Disc $disc): string
    {
        if ($disc === null) {
            return '—';
        }

        return $disc->model_name ?: ($disc->manufacturer ?: '—');
    }

    private function discDateLabel(?\App\Models\Disc $disc): string
    {
        if ($disc === null || $disc->occurred_at === null) {
            return '—';
        }

        return $disc->occurred_at->format('M j, Y');
    }

    private function discLocationLabel(?\App\Models\Disc $disc): string
    {
        if ($disc === null) {
            return '—';
        }

        if ($disc->locations instanceof \Illuminate\Support\Collection) {
            $textLocation = $disc->locations
                ->first(fn ($l) => $l->location_text !== null && trim((string) $l->location_text) !== '');

            if ($textLocation !== null && $textLocation->location_text !== null) {
                $label = trim((string) $textLocation->location_text);
                if ($label !== '') {
                    return $label;
                }
            }

            $location = $disc->locations->first(
                fn ($l) => $l->latitude !== null && $l->longitude !== null,
            );

            if ($location !== null) {
                return sprintf(
                    '%0.4f, %0.4f',
                    (float) $location->latitude,
                    (float) $location->longitude,
                );
            }
        }

        return '—';
    }
}
