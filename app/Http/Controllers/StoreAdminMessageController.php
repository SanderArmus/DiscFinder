<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminMessageRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

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

        Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $admin->id,
            'match_id' => null,
            'content' => $request->validated('content'),
        ]);

        return back()->with('success', 'Message sent to admin.');
    }
}
