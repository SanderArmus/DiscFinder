<?php

namespace App\Http\Controllers;

use App\Models\ChatBlock;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowSupportChatController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $admin = User::query()
            ->where('role', '=', 'admin')
            ->orderBy('id')
            ->first();

        if ($admin === null) {
            abort(404);
        }

        $blocked = ChatBlock::query()
            ->whereNull('match_id')
            ->where(function ($q) use ($user, $admin) {
                $q->where(function ($q2) use ($user, $admin) {
                    $q2->where('blocker_id', $user->id)->where('blocked_id', $admin->id);
                })->orWhere(function ($q2) use ($user, $admin) {
                    $q2->where('blocker_id', $admin->id)->where('blocked_id', $user->id);
                });
            })
            ->exists();

        $messages = Message::query()
            ->whereNull('match_id')
            ->where(function ($q) use ($user, $admin) {
                $q->where(function ($q2) use ($user, $admin) {
                    $q2->where('sender_id', $user->id)->where('receiver_id', $admin->id);
                })->orWhere(function ($q2) use ($user, $admin) {
                    $q2->where('sender_id', $admin->id)->where('receiver_id', $user->id);
                });
            })
            ->orderBy('created_at')
            ->get();

        $otherUserName = $admin->username ?: ($admin->name ?: 'Admin');

        return Inertia::render('SupportChat', [
            'otherUserName' => $otherUserName,
            'authUserId' => $user->id,
            'messages' => $messages->map(fn (Message $m) => [
                'id' => $m->id,
                'senderId' => $m->sender_id,
                'content' => $m->content ?? '',
                'createdAt' => $m->created_at?->format('M j, H:i') ?? '',
            ])->values(),
            'chatBlocked' => $blocked,
        ]);
    }
}
