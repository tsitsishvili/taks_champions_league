<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Team;
use App\Repositories\MatchRepository;
use App\Services\MatchSimulatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchSimulatorTest extends TestCase
{
    protected MatchSimulatorService $matchSimulator;

    protected MatchRepository $matchRepository;

    protected Team $homeTeam;

    protected Team $awayTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matchSimulator = new MatchSimulatorService;
        $this->matchRepository = new MatchRepository;

        // Create teams for testing
        $this->homeTeam = Team::factory()->create(['name' => 'Home Team', 'strength' => 80]);
        $this->awayTeam = Team::factory()->create(['name' => 'Away Team', 'strength' => 75]);
    }

    /**
     * Tests simulating all unplayed matches in the database.
     *
     * @scenario Simulate all unplayed matches when multiple matches exist
     * @expected All matches should be marked as played
     *           All matches should have home and away scores assigned
     */
    public function testSimulateAllMatches(): void
    {
        // Arrange
        $this->createUnplayedMatches();

        // Act
        $this->matchSimulator->simulateAllMatches();

        // Assert
        $matches = GameMatch::all();
        $this->assertNotEmpty($matches, 'There should be matches to check');

        foreach ($matches as $match) {
            $this->assertTrue($match->played, "Match {$match->id} should be marked as played");
            $this->assertNotNull($match->home_score, "Match {$match->id} should have a home score");
            $this->assertNotNull($match->away_score, "Match {$match->id} should have an away score");
        }
    }

    /**
     * Tests simulating matches for a specific week only.
     *
     * @scenario Simulate matches for week 1 when matches exist for multiple weeks
     * @expected Only matches for week 1 should be marked as played and have scores
     *           Matches for other weeks should remain unplayed with null scores
     */
    public function testSimulateWeek(): void
    {
        // Arrange
        // Delete any existing matches
        GameMatch::query()->delete();

        // Create unplayed matches for different weeks
        // Week 1 matches
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => false,
            'home_score' => null,
            'away_score' => null,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 1,
            'played' => false,
            'home_score' => null,
            'away_score' => null,
        ]);

        // Week 2 matches
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 2,
            'played' => false,
            'home_score' => null,
            'away_score' => null,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 2,
            'played' => false,
            'home_score' => null,
            'away_score' => null,
        ]);

        // Act
        $this->matchSimulator->simulateWeek(1);

        // Assert
        // Verify week 1 matches are played
        $week1Matches = GameMatch::where('week', 1)->get();
        $this->assertNotEmpty($week1Matches, 'There should be matches for week 1');

        foreach ($week1Matches as $match) {
            $this->assertTrue($match->played, "Match {$match->id} for week 1 should be marked as played");
            $this->assertNotNull($match->home_score, "Match {$match->id} for week 1 should have a home score");
            $this->assertNotNull($match->away_score, "Match {$match->id} for week 1 should have an away score");
        }

        // Verify other weeks' matches are not played
        $otherMatches = GameMatch::where('week', '!=', 1)->get();
        $this->assertNotEmpty($otherMatches, 'There should be matches for weeks other than week 1');

        foreach ($otherMatches as $match) {
            $this->assertFalse($match->played, "Match {$match->id} for week {$match->week} should remain unplayed");
            $this->assertNull($match->home_score, "Match {$match->id} for week {$match->week} should have null home score");
            $this->assertNull($match->away_score, "Match {$match->id} for week {$match->week} should have null away score");
        }
    }

    /**
     * Tests resetting all match results to unplayed status.
     *
     * @scenario Reset all matches when there are played matches with scores
     * @expected All matches should be marked as unplayed
     *           All match scores should be set to null
     */
    public function testResetMatches(): void
    {
        // Arrange
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => true,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 2,
            'played' => true,
            'home_score' => 3,
            'away_score' => 2,
        ]);

        // Verify initial state
        $initialMatches = GameMatch::all();
        $this->assertNotEmpty($initialMatches, 'There should be matches before reset');
        $this->assertTrue($initialMatches->every(fn($match) => $match->played),
            'All matches should be played before reset');

        // Act
        $this->matchSimulator->resetMatches();

        // Assert
        $matches = GameMatch::all();
        $this->assertNotEmpty($matches, 'Matches should still exist after reset');

        foreach ($matches as $match) {
            $this->assertFalse($match->played, "Match {$match->id} should be marked as unplayed after reset");
            $this->assertNull($match->home_score, "Match {$match->id} should have null home score after reset");
            $this->assertNull($match->away_score, "Match {$match->id} should have null away score after reset");
        }
    }

    /**
     * Tests that the match simulation respects team strength differences.
     *
     * @scenario Simulate many matches between a strong team and a weak team
     * @expected The stronger team should win significantly more often than the weaker team
     *           The win rate for the stronger team should be at least 60%
     * @edge_case This is a statistical test that could occasionally fail due to randomness,
     *            but should pass with high probability given a large enough sample size
     */
    public function testSimulateRespectsTeamStrengths(): void
    {
        // Arrange
        $strongTeam = Team::factory()->create(['name' => 'Strong Team', 'strength' => 90]);
        $weakTeam = Team::factory()->create(['name' => 'Weak Team', 'strength' => 50]);

        // Create many matches between the strong and weak teams
        $matchCount = 200;
        $matches = [];

        for ($i = 0; $i < $matchCount; $i++) {
            $matches[] = GameMatch::factory()->create([
                'home_team_id' => $strongTeam->id,
                'away_team_id' => $weakTeam->id,
                'week' => $i + 1,
                'played' => false,
            ]);
        }

        // Act
        foreach ($matches as $match) {
            $this->matchSimulator->simulate($match);
        }

        // Assert
        // Count wins for the strong team
        $strongTeamWins = GameMatch::where('home_team_id', $strongTeam->id)
            ->where('played', true)
            ->whereRaw('home_score > away_score')
            ->count();

        $winPercentage = ($strongTeamWins / $matchCount) * 100;
        $this->assertGreaterThan($matchCount * 0.6, $strongTeamWins,
            "Strong team should win more than 60% of matches (actual: {$winPercentage}%)");

        // Additional verification that all matches were simulated
        $this->assertEquals($matchCount, GameMatch::where('played', true)->count(),
            "All {$matchCount} matches should have been simulated");
    }

    /**
     * Helper method to create unplayed matches for testing.
     *
     * Creates 4 unplayed matches across 2 weeks:
     * - Week 1: Home Team vs Away Team, Away Team vs Home Team
     * - Week 2: Home Team vs Away Team, Away Team vs Home Team
     *
     * @return void
     */
    private function createUnplayedMatches(): void
    {
        // Create matches for week 1
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 1,
            'played' => false,
        ]);

        // Create matches for week 2
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 2,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 2,
            'played' => false,
        ]);
    }
}
