<?php

namespace Tests\Unit;

use App\Models\GameMatch;
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

class LeagueTest extends TestCase
{
    protected LeagueService $leagueService;

    protected MatchSimulatorService $matchSimulator;

    /**
     * Set up the test environment.
     *
     * Creates all necessary repositories and services for testing the league functionality.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create repositories
        $matchRepository = new MatchRepository;

        // Create services
        $this->matchSimulator = new MatchSimulatorService;

        // Create LeagueService with all dependencies
        $this->leagueService = new LeagueService(
            new FixtureGeneratorService($matchRepository),
            $this->matchSimulator,
            new TableGeneratorService,
            new PredictionService,
            $matchRepository,
            new TeamService
        );
    }

    /**
     * Tests the creation of a team.
     *
     * @scenario Create a team with a specific name and strength
     * @expected The team should be saved in the database with the correct attributes
     *
     * @return void
     */
    public function testTeamCreation(): void
    {
        // Arrange
        $teamName = 'Test Team';
        $teamStrength = 75;

        // Act
        $team = Team::factory()->create([
            'name' => $teamName,
            'strength' => $teamStrength,
        ]);

        // Assert
        $this->assertDatabaseHas('teams', [
            'name' => $teamName,
            'strength' => $teamStrength,
        ]); // Team should be saved in the database with correct attributes

        $this->assertEquals($teamName, $team->name, 'Team name should match the input');
        $this->assertEquals($teamStrength, $team->strength, 'Team strength should match the input');
    }

