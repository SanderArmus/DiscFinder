<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoundDiscRequest;
use App\Models\Disc;
use App\Models\Location;
use App\Services\DiscColorResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class StoreFoundDiscController extends Controller
{
    public function __invoke(StoreFoundDiscRequest $request): RedirectResponse
    {
        $user = $request->user();

        $occurredAt = $request->input('datetime')
            ? Carbon::parse($request->input('datetime'))
            : null;

        $disc = Disc::create([
            'user_id' => $user->id,
            'status' => 'found',
            'occurred_at' => $occurredAt,
            'manufacturer' => $request->input('manufacturer') ?: null,
            'model_name' => $request->input('name') ?: null,
            'plastic_type' => $request->input('plastic') ?: null,
            'back_text' => $request->input('inscription') ?: null,
            'condition_estimate' => $request->input('condition') ?: null,
            'active' => true,
        ]);

        $locationText = $request->input('location');
        $hasText = $locationText !== null && trim((string) $locationText) !== '';
        $hasCoords = $request->filled('latitude') && $request->filled('longitude');

        if ($hasText || $hasCoords) {
            Location::create([
                'disc_id' => $disc->id,
                'latitude' => $hasCoords ? $request->input('latitude') : null,
                'longitude' => $hasCoords ? $request->input('longitude') : null,
                'location_type' => 'found',
                'location_text' => $hasText ? (string) $locationText : null,
            ]);
        }

        $colorIds = DiscColorResolver::resolveToColorIds(
            $request->input('selectedColors', [])
        );
        $disc->colors()->sync($colorIds);

        return redirect()->route('dashboard')->with('status', 'found-disc-created');
    }
}
