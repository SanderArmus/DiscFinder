<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return Inertia::render('settings/Notifications', [
            'preferences' => [
                'emailNotifyDiscExpiring' => (bool) $user->email_notify_disc_expiring,
                'emailNotifyDiscExpired' => (bool) $user->email_notify_disc_expired,
                'emailNotifyNewMessage' => (bool) $user->email_notify_new_message,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $validated = $request->validate([
            'email_notify_disc_expiring' => ['required', 'boolean'],
            'email_notify_disc_expired' => ['required', 'boolean'],
            'email_notify_new_message' => ['required', 'boolean'],
        ]);

        $user->forceFill($validated)->save();

        return redirect()->back();
    }
}
