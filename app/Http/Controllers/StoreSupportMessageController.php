<?php

namespace App\Http\Controllers;

use App\Models\ChatBlock;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StoreSupportMessageController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $sender = $request->user();
        if ($sender === null) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $admin = User::query()
            ->where('role', '=', 'admin')
            ->orderBy('id')
            ->first();

        if ($admin === null) {
            return back()
                ->withErrors(['content' => 'No admin is configured.'])
                ->withInput();
        }

        $blocked = ChatBlock::query()
            ->whereNull('match_id')
            ->where(function ($q) use ($sender, $admin) {
                $q->where(function ($q2) use ($sender, $admin) {
                    $q2->where('blocker_id', $sender->id)->where('blocked_id', $admin->id);
                })->orWhere(function ($q2) use ($sender, $admin) {
                    $q2->where('blocker_id', $admin->id)->where('blocked_id', $sender->id);
                });
            })
            ->exists();

        if ($blocked) {
            abort(403);
        }

        $receiverId = $sender->role === 'admin'
            ? (int) $request->input('receiver_id')
            : $admin->id;

        if ($sender->role === 'admin') {
            $receiver = User::query()->whereKey($receiverId)->first();
            if ($receiver === null) {
                abort(422);
            }
        }

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'match_id' => null,
            'content' => $validated['content'],
        ]);

        $receiver = User::query()->whereKey($receiverId)->first();
        if ($receiver !== null && $receiver->email_notify_new_message) {
            $cacheKey = "email_notify:new_message:support:receiver:{$receiver->id}:sender:{$sender->id}";
            if (! Cache::has($cacheKey)) {
                $senderName = $sender->username ?: ($sender->name ?: ($sender->email ?: "User #{$sender->id}"));
                $receiver->notify(new NewMessageEmailNotification(
                    $message,
                    $senderName,
                    $receiver->role === 'admin' ? '/admin/support-messages' : '/support/chat'
                ));
                Cache::put($cacheKey, true, now()->addMinutes(10));
            }
        }

        return redirect()->back();
    }
}
