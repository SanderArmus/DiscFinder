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
    public function index(Request $request): Response
    {
        $authUser = $request->user();
        if ($authUser === null || $authUser->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $q = $validated['q'] ?? null;

        $messages = Message::query()
            ->with(['sender', 'receiver'])
            ->whereNull('match_id')
            ->when($q, function ($query) use ($q) {
                $term = '%'.trim($q).'%';
                $query->where(function ($q2) use ($term) {
                    $q2->where('content', 'like', $term)
                        ->orWhereHas('sender', function ($uq) use ($term) {
                            $uq->where('username', 'like', $term)
                                ->orWhere('name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        })
                        ->orWhereHas('receiver', function ($uq) use ($term) {
                            $uq->where('username', 'like', $term)
                                ->orWhere('name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/SupportMessages', [
            'filters' => [
                'q' => $q,
            ],
            'messages' => $messages->through(fn (Message $message) => [
                'id' => $message->id,
                'content' => $message->content,
                'createdAt' => $message->created_at?->format('Y-m-d H:i:s'),
                'sender' => [
                    'id' => $message->sender?->id,
                    'username' => $message->sender?->username,
                    'name' => $message->sender?->name,
                    'email' => $message->sender?->email,
                    'role' => $message->sender?->role,
                ],
                'receiver' => [
                    'id' => $message->receiver?->id,
                    'username' => $message->receiver?->username,
                    'name' => $message->receiver?->name,
                    'email' => $message->receiver?->email,
                    'role' => $message->receiver?->role,
                ],
            ]),
        ]);
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
