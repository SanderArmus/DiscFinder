<?php

namespace App\Http\Controllers\Admin;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSupportMessagesController
{
    public function index(Request $request): RedirectResponse
    {
        $authUser = $request->user();
        if ($authUser === null || $authUser->role !== 'admin') {
            abort(403);
        }

        return redirect()->route('messages.index');
    }

    public function reply(Request $request, Message $message): RedirectResponse
    {
        $authUser = $request->user();
        if ($authUser === null || $authUser->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $message->loadMissing(['sender', 'receiver']);

        $receiver = $message->sender;
        if (! $receiver instanceof User) {
            abort(422);
        }

        Message::create([
            'sender_id' => $authUser->id,
            'receiver_id' => $receiver->id,
            'match_id' => null,
            'content' => $validated['content'],
        ]);

        return redirect()->back()->with('success', 'Reply sent.');
    }
}
