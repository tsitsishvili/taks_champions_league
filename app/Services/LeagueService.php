<?php

namespace App\Services;

use App\Repositories\MatchRepository;
use Illuminate\Support\Collection;

class LeagueService
{
    private FixtureGeneratorService $fixtureGenerator;

    private MatchSimulatorService $matchSimulator;

    private TableGeneratorService $tableGenerator;

    private PredictionService $predictionService;

    private MatchRepository $matchRepository;

    private TeamService $teamService;

    public function __construct(
        FixtureGeneratorService $fixtureGenerator,
        MatchSimulatorService $matchSimulator,
        TableGeneratorService $tableGenerator,
        PredictionService $predictionService,
        MatchRepository $matchRepository,
        TeamService $teamService
    ) {
        $this->fixtureGenerator = $fixtureGenerator;
        $this->matchSimulator = $matchSimulator;
        $this->tableGenerator = $tableGenerator;
        $this->predictionService = $predictionService;
        $this->matchRepository = $matchRepository;
        $this->teamService = $teamService;
    }

    /**
     * Remove old records, create new ones, and Generate fixtures
     */
    public function initialize(): void
    {
        // First, delete existing matches and teams
        $this->matchRepository->deleteAllMatches();
        $this->teamService->deleteAllTeams();

        // Create teams with random strengths
        $teams = [
            ['name' => 'Liverpool', 'strength' => mt_rand(50, 90)],
            ['name' => 'Manchester City', 'strength' => mt_rand(50, 90)],
            ['name' => 'Chelsea', 'strength' => mt_rand(50, 90)],
            ['name' => 'Arsenal', 'strength' => mt_rand(50, 90)],
            // ['name' => 'Barcelona', 'strength' => mt_rand(50, 90)],
            // ['name' => 'Real madrid', 'strength' => mt_rand(50, 90)],
        ];

        foreach ($teams as $teamData) {
            $this->teamService->createTeam($teamData);
        }

        // Generate fixtures
        $this->generateFixtures();
    }

    /**
     * Generate fixtures for all teams.
     */
    public function generateFixtures(): void
    {
        // Delete existing matches
        $this->matchRepository->deleteAllMatches();

        $teams = $this->teamService->getAllTeams();

        // Use the FixtureGenerator to generate fixtures
        $this->fixtureGenerator->generateFixtures($teams);
    }

    /**
     * Simulate all unplayed matches.
     */
    public function simulateAllMatches(): void
    {
        $this->matchSimulator->simulateAllMatches();
    }

    /**
     * Simulate matches for a specific week.
     */
    public function simulateWeek(int $week): void
    {
        $this->matchSimulator->simulateWeek($week);
    }

    /**
     * Get the league table.
     */
    public function getTable(): Collection
    {
        return $this->tableGenerator->getTable();
    }

    /**
     * Get championship predictions.
     */
    public function getPredictions(): Collection
    {
        return $this->predictionService->getPredictions();
    }

    /**
     * Get all matches with team details.
     */
    public function getMatches(): Collection
    {
        return $this->matchRepository->getAllMatches();
    }

    /**
     * Get league data including matches, table, and predictions.
     */
    public function getLeagueData(): array
    {
        return [
            'matches' => $this->getMatches(),
            'table' => $this->getTable(),
            'predictions' => $this->getPredictions(),
        ];
    }

    /**
     * Reset all match results.
     */
    public function resetMatches(): void
    {
        $this->matchSimulator->resetMatches();
    }

    /**
     * Simulate the next unplayed week of matches.
     */
    public function simulateNextWeek(): array
    {
        // Find the next unplayed week
        $nextWeek = $this->matchRepository->findNextUnplayedWeek();

        if ($nextWeek) {
            $this->simulateWeek($nextWeek);

            return ['success' => true, 'week' => $nextWeek];
        }

        return ['success' => false, 'message' => 'No more weeks to simulate'];
    }
}
