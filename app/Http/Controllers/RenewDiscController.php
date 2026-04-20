<?php

namespace App\Http\Controllers;

use App\Models\Disc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RenewDiscController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Disc $disc): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        if ((int) $disc->user_id !== (int) $user->id) {
            abort(403);
        }

        $disc->forceFill([
            'active' => true,
            'expires_at' => now()->addDays(90),
            'expiring_soon_notified_at' => null,
        ])->save();

        return redirect()->back();
    }
}
