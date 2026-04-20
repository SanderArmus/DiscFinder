<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatchMessageRequest;
use App\Models\ChatBlock;
use App\Models\MatchThread;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

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

        $blocked = ChatBlock::query()
            ->where('match_id', $match->id)
            ->where(function ($q) use ($user, $receiver) {
                $q->where(function ($q2) use ($user, $receiver) {
                    $q2->where('blocker_id', $user->id)->where('blocked_id', $receiver->id);
                })->orWhere(function ($q2) use ($user, $receiver) {
                    $q2->where('blocker_id', $receiver->id)->where('blocked_id', $user->id);
                });
            })
            ->exists();

        if ($blocked) {
            abort(403);
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'match_id' => $match->id,
            'content' => $request->validated('content'),
        ]);

        if ($receiver instanceof User && $receiver->email_notify_new_message) {
            $cacheKey = "email_notify:new_message:match:{$match->id}:receiver:{$receiver->id}";
            if (! Cache::has($cacheKey)) {
                $senderName = $user->username ?: ($user->name ?: ($user->email ?: "User #{$user->id}"));
                $receiver->notify(new NewMessageEmailNotification(
                    $message,
                    $senderName,
                    "/matches/{$match->id}"
                ));

                Cache::put($cacheKey, true, now()->addMinutes(10));
            }
        }

        return redirect()->route('matches.chat', ['match' => $match->id]);
    }
}
