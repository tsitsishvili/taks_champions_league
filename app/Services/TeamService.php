<?php

namespace App\Services;

use App\Models\Team;
use App\Repositories\TeamRepository;

class TeamService
{
    private TeamRepository $teamRepository;

    public function __construct()
    {
        $this->teamRepository = app(TeamRepository::class);
    }

    public function getAllTeams()
    {
        return $this->teamRepository->getAllTeams();
    }

    public function createTeam(array $data): Team
    {
        return $this->teamRepository->createTeam($data);
    }

    public function deleteAllTeams(): void
    {
        $this->teamRepository->deleteAllTeams();
    }

    /**
     * Calculate team statistics based on played matches.
     */
    public function getStats(Team $team): array
    {
        $stats = [
            'played' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'points' => 0,
        ];

        // Process home matches
        foreach ($team->homeMatches as $match) {
            if (! $match->played) {
                continue;
            }

            $stats['played']++;
            $stats['goals_for'] += $match->home_score;
            $stats['goals_against'] += $match->away_score;

            if ($match->home_score > $match->away_score) {
                $stats['wins']++;
                $stats['points'] += 3;
            } elseif ($match->home_score == $match->away_score) {
                $stats['draws']++;
                $stats['points'] += 1;
            } else {
                $stats['losses']++;
            }
        }

        // Process away matches
        foreach ($team->awayMatches as $match) {
            if (! $match->played) {
                continue;
            }

            $stats['played']++;
            $stats['goals_for'] += $match->away_score;
            $stats['goals_against'] += $match->home_score;

            if ($match->away_score > $match->home_score) {
                $stats['wins']++;
                $stats['points'] += 3;
            } elseif ($match->away_score == $match->home_score) {
                $stats['draws']++;
                $stats['points'] += 1;
            } else {
                $stats['losses']++;
            }
        }

        $stats['goal_difference'] = $stats['goals_for'] - $stats['goals_against'];

        return $stats;
    }
}
