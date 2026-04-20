<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminMessageRequest;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

class StoreAdminMessageController extends Controller
{
    public function __invoke(StoreAdminMessageRequest $request): RedirectResponse
    {
        $sender = $request->user();

        if ($sender === null) {
            abort(403);
        }

        $admin = User::query()
            ->where('role', '=', 'admin')
            ->orderBy('id')
            ->first();

        if ($admin === null) {
            return back()
                ->withErrors(['content' => 'No admin is configured.'])
                ->withInput();
        }

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $admin->id,
            'match_id' => null,
            'content' => $request->validated('content'),
        ]);

        if ($admin->email_notify_new_message) {
            $cacheKey = "email_notify:new_message:help:receiver:{$admin->id}:sender:{$sender->id}";
            if (! Cache::has($cacheKey)) {
                $senderName = $sender->username ?: ($sender->name ?: ($sender->email ?: "User #{$sender->id}"));
                $admin->notify(new NewMessageEmailNotification(
                    $message,
                    $senderName,
                    '/admin/support-messages'
                ));
                Cache::put($cacheKey, true, now()->addMinutes(10));
            }
        }

        return back()->with('success', 'Message sent to admin.');
    }
}
