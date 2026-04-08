<?php

namespace App\Http\Controllers\Admin;

use App\Models\Disc;
use App\Models\MatchThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminDiscsController
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null || $user->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['lost', 'found'])],
            'active' => ['nullable', 'string', Rule::in(['1', '0'])],
            'lifecycle' => ['nullable', 'string', Rule::in(['confirmed', 'handed_over'])],
            'sort' => ['nullable', 'string', Rule::in(['created_at', 'occurred_at', 'id'])],
            'dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        $q = $validated['q'] ?? null;
        $status = $validated['status'] ?? null;
        $active = $validated['active'] ?? null;
        $lifecycle = $validated['lifecycle'] ?? null;
        $sort = $validated['sort'] ?? 'created_at';
        $dir = $validated['dir'] ?? 'desc';

        $query = Disc::query()
            ->with(['user', 'colors'])
            ->when($q, function ($query) use ($q) {
                $term = '%'.trim($q).'%';
                $query->where(function ($q2) use ($term) {
                    $q2->where('manufacturer', 'like', $term)
                        ->orWhere('model_name', 'like', $term)
                        ->orWhere('plastic_type', 'like', $term)
                        ->orWhere('back_text', 'like', $term)
                        ->orWhereHas('user', function ($uq) use ($term) {
                            $uq->where('name', 'like', $term)
                                ->orWhere('username', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            })
            ->when($status, fn ($q2) => $q2->where('status', $status))
            ->when($active !== null, fn ($q2) => $q2->where('active', (bool) ((int) $active)))
            ->when($lifecycle, fn ($q2) => $q2->where('match_lifecycle', $lifecycle))
            ->orderBy($sort, $dir);

        $discs = $query->paginate(25)->withQueryString();

        $matchesQuery = MatchThread::query()
            ->with([
                'lostDisc.user',
                'lostDisc.colors',
                'foundDisc.user',
                'foundDisc.colors',
            ])
            ->when($q, function ($query) use ($q) {
                $term = '%'.trim($q).'%';

                $query->where(function ($q2) use ($term) {
                    $q2->whereHas('lostDisc', function ($dq) use ($term) {
                        $dq->where('manufacturer', 'like', $term)
                            ->orWhere('model_name', 'like', $term)
                            ->orWhere('plastic_type', 'like', $term)
                            ->orWhere('back_text', 'like', $term)
                            ->orWhereHas('user', function ($uq) use ($term) {
                                $uq->where('name', 'like', $term)
                                    ->orWhere('username', 'like', $term)
                                    ->orWhere('email', 'like', $term);
                            });
                    })->orWhereHas('foundDisc', function ($dq) use ($term) {
                        $dq->where('manufacturer', 'like', $term)
                            ->orWhere('model_name', 'like', $term)
                            ->orWhere('plastic_type', 'like', $term)
                            ->orWhere('back_text', 'like', $term)
                            ->orWhereHas('user', function ($uq) use ($term) {
                                $uq->where('name', 'like', $term)
                                    ->orWhere('username', 'like', $term)
                                    ->orWhere('email', 'like', $term);
                            });
                    });
                });
            })
            ->orderByDesc('created_at');

        $matches = $matchesQuery->paginate(25)->withQueryString();

        return Inertia::render('Admin/Discs', [
            'filters' => [
                'q' => $q,
                'status' => $status,
                'active' => $active,
                'lifecycle' => $lifecycle,
                'sort' => $sort,
                'dir' => $dir,
            ],
            'discs' => $discs->through(fn (Disc $disc) => [
                'id' => $disc->id,
                'status' => $disc->status,
                'occurredAt' => $disc->occurred_at?->format('Y-m-d H:i:s'),
                'manufacturer' => $disc->manufacturer,
                'modelName' => $disc->model_name,
                'plasticType' => $disc->plastic_type,
                'backText' => $disc->back_text,
                'conditionEstimate' => $disc->condition_estimate,
                'active' => (bool) $disc->active,
                'matchLifecycle' => $disc->match_lifecycle,
                'colors' => $disc->colors->pluck('name')->values(),
                'owner' => [
                    'id' => $disc->user?->id,
                    'name' => $disc->user?->name,
                    'username' => $disc->user?->username,
                    'email' => $disc->user?->email,
                ],
                'createdAt' => $disc->created_at?->format('Y-m-d H:i:s'),
            ]),
            'matches' => $matches->through(fn (MatchThread $match) => [
                'id' => $match->id,
                'status' => $match->status,
                'matchScore' => $match->match_score,
                'createdAt' => $match->created_at?->format('Y-m-d H:i:s'),
                'lostDisc' => [
                    'id' => $match->lostDisc?->id,
                    'manufacturer' => $match->lostDisc?->manufacturer,
                    'modelName' => $match->lostDisc?->model_name,
                    'plasticType' => $match->lostDisc?->plastic_type,
                    'colors' => $match->lostDisc?->colors?->pluck('name')->values() ?? [],
                    'owner' => [
                        'id' => $match->lostDisc?->user?->id,
                        'name' => $match->lostDisc?->user?->name,
                        'username' => $match->lostDisc?->user?->username,
                        'email' => $match->lostDisc?->user?->email,
                    ],
                ],
                'foundDisc' => [
                    'id' => $match->foundDisc?->id,
                    'manufacturer' => $match->foundDisc?->manufacturer,
                    'modelName' => $match->foundDisc?->model_name,
                    'plasticType' => $match->foundDisc?->plastic_type,
                    'colors' => $match->foundDisc?->colors?->pluck('name')->values() ?? [],
                    'owner' => [
                        'id' => $match->foundDisc?->user?->id,
                        'name' => $match->foundDisc?->user?->name,
                        'username' => $match->foundDisc?->user?->username,
                        'email' => $match->foundDisc?->user?->email,
                    ],
                ],
            ]),
        ]);
    }

    public function update(Request $request, Disc $disc): RedirectResponse
    {
        $user = $request->user();
        if ($user === null || $user->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['lost', 'found'])],
            'active' => ['nullable', 'boolean'],
            'matchLifecycle' => ['nullable', 'string', Rule::in(['confirmed', 'handed_over'])],
        ]);

        if (array_key_exists('status', $validated)) {
            $disc->status = $validated['status'];
        }

        if (array_key_exists('active', $validated)) {
            $disc->active = (bool) $validated['active'];
        }

        if (array_key_exists('matchLifecycle', $validated)) {
            $disc->match_lifecycle = $validated['matchLifecycle'];
        }

        $disc->save();

        return redirect()->back();
    }

    public function updateMatch(Request $request, MatchThread $match): RedirectResponse
    {
        $user = $request->user();
        if ($user === null || $user->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['confirmed', 'handed_over', 'rejected'])],
        ]);

        $status = $validated['status'] ?? null;

        $match->loadMissing(['lostDisc', 'foundDisc']);

        if ($status === 'confirmed') {
            $match->status = 'confirmed';

            if ($match->lostDisc) {
                $match->lostDisc->active = false;
                $match->lostDisc->match_lifecycle = 'confirmed';
                $match->lostDisc->save();
            }

            if ($match->foundDisc) {
                $match->foundDisc->active = false;
                $match->foundDisc->match_lifecycle = 'confirmed';
                $match->foundDisc->save();
            }
        } elseif ($status === 'handed_over') {
            $match->status = 'handed_over';

            if ($match->lostDisc) {
                $match->lostDisc->active = false;
                $match->lostDisc->match_lifecycle = 'handed_over';
                $match->lostDisc->save();
            }

            if ($match->foundDisc) {
                $match->foundDisc->active = false;
                $match->foundDisc->match_lifecycle = 'handed_over';
                $match->foundDisc->save();
            }
        } elseif ($status === 'rejected') {
            $match->status = 'rejected';
        } else {
            // Pending / reset.
            $match->status = null;

            if ($match->lostDisc) {
                $match->lostDisc->active = true;
                $match->lostDisc->match_lifecycle = null;
                $match->lostDisc->save();
            }

            if ($match->foundDisc) {
                $match->foundDisc->active = true;
                $match->foundDisc->match_lifecycle = null;
                $match->foundDisc->save();
            }
        }

        $match->save();

        return redirect()->back();
    }
}
