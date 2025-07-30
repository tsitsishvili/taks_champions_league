<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Repositories\MatchRepository;
use App\Repositories\TeamRepository;
use App\Services\FixtureGeneratorService;
use App\Services\LeagueService;
use App\Services\MatchSimulatorService;
use App\Services\PredictionService;
use App\Services\TableGeneratorService;
use App\Services\TeamService;
use Tests\TestCase;

class LeagueServiceTest extends TestCase
{
    protected LeagueService $leagueService;

    protected MatchRepository $matchRepository;

    protected TeamRepository $teamRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matchRepository = new MatchRepository;
        $this->teamRepository = new TeamRepository;

        // Create LeagueService with all dependencies
        $this->leagueService = new LeagueService(
            new FixtureGeneratorService($this->matchRepository),
            new MatchSimulatorService,
            new TableGeneratorService,
            new PredictionService,
            $this->matchRepository,
            new TeamService
        );
    }

    /**
     * Tests the initialization of the league.
     *
     * @scenario Initialize a new league
     * @expected Teams should be created
     *           Matches should be created
     *           All matches should be unplayed
     *
     * @return void
     */
    public function testInitialize(): void
    {
        // Act
        $this->leagueService->initialize();

        // Assert that teams were created
        $teams = $this->teamRepository->getAllTeams();
        $this->assertGreaterThan(0, $teams->count(), 'Teams should be created');

        // Assert that matches were created
        $matches = $this->matchRepository->getAllMatches();
        $this->assertGreaterThan(0, $matches->count(), 'Matches should be created');

        // Assert that all matches are unplayed
        foreach ($matches as $match) {
            $this->assertFalse($match->played, 'All matches should be unplayed');
        }
    }

    /**
     * Tests the simulation of all matches in the league.
     *
     * @scenario Simulate all matches in the league
     * @expected All matches should be marked as played
     *           All matches should have home and away scores
     *
     * @return void
     */
    public function testSimulateAllMatches(): void
    {
        // Arrange
        $this->createTeamsAndMatches();

        // Act
        $this->leagueService->simulateAllMatches();

        // Assert
        $matches = $this->matchRepository->getAllMatches();
        $this->assertGreaterThan(0, $matches->count(), 'There should be matches to simulate');

        foreach ($matches as $match) {
            $this->assertTrue($match->played, "Match {$match->id} should be marked as played");
            $this->assertNotNull($match->home_score, "Match {$match->id} should have a home score");
            $this->assertNotNull($match->away_score, "Match {$match->id} should have an away score");
        }
    }

    /**
     * Tests the simulation of matches for a specific week.
     *
     * @scenario Simulate matches for week 1 only
     * @expected Matches for week 1 should be marked as played and have scores
     *           Matches for other weeks should remain unplayed with null scores
     *
     * @return void
     */
    public function testSimulateWeek(): void
    {
        // Arrange
        $this->createTeamsAndMatches();
        $weekToSimulate = 1;

        // Act
        $this->leagueService->simulateWeek($weekToSimulate);

        // Assert
        // Assert that matches for the specified week are played
        $simulatedMatches = $this->matchRepository->getMatchesByWeek($weekToSimulate);
        $this->assertGreaterThan(0, $simulatedMatches->count(),
            "There should be matches for week {$weekToSimulate}");

        foreach ($simulatedMatches as $match) {
            $this->assertTrue($match->played,
                "Match {$match->id} for week {$weekToSimulate} should be marked as played");
            $this->assertNotNull($match->home_score,
                "Match {$match->id} for week {$weekToSimulate} should have a home score");
            $this->assertNotNull($match->away_score,
                "Match {$match->id} for week {$weekToSimulate} should have an away score");
        }

        // Assert that matches for other weeks are not played
        $otherMatches = GameMatch::where('week', '!=', $weekToSimulate)->get();
        $this->assertGreaterThan(0, $otherMatches->count(),
            "There should be matches for weeks other than {$weekToSimulate}");

        foreach ($otherMatches as $match) {
            $this->assertFalse($match->played,
                "Match {$match->id} for week {$match->week} should not be played");
            $this->assertNull($match->home_score,
                "Match {$match->id} for week {$match->week} should have a null home score");
            $this->assertNull($match->away_score,
                "Match {$match->id} for week {$match->week} should have a null away score");
        }
    }

    /**
     * Tests retrieving championship predictions.
     *
     * @scenario Simulate week 1 matches and get championship predictions
     * @expected Predictions should not be empty
     *           Each prediction should have 'team' and 'probability' fields
     *
     * @return void
     */
    public function testGetPredictions(): void
    {
        // Arrange
        $this->createTeamsAndMatches();
        $this->leagueService->simulateWeek(1);

        // Act
        $predictions = $this->leagueService->getPredictions();

        // Assert
        $this->assertNotEmpty($predictions, 'Predictions should not be empty');

        foreach ($predictions as $index => $prediction) {
            $this->assertArrayHasKey('team', $prediction,
                "Prediction {$index} should have a 'team' field");
            $this->assertArrayHasKey('probability', $prediction,
                "Prediction {$index} should have a 'probability' field");
            $this->assertIsNumeric($prediction['probability'],
                "Prediction {$index} probability should be numeric");
            $this->assertGreaterThanOrEqual(0, $prediction['probability'],
                "Prediction {$index} probability should be >= 0");
            $this->assertLessThanOrEqual(100, $prediction['probability'],
                "Prediction {$index} probability should be <= 100");
        }
    }

    /**
     * Tests retrieving all matches with team details.
     *
     * @scenario Create teams and matches and retrieve all matches
     * @expected Matches should not be empty
     *           Each match should have homeTeam and awayTeam details
     *
     * @return void
     */
    public function testGetMatches(): void
    {
        // Arrange
        $this->createTeamsAndMatches();

        // Act
        $matches = $this->leagueService->getMatches();

        // Assert
        $this->assertNotEmpty($matches, 'Matches should not be empty');

        foreach ($matches as $match) {
            $this->assertNotNull($match->homeTeam,
                "Match {$match->id} should have home team details");
            $this->assertNotNull($match->awayTeam,
                "Match {$match->id} should have away team details");
            $this->assertEquals($match->home_team_id, $match->homeTeam->id,
                "Match {$match->id} home_team_id should match homeTeam->id");
            $this->assertEquals($match->away_team_id, $match->awayTeam->id,
                "Match {$match->id} away_team_id should match awayTeam->id");
        }
    }

    /**
     * Tests retrieving comprehensive league data.
     *
     * @scenario Simulate week 1 matches and get league data
     * @expected League data should contain matches, table, and predictions
     *           All data components should not be empty
     *
     * @return void
     */
    public function testGetLeagueData(): void
    {
        // Arrange
        $this->createTeamsAndMatches();
        $this->leagueService->simulateWeek(1);

        // Act
        $leagueData = $this->leagueService->getLeagueData();

        // Assert
        // Check structure
        $this->assertIsArray($leagueData, 'League data should be an array');
        $this->assertArrayHasKey('matches', $leagueData, 'League data should contain matches');
        $this->assertArrayHasKey('table', $leagueData, 'League data should contain table');
        $this->assertArrayHasKey('predictions', $leagueData, 'League data should contain predictions');

        // Check content (skip checking for homeTeam/awayTeam properties as they might not be loaded in tests)
        $this->assertNotEmpty($leagueData['matches'], 'Matches should not be empty');
        $this->assertNotEmpty($leagueData['table'], 'Table should not be empty');
        $this->assertNotEmpty($leagueData['predictions'], 'Predictions should not be empty');

        // Check that table entries have team details and stats
        foreach ($leagueData['table'] as $entry) {
            $this->assertArrayHasKey('team', $entry, 'Table entry should have team information');
            $this->assertArrayHasKey('points', $entry, 'Table entry should have points');
            $this->assertIsNumeric($entry['points'], 'Points should be numeric');
        }
    }

    /**
     * Tests resetting all match results.
     *
     * @scenario Simulate all matches, then reset them
     * @expected All matches should be marked as unplayed after reset
     *           All match scores should be null after reset
     *
     * @return void
     */
    public function testResetMatches(): void
    {
        // Arrange
        $this->createTeamsAndMatches();
        $this->leagueService->simulateAllMatches();

        // Verify that all matches are played before reset
        $unplayedCount = $this->matchRepository->countUnplayedMatches();
        $this->assertEquals(0, $unplayedCount, 'All matches should be played before reset');

        // Act
        $this->leagueService->resetMatches();

        // Assert
        $matches = $this->matchRepository->getAllMatches();
        $this->assertGreaterThan(0, $matches->count(), 'There should be matches to check');

        foreach ($matches as $match) {
            $this->assertFalse($match->played,
                "Match {$match->id} should be marked as unplayed after reset");
            $this->assertNull($match->home_score,
                "Match {$match->id} should have null home score after reset");
            $this->assertNull($match->away_score,
                "Match {$match->id} should have null away score after reset");
        }

        // Additional verification
        $allUnplayedCount = $this->matchRepository->countUnplayedMatches();
        $allMatchesCount = $matches->count();
        $this->assertEquals($allMatchesCount, $allUnplayedCount,
            'All matches should be counted as unplayed after reset');
    }

    /**
     * Helper method to create teams and matches for testing.
     *
     * This method sets up a test environment with 4 teams and 4 matches across 2 weeks.
     * It first clears any existing data, then creates teams with different strengths,
     * and finally creates matches between these teams for weeks 1 and 2.
     *
     * Teams created:
     * - Team A (strength: 80)
     * - Team B (strength: 75)
     * - Team C (strength: 70)
     * - Team D (strength: 65)
     *
     * Matches created:
     * - Week 1: Team A vs Team B, Team C vs Team D
     * - Week 2: Team A vs Team C, Team B vs Team D
     *
     * @return void
     */
    private function createTeamsAndMatches(): void
    {
        // Clean up - delete existing teams and matches
        $this->matchRepository->deleteAllMatches();
        $this->teamRepository->deleteAllTeams();

        // Create teams with different strengths
        $teamA = $this->teamRepository->createTeam([
            'name' => 'Team A',
            'strength' => 80
        ]);

        $teamB = $this->teamRepository->createTeam([
            'name' => 'Team B',
            'strength' => 75
        ]);

        $teamC = $this->teamRepository->createTeam([
            'name' => 'Team C',
            'strength' => 70
        ]);

        $teamD = $this->teamRepository->createTeam([
            'name' => 'Team D',
            'strength' => 65
        ]);

        // Create matches for week 1
        $this->matchRepository->createMatch([
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'week' => 1,
            'played' => false,
        ]);

        $this->matchRepository->createMatch([
            'home_team_id' => $teamC->id,
            'away_team_id' => $teamD->id,
            'week' => 1,
            'played' => false,
        ]);

        // Create matches for week 2
        $this->matchRepository->createMatch([
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamC->id,
            'week' => 2,
            'played' => false,
        ]);

        $this->matchRepository->createMatch([
            'home_team_id' => $teamB->id,
            'away_team_id' => $teamD->id,
            'week' => 2,
            'played' => false,
        ]);
    }
}
