<?php

namespace App\Http\Controllers;

use App\Models\Disc;
use App\Models\MatchThread;
use App\Services\MatchFinder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowDiscController extends Controller
{
    public function __invoke(Request $request, Disc $disc): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $isOwner = $user->id === $disc->user_id;

        if (! $isOwner) {
            $participantMatchExists = MatchThread::query()
                ->where(function ($q) use ($disc) {
                    $q->where('lost_disc_id', $disc->id)
                        ->orWhere('found_disc_id', $disc->id);
                })
                ->where(function ($q) use ($user) {
                    $q->whereHas('lostDisc', fn ($dQ) => $dQ->where('user_id', $user->id))
                        ->orWhereHas('foundDisc', fn ($dQ) => $dQ->where('user_id', $user->id));
                })
                ->exists();

            if (! $participantMatchExists) {
                abort(403);
            }
        }

        $disc->loadMissing(['colors', 'locations']);

        $locationText = '';
        $locationPin = null;

        if ($disc->locations instanceof \Illuminate\Support\Collection) {
            $textLocation = $disc->locations
                ->first(fn ($l) => $l->location_text !== null && trim((string) $l->location_text) !== '');

            if ($textLocation !== null && $textLocation->location_text !== null) {
                $locationText = trim((string) $textLocation->location_text);
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

        $displayName = $disc->model_name ?: ($disc->manufacturer ?: '—');
        $brand = $disc->plastic_type ?: ($disc->manufacturer ?: '—');
        $colorNames = $disc->colors?->pluck('name')->values()->all() ?? [];

        $canEdit = $isOwner && $disc->active === true;
        $canDelete = $canEdit;
        $showPotentialMatches = $isOwner && $disc->active === true;

        $possibleMatches = $showPotentialMatches
            ? app(MatchFinder::class)->findForDisc($disc, limit: 5, minScore: 60.0)
            : [];

        return Inertia::render('DiscDetails', [
            'disc' => [
                'id' => $disc->id,
                'status' => $disc->status,
                'name' => $displayName,
                'brand' => $brand,
                'manufacturer' => $disc->manufacturer,
                'modelName' => $disc->model_name,
                'plasticType' => $disc->plastic_type,
                'condition' => $disc->condition_estimate,
                'inscription' => $disc->back_text,
                'occurredAt' => $disc->occurred_at?->format('Y-m-d\\TH:i') ?? '',
                'active' => (bool) $disc->active,
                'matchLifecycle' => $disc->match_lifecycle,
                'colorNames' => $colorNames,
                'locationText' => $locationText,
                'locationPin' => $locationPin,
            ],
            'possibleMatches' => $possibleMatches,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
        ]);
    }
}
