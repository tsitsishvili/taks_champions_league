<?php

namespace App\Services;

use App\Repositories\MatchRepository;
use Illuminate\Database\Eloquent\Collection;

class FixtureGeneratorService
{
    private MatchRepository $matchRepository;

    public function __construct(MatchRepository $matchRepository)
    {
        $this->matchRepository = $matchRepository;
    }

    /**
     * Generate a schedule pattern for a given number of teams.
     */
    public function generateSchedulePattern(int $teamCount): array
    {
        // Generate a tournament schedule using the circle method
        // Each inner array represents a round of matches
        // Each match is represented as [team1_index, team2_index]

        // Check if the number of teams is odd
        if ($teamCount % 2 !== 0) {
            throw new \InvalidArgumentException('Number of teams must be even');
        }

        $teams = range(0, $teamCount - 1);
        $schedule = [];

        $halfSize = $teamCount / 2;

        // Generate rounds
        for ($round = 0; $round < $teamCount - 1; $round++) {
            $roundMatches = [];

            // Generate matches for this round
            for ($match = 0; $match < $halfSize; $match++) {
                $team1 = $teams[$match];
                $team2 = $teams[$teamCount - 1 - $match];
                $roundMatches[] = [$team1, $team2];
            }

            $schedule[] = $roundMatches;

            // Rotate teams (except the first one)
            $teams = array_merge(
                [$teams[0]],
                [$teams[$teamCount - 1]],
                array_slice($teams, 1, $teamCount - 2)
            );
        }

        return $schedule;
    }

    /**
     * Create fixtures for the first half of the season (home matches).
     */
    public function createFirstHalfFixtures(array $schedule, Collection $teams, int $startWeek = 1): int
    {
        $week = $startWeek;

        foreach ($schedule as $round) {
            foreach ($round as $match) {
                $this->matchRepository->createMatch([
                    'home_team_id' => $teams[$match[0]]->id,
                    'away_team_id' => $teams[$match[1]]->id,
                    'week' => $week,
                    'played' => false,
                ]);
            }
            $week++;
        }

        return $week;
    }

    /**
     * Create fixtures for the second half of the season (away matches).
     */
    public function createSecondHalfFixtures(array $schedule, Collection $teams, int $startWeek): int
    {
        $week = $startWeek;

        foreach ($schedule as $round) {
            foreach ($round as $match) {
                $this->matchRepository->createMatch([
                    'home_team_id' => $teams[$match[1]]->id,
                    'away_team_id' => $teams[$match[0]]->id,
                    'week' => $week,
                    'played' => false,
                ]);
            }
            $week++;
        }

        return $week;
    }

    /**
     * Generate all fixtures for the season.
     */
    public function generateFixtures(Collection $teams): void
    {
        // Generate the schedule pattern for the given number of teams
        $schedule = $this->generateSchedulePattern($teams->count());

        // Create first half fixtures (home matches)
        $week = $this->createFirstHalfFixtures($schedule, $teams);

        // Create second half fixtures (away matches)
        $this->createSecondHalfFixtures($schedule, $teams, $week);
    }
}
