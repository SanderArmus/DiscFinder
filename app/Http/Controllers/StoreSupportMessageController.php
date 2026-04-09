<?php

namespace App\Http\Controllers;

use App\Models\ChatBlock;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'match_id' => null,
            'content' => $validated['content'],
        ]);

        return redirect()->back();
    }
}
