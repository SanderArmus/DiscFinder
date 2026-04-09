<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminUsersController
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

        $users = User::query()
            ->when($q, function ($query) use ($q) {
                $term = '%'.trim($q).'%';
                $query->where(function ($q2) use ($term) {
                    $q2->where('username', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Users', [
            'filters' => [
                'q' => $q,
            ],
            'users' => $users->through(fn (User $user) => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'createdAt' => $user->created_at?->format('Y-m-d H:i:s'),
            ]),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $authUser = $request->user();
        if ($authUser === null || $authUser->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'role' => ['nullable', 'string', Rule::in(['admin', 'trusted', 'user'])],
            'banned' => ['nullable', 'boolean'],
            'banned_reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($user->id === $authUser->id && ($validated['role'] ?? null) !== 'admin') {
            return redirect()->back();
        }

        $role = $validated['role'] ?? null;
        $role = $role === 'user' ? null : $role;

        $user->forceFill(['role' => $role]);

        if (array_key_exists('banned', $validated)) {
            if ((bool) $validated['banned']) {
                $user->banned_at = now();
                $user->banned_reason = $validated['banned_reason'] ?? null;
            } else {
                $user->banned_at = null;
                $user->banned_reason = null;
            }
        }

        $user->save();

        return redirect()->back();
    }
}
