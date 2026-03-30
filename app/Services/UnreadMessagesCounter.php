<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class UnreadMessagesCounter
{
    public function countForUser(User $user): int
    {
        if (! Schema::hasTable('match_thread_reads')) {
            return 0;
        }

        $query = Message::query()
            ->from('messages')
            ->leftJoin('match_thread_reads as mtr', function ($join) {
                $join->on('mtr.match_id', '=', 'messages.match_id');
                $join->on('mtr.user_id', '=', 'messages.receiver_id');
            })
            ->where('messages.receiver_id', $user->id)
            ->where(static function (Builder $q) {
                $q->whereNull('mtr.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'mtr.last_read_at');
            });

        return (int) $query->count('messages.id');
    }
}
