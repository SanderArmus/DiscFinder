<?php

namespace App\Http\Controllers;

use App\Models\MatchThread;
use Inertia\Inertia;
use Inertia\Response;

class ShowMatchDetailsController extends Controller
{
    public function __invoke(MatchThread $match): Response
    {
        $user = auth()->user();

        if ($user === null) {
            abort(403);
        }

        $match->load([
            'lostDisc.user',
            'foundDisc.user',
            'lostDisc.locations',
            'foundDisc.locations',
            'lostDisc.colors',
            'foundDisc.colors',
            'confirmation',
        ]);

        if (
            $user->id !== $match->lostDisc->user_id
            && $user->id !== $match->foundDisc->user_id
        ) {
            abort(403);
        }

        $lost = $match->lostDisc;
        $found = $match->foundDisc;

        return Inertia::render('MatchDetails', [
            'match' => [
                'id' => $match->id,
                'status' => $match->status ?: 'pending',
                'score' => $match->match_score,
            ],
            'lostDisc' => $this->discPayload($lost),
            'foundDisc' => $this->discPayload($found),
        ]);
    }

    /**
     * @return array{
     *     id: int,
     *     ownerName: string,
     *     status: string,
     *     occurredAt: string,
     *     manufacturer: string|null,
     *     modelName: string|null,
     *     plasticType: string|null,
     *     condition: string|null,
     *     inscription: string|null,
     *     inscriptionName: string|null,
     *     inscriptionNumber: string|null,
     *     customDescription: string|null,
     *     colors: array<int, string>,
     *     locationText: string,
     *     locationPin: array{lat: float, lng: float}|null
     * }
     */
    private function discPayload(\App\Models\Disc $disc): array
    {
        $locationText = '—';
        $locationPin = null;

        if ($disc->locations instanceof \Illuminate\Support\Collection) {
            $textLocation = $disc->locations
                ->first(fn ($l) => $l->location_text !== null && trim((string) $l->location_text) !== '');

            if ($textLocation !== null && $textLocation->location_text !== null) {
                $label = trim((string) $textLocation->location_text);
                if ($label !== '') {
                    $locationText = $label;
                }
            }

            $coordsLocation = $disc->locations
                ->first(fn ($l) => $l->latitude !== null && $l->longitude !== null);

            if ($coordsLocation !== null) {
                $locationPin = [
                    'lat' => (float) $coordsLocation->latitude,
                    'lng' => (float) $coordsLocation->longitude,
                ];
            }
        }

        return [
            'id' => $disc->id,
            'ownerName' => $disc->user?->name ?? '',
            'status' => $disc->status,
            'occurredAt' => $disc->occurred_at?->format('M j, Y') ?? '—',
            'manufacturer' => $disc->manufacturer,
            'modelName' => $disc->model_name,
            'plasticType' => $disc->plastic_type,
            'condition' => $disc->condition_estimate,
            'inscription' => $disc->back_text,
            'inscriptionName' => $disc->back_name,
            'inscriptionNumber' => $disc->back_number,
            'customDescription' => $disc->custom_description,
            'colors' => $disc->colors?->pluck('name')->values()->all() ?? [],
            'locationText' => $locationText,
            'locationPin' => $locationPin,
        ];
    }
}
