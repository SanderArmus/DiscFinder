<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDiscRequest;
use App\Models\Disc;
use App\Services\DiscColorResolver;
use App\Services\MatchFinder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class UpdateDiscController extends Controller
{
    public function __invoke(UpdateDiscRequest $request): RedirectResponse
    {
        $discParam = $request->route('disc');
        $disc = $discParam instanceof Disc ? $discParam : Disc::query()->find($discParam);

        if (! ($disc instanceof Disc)) {
            abort(403);
        }

        $occurredAtInput = $request->input('datetime');
        $occurredAt = $occurredAtInput !== null && trim((string) $occurredAtInput) !== ''
            ? Carbon::parse((string) $occurredAtInput)
            : null;

        $disc->update([
            'manufacturer' => $request->input('manufacturer') ?: null,
            'model_name' => $request->input('name') ?: null,
            'plastic_type' => $request->input('plastic') ?: null,
            'back_text' => $request->input('inscription') ?: null,
            'condition_estimate' => $request->input('condition') ?: null,
            'occurred_at' => $occurredAt,
        ]);

        $locationText = $request->input('location');
        $hasText = $locationText !== null && trim((string) $locationText) !== '';

        $hasCoords = $request->filled('latitude') && $request->filled('longitude');

        $location = $disc->locations()
            ->firstOrNew(['location_type' => $disc->status]);

        $location->disc_id = $disc->id;
        $location->location_type = $disc->status;
        $location->latitude = $hasCoords ? $request->input('latitude') : null;
        $location->longitude = $hasCoords ? $request->input('longitude') : null;
        $location->location_text = $hasText ? (string) $locationText : null;
        $location->save();

        $colorIds = DiscColorResolver::resolveToColorIds(
            $request->input('selectedColors', [])
        );
        $disc->colors()->sync($colorIds);

        // Recompute potential matches so the chat list stays relevant.
        app(MatchFinder::class)->findForDisc($disc, limit: 5, minScore: 60.0);

        return redirect()->route('discs.show', ['disc' => $disc->id]);
    }
}
