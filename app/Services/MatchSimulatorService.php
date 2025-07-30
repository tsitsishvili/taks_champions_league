<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Repositories\MatchRepository;

class MatchSimulatorService
{
    private MatchRepository $matchRepository;

    public function __construct()
    {
        $this->matchRepository = app(MatchRepository::class);
    }

    /**
     * Simulate all unplayed matches.
     */
    public function simulateAllMatches(): void
    {
        $matches = $this->matchRepository->getUnplayedMatches();

        foreach ($matches as $match) {
            $this->simulate($match);
        }
    }

    /**
     * Simulate matches for a specific week.
     */
    public function simulateWeek(int $week): void
    {
        $matches = $this->matchRepository->getUnplayedMatchesByWeek($week);

        foreach ($matches as $match) {
            $this->simulate($match);
        }
    }

    /**
     * Reset all match results.
     */
    public function resetMatches(): void
    {
        $this->matchRepository->resetAllMatches();
    }

    /**
     * Simulate the match result based on team strengths.
     */
    public function simulate(GameMatch $match): void
    {
        if ($match->played) {
            return;
        }

        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        // Home advantage factor (home teams tend to score more)
        $homeAdvantage = 1.2;

        // Calculate expected goals based on team strengths
        $homeExpectedGoals = ($homeTeam->strength / mt_rand(25, 50)) * $homeAdvantage;
        $awayExpectedGoals = $awayTeam->strength / mt_rand(25, 50);

        // Add some randomness to the goals
        $homeScore = $this->generateGoals($homeExpectedGoals);
        $awayScore = $this->generateGoals($awayExpectedGoals);

        $match->home_score = $homeScore;
        $match->away_score = $awayScore;
        $match->played = true;
        $match->save();
    }

    /**
     * Generate a random number of goals based on expected goals.
     */
    private function generateGoals(float $expectedGoals): int
    {
        // Use Poisson distribution to generate realistic goal counts
        $edge = exp(-$expectedGoals);
        $probability = 1.0;
        $goals = 0;

        do {
            $goals++;
            $probability *= mt_rand() / mt_getrandmax();
        } while ($probability > $edge);

        return max(0, $goals - 1); // Ensure we don't get negative goals
    }
}
