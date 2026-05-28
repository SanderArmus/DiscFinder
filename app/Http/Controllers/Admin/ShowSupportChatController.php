<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChatBlock;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowSupportChatController
{
    public function __invoke(Request $request, User $user): Response
    {
        $admin = $request->user();
        if ($admin === null || $admin->role !== 'admin') {
            abort(403);
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

        $otherUserName = $user->username ?: ($user->name ?: ($user->email ?: "User #{$user->id}"));

        return Inertia::render('Admin/SupportChat', [
            'receiverId' => $user->id,
            'otherUserName' => $otherUserName,
            'authUserId' => $admin->id,
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

