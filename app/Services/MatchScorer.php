<?php

namespace App\Services;

use App\Models\Disc;
use App\Models\Location;

final class MatchScorer
{
    /**
     * Returns null when the pair should be discarded completely.
     *
     * @return array{match_score: float, time_score: float, distance_score: float, color_score: float, condition_score: float, identity_score: float}|null
     */
    public function score(Disc $lost, Disc $found): ?array
    {
        $lostLocation = $this->pickLocationWithCoords($lost);
        $foundLocation = $this->pickLocationWithCoords($found);

        if ($lostLocation === null || $foundLocation === null) {
            return null;
        }

        if ($lost->occurred_at === null || $found->occurred_at === null) {
            return null;
        }

        // Hard rule: found must be after lost.
        if ($found->occurred_at->lt($lost->occurred_at)) {
            return null;
        }

        if ($lost->condition_estimate === null || $found->condition_estimate === null) {
            return null;
        }

        $lostColors = $lost->colors ?? collect();
        $foundColors = $found->colors ?? collect();

        $lostColorIds = $lostColors->pluck('id')->all();
        $foundColorIds = $foundColors->pluck('id')->all();

        if (empty($lostColorIds) || empty($foundColorIds)) {
            return null;
        }

        $overlap = array_intersect($lostColorIds, $foundColorIds);
        if (count($overlap) === 0) {
            return null;
        }

        $distanceKm = $this->haversineKm(
            (float) $lostLocation->latitude,
            (float) $lostLocation->longitude,
            (float) $foundLocation->latitude,
            (float) $foundLocation->longitude
        );

        $timeScore = $this->scoreTimeGapDays((float) $found->occurred_at->diffInSeconds($lost->occurred_at) / 86400);
        $distanceScore = $this->scoreDistanceKm($distanceKm);
        $colorScore = $this->scoreColorOverlap($lostColorIds, $foundColorIds, $overlap);
        $conditionScore = $lost->condition_estimate === $found->condition_estimate ? 100.0 : 50.0;
        $identityScore = $this->scoreIdentity($lost, $found);

        // Weights can be tuned later based on confirmed/rejected feedback.
        // Distance is intentionally not dominant (a disc may be carried far away).
        $matchScore = (0.20 * $timeScore)
            + (0.15 * $distanceScore)
            + (0.35 * $colorScore)
            + (0.10 * $conditionScore)
            + (0.20 * $identityScore);

        return [
            'match_score' => round($matchScore, 2),
            'time_score' => round($timeScore, 2),
            'distance_score' => round($distanceScore, 2),
            'color_score' => round($colorScore, 2),
            'condition_score' => round($conditionScore, 2),
            'identity_score' => round($identityScore, 2),
        ];
    }

    private function pickLocationWithCoords(Disc $disc): ?Location
    {
        $location = $disc->locations instanceof \Illuminate\Support\Collection
            ? $disc->locations
                ->first(fn (Location $l): bool => $l->latitude !== null && $l->longitude !== null)
            : null;

        return $location;
    }

    private function scoreDistanceKm(float $distanceKm): float
    {
        if ($distanceKm <= 1.0) {
            return 100.0;
        }

        // Gradual decay:
        // - 1km..10km: 100 -> 70
        // - 10km..50km: 70 -> 40
        // - 50km..200km: 40 -> 20 (never goes to 0; discs can travel)
        if ($distanceKm <= 10.0) {
            return max(70.0, 100.0 - (($distanceKm - 1.0) / 9.0) * 30.0);
        }

        if ($distanceKm <= 50.0) {
            return max(40.0, 70.0 - (($distanceKm - 10.0) / 40.0) * 30.0);
        }

        if ($distanceKm <= 200.0) {
            return max(20.0, 40.0 - (($distanceKm - 50.0) / 150.0) * 20.0);
        }

        return 20.0;
    }

    private function scoreTimeGapDays(float $gapDays): float
    {
        if ($gapDays <= 0.5) {
            return 100.0; // ~12 hours
        }

        if ($gapDays <= 2) {
            return 80.0;
        }

        if ($gapDays <= 7) {
            return 60.0;
        }

        if ($gapDays <= 30) {
            return 25.0;
        }

        return 0.0;
    }

    /**
     * @param  array<int, int|string>  $lostColorIds
     * @param  array<int, int|string>  $foundColorIds
     * @param  array<int, int|string>  $overlap
     */
    private function scoreColorOverlap(array $lostColorIds, array $foundColorIds, array $overlap): float
    {
        $lostCount = max(1, count($lostColorIds));
        $foundCount = max(1, count($foundColorIds));
        $denominator = max($lostCount, $foundCount);

        return min(100.0, (count($overlap) / $denominator) * 100.0);
    }

    private function scoreIdentity(Disc $lost, Disc $found): float
    {
        $scores = [];

        $brandLost = $lost->manufacturer ?: null;
        $brandFound = $found->manufacturer ?: null;
        if ($brandLost !== null && $brandFound !== null) {
            $scores[] = $this->stringSimilarity($brandLost, $brandFound);
        }

        $nameLost = $lost->model_name ?: null;
        $nameFound = $found->model_name ?: null;
        if ($nameLost !== null && $nameFound !== null) {
            $scores[] = $this->stringSimilarity($nameLost, $nameFound);
        }

        $textLost = $lost->back_text ?: null;
        $textFound = $found->back_text ?: null;
        if ($textLost !== null && $textFound !== null) {
            $scores[] = $this->stringSimilarity($textLost, $textFound);
        }

        if (count($scores) === 0) {
            return 0.0;
        }

        return array_sum($scores) / count($scores);
    }

    private function stringSimilarity(string $a, string $b): float
    {
        $aNorm = $this->normalizeText($a);
        $bNorm = $this->normalizeText($b);

        if ($aNorm === '' || $bNorm === '') {
            return 0.0;
        }

        if ($aNorm === $bNorm) {
            return 100.0;
        }

        // similar_text returns percent 0..100.
        $percent = similar_text($aNorm, $bNorm);

        return max(0.0, min(100.0, (float) $percent));
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', (string) $text);
        $text = preg_replace('/[^a-z0-9\\s]/', '', (string) $text);

        return (string) $text;
    }

    private function haversineKm(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);

        $a = sin($dLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
