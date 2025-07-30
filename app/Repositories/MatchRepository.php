<?php

namespace App\Repositories;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class MatchRepository
{
    /**
     * Get all matches with team details.
     */
    public function getAllMatches(): Collection
    {
        return GameMatch::with(['homeTeam', 'awayTeam'])
            ->orderBy('week')
            ->get();
    }

    /**
     * Get matches for a specific week.
     */
    public function getMatchesByWeek(int $week): Collection
    {
        return GameMatch::with(['homeTeam', 'awayTeam'])
            ->where('week', $week)
            ->orderBy('week')
            ->get();
    }

    /**
     * Get unplayed matches.
     */
    public function getUnplayedMatches(): Collection
    {
        return GameMatch::with(['homeTeam', 'awayTeam'])
            ->where('played', false)
            ->orderBy('week')
            ->get();
    }

    /**
     * Get unplayed matches for a specific week.
     */
    public function getUnplayedMatchesByWeek(int $week): Collection
    {
        return GameMatch::query()
            ->where('week', $week)
            ->where('played', false)
            ->get();
    }

    /**
     * Count unplayed matches.
     */
    public function countUnplayedMatches(): int
    {
        return GameMatch::query()->where('played', false)->count();
    }

    /**
     * Count remaining matches for a team.
     */
    public function countRemainingMatchesForTeam(Team $team): int
    {
        return GameMatch::query()->where('played', false)
            ->where(function ($query) use ($team) {
                $query->where('home_team_id', $team->id)
                    ->orWhere('away_team_id', $team->id);
            })
            ->count();
    }

    /**
     * Find the next unplayed week.
     */
    public function findNextUnplayedWeek(): ?int
    {
        return GameMatch::query()
            ->where('played', false)
            ->min('week');
    }

    /**
     * Reset all match results.
     */
    public function resetAllMatches(): void
    {
        GameMatch::query()->update([
            'home_score' => null,
            'away_score' => null,
            'played' => false,
        ]);
    }

    /**
     * Delete all matches.
     */
    public function deleteAllMatches(): void
    {
        GameMatch::query()->delete();
    }

    /**
     * Create a new match.
     */
    public function createMatch(array $data): GameMatch
    {
        return GameMatch::create($data);
    }
}
