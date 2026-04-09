<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatReportsController extends Controller
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

        $reports = ChatReport::query()
            ->with(['reporter', 'reported'])
            ->when($q, function ($query) use ($q) {
                $term = '%'.trim($q).'%';
                $query->where(function ($q2) use ($term) {
                    $q2->where('reason', 'like', $term)
                        ->orWhere('details', 'like', $term)
                        ->orWhere('last_message_preview', 'like', $term)
                        ->orWhereHas('reporter', function ($uq) use ($term) {
                            $uq->where('username', 'like', $term)
                                ->orWhere('name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        })
                        ->orWhereHas('reported', function ($uq) use ($term) {
                            $uq->where('username', 'like', $term)
                                ->orWhere('name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/ChatReports', [
            'filters' => [
                'q' => $q,
            ],
            'reports' => $reports->through(fn (ChatReport $r) => [
                'id' => $r->id,
                'context' => $r->match_id ? 'match' : 'support',
                'matchId' => $r->match_id,
                'reason' => $r->reason,
                'details' => $r->details,
                'lastMessagePreview' => $r->last_message_preview,
                'lastMessageAt' => $r->last_message_at?->format('Y-m-d H:i:s'),
                'createdAt' => $r->created_at?->format('Y-m-d H:i:s'),
                'messagesSnapshot' => $r->messages_snapshot,
                'reporter' => [
                    'id' => $r->reporter?->id,
                    'username' => $r->reporter?->username,
                    'name' => $r->reporter?->name,
                    'email' => $r->reporter?->email,
                ],
                'reported' => [
                    'id' => $r->reported?->id,
                    'username' => $r->reported?->username,
                    'name' => $r->reported?->name,
                    'email' => $r->reported?->email,
                ],
            ]),
        ]);
    }
}
