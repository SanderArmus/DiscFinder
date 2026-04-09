<?php

namespace App\Http\Controllers;

use App\Models\ChatBlock;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EndSupportChatController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
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

        ChatBlock::firstOrCreate([
            'blocker_id' => $user->id,
            'blocked_id' => $admin->id,
            'match_id' => null,
        ]);

        return redirect()->route('messages.index');
    }
}
