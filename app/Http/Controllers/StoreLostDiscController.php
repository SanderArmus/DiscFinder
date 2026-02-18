<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLostDiscRequest;
use App\Models\Disc;
use App\Models\Location;
use App\Services\DiscColorResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class StoreLostDiscController extends Controller
{
    public function __invoke(StoreLostDiscRequest $request): RedirectResponse
    {
        $user = $request->user();

        $occurredAt = $request->input('datetime')
            ? Carbon::parse($request->input('datetime'))
            : null;

        $disc = Disc::create([
            'user_id' => $user->id,
            'status' => 'lost',
            'occurred_at' => $occurredAt,
            'manufacturer' => $request->input('manufacturer') ?: null,
            'model_name' => $request->input('name') ?: null,
            'plastic_type' => $request->input('plastic') ?: null,
            'back_text' => $request->input('inscription') ?: null,
            'condition_estimate' => $request->input('condition') ?: null,
            'active' => true,
        ]);

        if ($request->filled('latitude') && $request->filled('longitude')) {
            Location::create([
                'disc_id' => $disc->id,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'location_type' => 'lost',
            ]);
        }

        $colorIds = DiscColorResolver::resolveToColorIds(
            $request->input('selectedColors', [])
        );
        $disc->colors()->sync($colorIds);

        return redirect()->route('dashboard')->with('status', 'lost-disc-created');
    }
}
