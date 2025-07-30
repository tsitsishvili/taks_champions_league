<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Repositories\MatchRepository;
use App\Repositories\TeamRepository;
use App\Services\FixtureGeneratorService;
use App\Services\LeagueService;
use App\Services\MatchSimulatorService;
use App\Services\PredictionService;
use App\Services\TableGeneratorService;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixtureGenerationTest extends TestCase
{
    /**
     * Tests that an exception is thrown when generating fixtures with an odd number of teams.
     *
     * @scenario Attempt to generate fixtures with 3 teams (odd number)
     * @expected An InvalidArgumentException should be thrown with the message 'Number of teams must be even'
     */
    public function testThrowsExceptionForOddNumberOfTeams(): void
    {
        // Arrange
        // Create 3 teams (odd number)
        Team::factory()->create(['name' => 'Liverpool', 'strength' => 85]);
        Team::factory()->create(['name' => 'Manchester City', 'strength' => 87]);
        Team::factory()->create(['name' => 'Chelsea', 'strength' => 83]);

        // Expect an exception when generating fixtures
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Number of teams must be even');

        // Create repositories and services
        $matchRepository = new MatchRepository;
        $teamRepository = new TeamRepository;
        $teamService = new TeamService;
        $matchSimulatorService = new MatchSimulatorService;
        $tableGeneratorService = new TableGeneratorService;
        $predictionService = new PredictionService;
        $fixtureGeneratorService = new FixtureGeneratorService($matchRepository);

        // Create LeagueService with all dependencies
        $leagueService = new LeagueService(
            $fixtureGeneratorService,
            $matchSimulatorService,
            $tableGeneratorService,
            $predictionService,
            $matchRepository,
            $teamService
        );

        // Act
        // Generate fixtures - this should throw an exception
        $leagueService->generateFixtures();
    }

    /**
     * Tests that each team plays exactly once per week in the generated fixtures.
     *
     * @scenario Generate fixtures for 4 teams and verify the schedule structure
     * @expected Each team should appear exactly once in each week's matches
     *           There should be exactly 2 matches per week
     *           There should be exactly 6 weeks in total (n-1)*2 for 4 teams
     */
    public function testEachTeamPlaysOncePerWeek(): void
    {
        // Arrange
        // Create 4 teams
        Team::factory()->create(['name' => 'Liverpool', 'strength' => 85]);
        Team::factory()->create(['name' => 'Manchester City', 'strength' => 87]);
        Team::factory()->create(['name' => 'Chelsea', 'strength' => 83]);
        Team::factory()->create(['name' => 'Arsenal', 'strength' => 82]);

        // Create repositories and services
        $matchRepository = new MatchRepository;
        $teamRepository = new TeamRepository;
        $teamService = new TeamService;
        $matchSimulatorService = new MatchSimulatorService;
        $tableGeneratorService = new TableGeneratorService;
        $predictionService = new PredictionService;
        $fixtureGeneratorService = new FixtureGeneratorService($matchRepository);

        // Create LeagueService with all dependencies
        $leagueService = new LeagueService(
            $fixtureGeneratorService,
            $matchSimulatorService,
            $tableGeneratorService,
            $predictionService,
            $matchRepository,
            $teamService
        );

        // Act
        // Generate fixtures
        $leagueService->generateFixtures();

        // Get all matches grouped by week
        $matches = $leagueService->getMatches();
        $matchesByWeek = $matches->groupBy('week');

        // Assert
        // For each week, check that each team appears exactly once
        foreach ($matchesByWeek as $week => $weekMatches) {
            $teamsInWeek = [];

            foreach ($weekMatches as $match) {
                $teamsInWeek[] = $match->home_team_id;
                $teamsInWeek[] = $match->away_team_id;
            }

            // Sort teams for easier comparison
            sort($teamsInWeek);

            // Get all team IDs
            $allTeamIds = Team::pluck('id')->toArray();
            sort($allTeamIds);

            // Check that all teams appear exactly once in this week
            $this->assertEquals($allTeamIds, $teamsInWeek,
                "Week {$week}: Each team should appear exactly once");

            // Check that there are exactly 2 matches in this week
            $this->assertEquals(2, $weekMatches->count(),
                "Week {$week}: There should be exactly 2 matches");
        }

        // Check that there are exactly 6 weeks
        $this->assertEquals(6, $matchesByWeek->count(),
            'There should be exactly 6 weeks for a 4-team league');
    }
}
