<?php

namespace App\Services;

use App\Models\Disc;
use App\Models\MatchThread;
use App\Models\User;
use Illuminate\Support\Carbon;

final class MatchFinder
{
    public function __construct(
        private readonly MatchScorer $scorer = new MatchScorer
    ) {}

    /**
     * Returns potential matches for the given user's lost discs.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     confidence: int,
     *     location: string,
     *     date: string,
     *     lostDiscId: int,
     *     foundDiscId: int,
     *     otherDiscId: int,
     *     otherDiscType: string
     * }>
     */
    public function findForUser(User $user, int $limit = 5, float $minScore = 60.0): array
    {
        // Consider both the user's lost and found discs so that matches are
        // surfaced symmetrically to both the owner and the finder.
        $discs = $user->discs()
            ->whereIn('status', ['lost', 'found'])
            ->where('active', true)
            ->whereNotNull('occurred_at')
            ->whereNotNull('condition_estimate')
            ->with(['colors', 'locations'])
            ->latest('created_at')
            ->get();

        $matches = [];
        foreach ($discs as $disc) {
            foreach ($this->findForDisc($disc, $limit, $minScore) as $match) {
                $matches[$match['id']] = $match;
            }
        }

        $matches = array_values($matches);

        usort($matches, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);

        return array_slice($matches, 0, $limit);
    }

