<?php

namespace App\Services;

use App\Repositories\MatchRepository;
use Illuminate\Support\Collection;

class PredictionService
{
    private TeamService $teamService;

    private TableGeneratorService $tableGeneratorService;

    private MatchRepository $matchRepository;

    public function __construct()
    {
        $this->teamService = app(TeamService::class);
        $this->tableGeneratorService = app(TableGeneratorService::class);
        $this->matchRepository = app(MatchRepository::class);
    }

    /**
     * Calculate the probability of each team winning the championship.
     */
    public function getPredictions(): Collection
    {
        $teams = $this->teamService->getAllTeams();

        if ($teams->isEmpty()) {
            return collect();
        }

        $predictions = [];

        // every team has exactly the same number of remaining matches left, so we need to select once
        $remainingMatches = $this->matchRepository->countRemainingMatchesForTeam($teams->first());

        // Get the current table to determine current standings
        $table = $this->tableGeneratorService->getTable();

        // If all matches are played, the team with the most points is the champion (100%)
        if ($remainingMatches === 0) {
            // The team at position 1 has a 100% chance, others have 0%
            foreach ($teams as $team) {
                $predictions[$team->id] = [
                    'team' => $team,
                    'probability' => 0,
                ];
            }

            // Set 100% for the champion
            if ($table->isNotEmpty()) {
                $champion = $table->first()['team'];
                $predictions[$champion->id]['probability'] = 100;
            }

            return collect($predictions)
                ->sortByDesc('probability')
                ->values();
        }

        // Get current points for each team
        $currentPoints = [];
        foreach ($table as $row) {
            $currentPoints[$row['team']->id] = $row['points'];
        }

        // Maximum possible additional points (3 points per remaining match)
        $maxAdditionalPoints = $remainingMatches * 3;

        // Calculate maximum possible points for each team
        $maxPossiblePoints = [];

        foreach ($teams as $team) {
            $stats = $table->firstWhere('team.id', $team->id);

            $maxPossiblePoints[$team->id] = $stats['points'] + $maxAdditionalPoints;
        }

        $totalPoints = 0;

        // Calculate weighted scores based on current points, goal difference, and team strength
        foreach ($teams as $team) {
            $stats = $this->teamService->getStats($team);

            // Check if the team has a mathematical chance to win
            $hasChance = true;

            // A team has no chance if there's another team with more current points than this team's maximum possible points
            foreach ($currentPoints as $otherTeamId => $otherTeamPoints) {
                if ($otherTeamId != $team->id && $otherTeamPoints > $maxPossiblePoints[$team->id]) {
                    $hasChance = false;
                    break;
                }
            }

            if (! $hasChance) {
                $predictions[$team->id] = [
                    'team' => $team,
                    'weighted_score' => 0,
                    'probability' => 0,
                ];

                continue;
            }

            // Weight factors
            $pointsWeight = 4;
            $goalDifferenceWeight = 2;
            $strengthWeight = 1;

            // Calculate weighted score
            $weightedScore =
                ($stats['points'] * $pointsWeight) +
                ($stats['goal_difference'] * $goalDifferenceWeight) +
                ($team->strength * $strengthWeight);

            $predictions[$team->id] = [
                'team' => $team,
                'weighted_score' => $weightedScore,
                'probability' => 0, // Will be calculated after summing all scores
            ];

            $totalPoints += $weightedScore;
        }

        // Calculate probabilities based on weighted scores
        if ($totalPoints > 0) {
            foreach ($predictions as &$prediction) {
                $prediction['probability'] = round(($prediction['weighted_score'] / $totalPoints) * 100);
                unset($prediction['weighted_score']); // Remove the weighted score from the final result
            }
        }

        return collect($predictions)
            ->sortByDesc(function ($prediction) {
                return $prediction['probability'];
            })
            ->values();
    }
}