    /**
     * Tests the creation of a match between two teams.
     *
     * @scenario Create a match between two teams for a specific week
     * @expected The match should be saved in the database with the correct attributes
     *
     * @return void
     */
    public function testMatchCreation(): void
    {
        // Arrange
        $homeTeam = Team::factory()->create([
            'name' => 'Home Team',
            'strength' => 70
        ]);

        $awayTeam = Team::factory()->create([
            'name' => 'Away Team',
            'strength' => 65
        ]);

        $week = 1;
        $played = false;

        // Act
        $match = GameMatch::factory()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'week' => $week,
            'played' => $played,
        ]);

        // Assert
        $this->assertDatabaseHas('matches', [
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'week' => $week,
            'played' => (int)$played, // In database, boolean is stored as 0/1
        ]); // Match should be saved in the database with correct attributes

        $this->assertEquals($homeTeam->id, $match->home_team_id, 'Match home team ID should match the input');
        $this->assertEquals($awayTeam->id, $match->away_team_id, 'Match away team ID should match the input');
        $this->assertEquals($week, $match->week, 'Match week should match the input');
        $this->assertEquals($played, $match->played, 'Match played status should match the input');
    }

    /**
     * Tests the simulation of a match.
     *
     * @scenario Create an unplayed match and simulate it
     * @expected The match should be marked as played and have home and away scores
     *
     * @return void
     */
    public function testMatchSimulation(): void
    {
        // Arrange
        $homeTeam = Team::factory()->create([
            'name' => 'Home Team',
            'strength' => 70
        ]);

        $awayTeam = Team::factory()->create([
            'name' => 'Away Team',
            'strength' => 65
        ]);

        $match = GameMatch::factory()->notPlayed()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'week' => 1,
        ]);

        // Verify match is not played before simulation
        $this->assertFalse($match->played, 'Match should not be played before simulation');
        $this->assertNull($match->home_score, 'Home score should be null before simulation');
        $this->assertNull($match->away_score, 'Away score should be null before simulation');

        // Act
        $this->matchSimulator->simulate($match);
        $match->refresh(); // Refresh the model to get the updated values from the database

        // Assert
        $this->assertTrue($match->played, 'Match should be marked as played after simulation');
        $this->assertNotNull($match->home_score, 'Home score should not be null after simulation');
        $this->assertNotNull($match->away_score, 'Away score should not be null after simulation');
        $this->assertIsInt($match->home_score, 'Home score should be an integer');
        $this->assertIsInt($match->away_score, 'Away score should be an integer');
        $this->assertGreaterThanOrEqual(0, $match->home_score, 'Home score should be non-negative');
        $this->assertGreaterThanOrEqual(0, $match->away_score, 'Away score should be non-negative');
    }

    /**
     * Tests the generation of league fixtures.
     *
     * @scenario Create 4 teams and generate fixtures for them
     * @expected 12 matches should be created (each team plays against every other team twice)
     *           There should be exactly 2 matches per week for 6 weeks
     *
     * @return void
     */
    public function testLeagueFixtureGeneration(): void
    {
        // Arrange
        Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        Team::factory()->create(['name' => 'Team B', 'strength' => 75]);
        Team::factory()->create(['name' => 'Team C', 'strength' => 70]);
        Team::factory()->create(['name' => 'Team D', 'strength' => 65]);

        $teamCount = Team::count();
        $this->assertEquals(4, $teamCount, 'Should have 4 teams for this test');

        // Act
        $this->leagueService->generateFixtures();

        // Assert
        // Calculate expected number of matches: n teams * (n-1) opponents
        $expectedMatchCount = $teamCount * ($teamCount - 1);
        $this->assertEquals($expectedMatchCount, GameMatch::count(),
            "Should create {$expectedMatchCount} matches for {$teamCount} teams");

        // Check that there are exactly n/2 games per week
        $matchesPerWeek = $teamCount / 2;
        $expectedWeekCount = ($teamCount - 1) * 2; // (n-1) rounds * 2 (home and away)

        $weeks = GameMatch::select('week')->distinct()->get()->pluck('week')->toArray();
        $this->assertEquals($expectedWeekCount, count($weeks),
            "Should have {$expectedWeekCount} weeks of matches");

        // Verify each week has the correct number of matches
        foreach ($weeks as $week) {
            $matchesInWeek = GameMatch::where('week', $week)->count();
            $this->assertEquals($matchesPerWeek, $matchesInWeek,
                "Week {$week} should have exactly {$matchesPerWeek} matches");
        }

        // Verify each team plays against every other team twice (once home, once away)
        $teams = Team::all();
        foreach ($teams as $team) {
            foreach ($teams as $opponent) {
                if ($team->id != $opponent->id) {
                    $homeMatchCount = GameMatch::where('home_team_id', $team->id)
                        ->where('away_team_id', $opponent->id)
                        ->count();
                    $this->assertEquals(1, $homeMatchCount,
                        "Team {$team->name} should play at home against {$opponent->name} exactly once");

                    $awayMatchCount = GameMatch::where('home_team_id', $opponent->id)
                        ->where('away_team_id', $team->id)
                        ->count();
                    $this->assertEquals(1, $awayMatchCount,
                        "Team {$team->name} should play away against {$opponent->name} exactly once");
                }
            }
        }
    }

    /**
     * Tests the calculation of the league table.
     *
     * @scenario Create 2 teams and a match where Team A wins against Team B
     * @expected Team A should be first in the table with 3 points, 1 win, 0 draws, 0 losses
     *           Team B should be second with 0 points, 0 wins, 0 draws, 1 loss
     *           Goal statistics should be correctly calculated
     *
     * @return void
     */
    public function testLeagueTableCalculation(): void
    {
        // Arrange
        $teamA = Team::factory()->create([
            'name' => 'Team A',
            'strength' => 80
        ]);

        $teamB = Team::factory()->create([
            'name' => 'Team B',
            'strength' => 75
        ]);

        $homeScore = 3;
        $awayScore = 1;

        // Create a match where Team A wins
        $match = GameMatch::factory()->played()->create([
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'week' => 1,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ]);

        // Act
        $table = $this->leagueService->getTable();

        // Assert
        $this->assertCount(2, $table, 'Table should have 2 entries');

        // Team A should be first with 3 points
        $this->assertEquals($teamA->id, $table[0]['team']->id, 'Team A should be first in the table');
        $this->assertEquals(3, $table[0]['points'], 'Team A should have 3 points');
        $this->assertEquals(1, $table[0]['wins'], 'Team A should have 1 win');
        $this->assertEquals(0, $table[0]['draws'], 'Team A should have 0 draws');
        $this->assertEquals(0, $table[0]['losses'], 'Team A should have 0 losses');
        $this->assertEquals($homeScore, $table[0]['goals_for'], 'Team A should have correct goals for');
        $this->assertEquals($awayScore, $table[0]['goals_against'], 'Team A should have correct goals against');
        $this->assertEquals($homeScore - $awayScore, $table[0]['goal_difference'],
            'Team A should have correct goal difference');

        // Team B should be second with 0 points
        $this->assertEquals($teamB->id, $table[1]['team']->id, 'Team B should be second in the table');
        $this->assertEquals(0, $table[1]['points'], 'Team B should have 0 points');
        $this->assertEquals(0, $table[1]['wins'], 'Team B should have 0 wins');
        $this->assertEquals(0, $table[1]['draws'], 'Team B should have 0 draws');
        $this->assertEquals(1, $table[1]['losses'], 'Team B should have 1 loss');
        $this->assertEquals($awayScore, $table[1]['goals_for'], 'Team B should have correct goals for');
        $this->assertEquals($homeScore, $table[1]['goals_against'], 'Team B should have correct goals against');
        $this->assertEquals($awayScore - $homeScore, $table[1]['goal_difference'],
            'Team B should have correct goal difference');
    }
}