    /**
     * Returns potential matches for a single disc against all active discs
     * of the opposite type.
     *
     * - If the disc is `lost`, it is compared to all active `found` discs.
     * - If the disc is `found`, it is compared to all active `lost` discs.
     *
     * This also persists/upserts `MatchThread` rows for matches above `$minScore`
     * so the user can open a chat for them.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     confidence: int,
     *     location: string,
     *     date: string,
     *     lostDiscId: int,
     *     foundDiscId: int,
     *     otherDiscId: int,
     *     otherDiscType: string
     * }>
     */
    public function findForDisc(Disc $disc, int $limit = 5, float $minScore = 60.0): array
    {
        $disc->loadMissing(['colors', 'locations']);

        if ($disc->occurred_at === null || $disc->condition_estimate === null) {
            return [];
        }

        $discLocation = $this->pickLocation($disc);
        if ($discLocation === null) {
            return [];
        }

        $colorIds = $disc->colors->pluck('id')->all();
        if (empty($colorIds)) {
            return [];
        }

        $isLost = $disc->status === 'lost';
        $oppositeStatus = $isLost ? 'found' : 'lost';

        $possibleMatches = [];

        if ($isLost) {
            /** @var Disc $lostDisc */
            $lostDisc = $disc;
            /** @var Disc[] $oppositeCandidates */
            $oppositeCandidates = Disc::query()
                ->where('status', $oppositeStatus)
                ->where('active', true)
                ->where('user_id', '!=', $lostDisc->user_id) // never match same user's lost+found
                ->whereNotNull('occurred_at')
                ->whereNotNull('condition_estimate')
                ->where('occurred_at', '>=', $lostDisc->occurred_at)
                ->whereHas('colors', function ($q) use ($colorIds) {
                    $q->whereIn('colors.id', $colorIds);
                })
                ->whereHas('locations', function ($q) {
                    $q->whereNotNull('latitude')->whereNotNull('longitude');
                })
                ->with(['colors', 'locations'])
                ->limit(30)
                ->get();

            foreach ($oppositeCandidates as $foundDisc) {
                $scored = $this->scorer->score(
                    $lostDisc->loadMissing(['colors', 'locations']),
                    $foundDisc
                );

                if ($scored === null) {
                    continue;
                }

                $score = $scored['match_score'];
                if ($score < $minScore) {
                    continue;
                }

                $match = MatchThread::query()
                    ->where('lost_disc_id', $lostDisc->id)
                    ->where('found_disc_id', $foundDisc->id)
                    ->first();

                if ($match === null) {
                    $match = MatchThread::create([
                        'lost_disc_id' => $lostDisc->id,
                        'found_disc_id' => $foundDisc->id,
                        'match_score' => $score,
                        'status' => 'pending',
                    ]);
                } else {
                    if ($match->status === 'rejected') {
                        continue;
                    }

                    $match->match_score = $score;
                    if ($match->status === null) {
                        $match->status = 'pending';
                    }
                    $match->save();
                }

                $oppositeLoc = $this->pickLocation($foundDisc);
                $locationString = $oppositeLoc
                    ? sprintf('%0.4f, %0.4f', (float) $oppositeLoc->latitude, (float) $oppositeLoc->longitude)
                    : '—';

                $possibleMatches[] = [
                    'id' => $match->id,
                    'name' => $foundDisc->model_name ?: ($foundDisc->manufacturer ?: '—'),
                    'confidence' => (int) round($score),
                    'location' => $locationString,
                    'date' => $foundDisc->occurred_at instanceof Carbon
                        ? $foundDisc->occurred_at->format('M j, Y')
                        : (string) $foundDisc->occurred_at,
                    'lostDiscId' => $lostDisc->id,
                    'foundDiscId' => $foundDisc->id,
                    'otherDiscId' => $foundDisc->id,
                    'otherDiscType' => 'found',
                ];
            }
        } else {
            /** @var Disc $foundDisc */
            $foundDisc = $disc;
            /** @var Disc[] $oppositeCandidates */
            $oppositeCandidates = Disc::query()
                ->where('status', $oppositeStatus)
                ->where('active', true)
                ->where('user_id', '!=', $foundDisc->user_id) // never match same user's lost+found
                ->whereNotNull('occurred_at')
                ->whereNotNull('condition_estimate')
                ->where('occurred_at', '<=', $foundDisc->occurred_at)
                ->whereHas('colors', function ($q) use ($colorIds) {
                    $q->whereIn('colors.id', $colorIds);
                })
                ->whereHas('locations', function ($q) {
                    $q->whereNotNull('latitude')->whereNotNull('longitude');
                })
                ->with(['colors', 'locations'])
                ->limit(30)
                ->get();

            foreach ($oppositeCandidates as $lostDisc) {
                $scored = $this->scorer->score(
                    $lostDisc,
                    $foundDisc->loadMissing(['colors', 'locations'])
                );

                if ($scored === null) {
                    continue;
                }

                $score = $scored['match_score'];
                if ($score < $minScore) {
                    continue;
                }

                $match = MatchThread::query()
                    ->where('lost_disc_id', $lostDisc->id)
                    ->where('found_disc_id', $foundDisc->id)
                    ->first();

                if ($match === null) {
                    $match = MatchThread::create([
                        'lost_disc_id' => $lostDisc->id,
                        'found_disc_id' => $foundDisc->id,
                        'match_score' => $score,
                        'status' => 'pending',
                    ]);
                } else {
                    if ($match->status === 'rejected') {
                        continue;
                    }

                    $match->match_score = $score;
                    if ($match->status === null) {
                        $match->status = 'pending';
                    }
                    $match->save();
                }

                $oppositeLoc = $this->pickLocation($lostDisc);
                $locationString = $oppositeLoc
                    ? sprintf('%0.4f, %0.4f', (float) $oppositeLoc->latitude, (float) $oppositeLoc->longitude)
                    : '—';

                $possibleMatches[] = [
                    'id' => $match->id,
                    'name' => $lostDisc->model_name ?: ($lostDisc->manufacturer ?: '—'),
                    'confidence' => (int) round($score),
                    'location' => $locationString,
                    'date' => $lostDisc->occurred_at instanceof Carbon
                        ? $lostDisc->occurred_at->format('M j, Y')
                        : (string) $lostDisc->occurred_at,
                    'lostDiscId' => $lostDisc->id,
                    'foundDiscId' => $foundDisc->id,
                    'otherDiscId' => $lostDisc->id,
                    'otherDiscType' => 'lost',
                ];
            }
        }

        usort($possibleMatches, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);

        return array_slice($possibleMatches, 0, $limit);
    }

    private function pickLocation(Disc $disc): ?object
    {
        return $disc->locations
            ? $disc->locations->first(fn ($l) => $l->latitude !== null && $l->longitude !== null)
            : null;
    }
}
