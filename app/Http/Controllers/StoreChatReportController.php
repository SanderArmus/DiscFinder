<?php

namespace App\Http\Controllers;

use App\Models\ChatBlock;
use App\Models\ChatReport;
use App\Models\MatchThread;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreChatReportController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $reporter = $request->user();
        if ($reporter === null) {
            abort(403);
        }

        $validated = $request->validate([
            'context' => ['required', 'string', 'max:20'], // match|support
            'match_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'in:harassment,spam,scam,other'],
            'details' => ['nullable', 'string', 'max:2000'],
            'also_block' => ['nullable', 'boolean'],
        ]);

        $context = $validated['context'];
        $matchId = $context === 'match' ? (int) ($validated['match_id'] ?? 0) : null;

        if ($context === 'match') {
            $match = MatchThread::query()->whereKey($matchId)->first();
            if (! $match instanceof MatchThread) {
                abort(404);
            }

            $match->loadMissing(['lostDisc', 'foundDisc']);

            if (
                $reporter->id !== $match->lostDisc->user_id
                && $reporter->id !== $match->foundDisc->user_id
            ) {
                abort(403);
            }

            $reportedId = $reporter->id === $match->lostDisc->user_id
                ? $match->foundDisc->user_id
                : $match->lostDisc->user_id;

            $snapshotMessages = Message::query()
                ->where('match_id', $match->id)
                ->latest('id')
                ->limit(10)
                ->get()
                ->reverse()
                ->values();

            $lastMessage = $snapshotMessages->last();

            $snapshot = $snapshotMessages->map(fn (Message $m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'receiver_id' => $m->receiver_id,
                'created_at' => $m->created_at?->toISOString(),
                'content' => $m->content,
            ])->all();

            ChatReport::create([
                'reporter_id' => $reporter->id,
                'reported_id' => $reportedId,
                'match_id' => $match->id,
                'reason' => $validated['reason'],
                'details' => $validated['details'] ?? null,
                'last_message_preview' => $lastMessage?->content,
                'last_message_at' => $lastMessage?->created_at,
                'messages_snapshot' => $snapshot,
            ]);

            if ((bool) ($validated['also_block'] ?? true)) {
                ChatBlock::firstOrCreate([
                    'blocker_id' => $reporter->id,
                    'blocked_id' => $reportedId,
                    'match_id' => $match->id,
                ]);
            }

            return redirect()->route('matches.chat', ['match' => $match->id]);
        }

        // support context
        $admin = User::query()->where('role', '=', 'admin')->orderBy('id')->first();
        if (! $admin instanceof User) {
            abort(404);
        }

        $snapshotMessages = Message::query()
            ->whereNull('match_id')
            ->where(function ($q) use ($reporter, $admin) {
                $q->where(function ($q2) use ($reporter, $admin) {
                    $q2->where('sender_id', $reporter->id)->where('receiver_id', $admin->id);
                })->orWhere(function ($q2) use ($reporter, $admin) {
                    $q2->where('sender_id', $admin->id)->where('receiver_id', $reporter->id);
                });
            })
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        $lastMessage = $snapshotMessages->last();

        $snapshot = $snapshotMessages->map(fn (Message $m) => [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'receiver_id' => $m->receiver_id,
            'created_at' => $m->created_at?->toISOString(),
            'content' => $m->content,
        ])->all();

        ChatReport::create([
            'reporter_id' => $reporter->id,
            'reported_id' => $admin->id,
            'match_id' => null,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'last_message_preview' => $lastMessage?->content,
            'last_message_at' => $lastMessage?->created_at,
            'messages_snapshot' => $snapshot,
        ]);

        if ((bool) ($validated['also_block'] ?? true)) {
            ChatBlock::firstOrCreate([
                'blocker_id' => $reporter->id,
                'blocked_id' => $admin->id,
                'match_id' => null,
            ]);
        }

        return redirect()->route('support.chat');
    }
}
