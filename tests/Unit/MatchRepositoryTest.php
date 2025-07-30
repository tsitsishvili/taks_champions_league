<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Team;
use App\Repositories\MatchRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchRepositoryTest extends TestCase
{
    protected MatchRepository $matchRepository;

    protected Team $homeTeam;

    protected Team $awayTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matchRepository = new MatchRepository;

        // Create teams for testing
        $this->homeTeam = Team::factory()->create(['name' => 'Home Team', 'strength' => 80]);
        $this->awayTeam = Team::factory()->create(['name' => 'Away Team', 'strength' => 75]);
    }

    /**
     * Tests retrieving all matches from the repository.
     *
     * @scenario Retrieve all matches from the database
     * @expected All matches should be returned in order of week
     *           The collection should contain the correct number of matches
     *           Each match should have the correct team associations
     */
    public function testGetAllMatches(): void
    {
        // Arrange
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 2,
            'played' => true,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        // Act
        $matches = $this->matchRepository->getAllMatches();

        // Assert
        $this->assertEquals(2, $matches->count(), 'Should return exactly 2 matches');
        $this->assertEquals(1, $matches->first()->week, 'First match should be from week 1');
        $this->assertEquals(2, $matches->last()->week, 'Last match should be from week 2');
        $this->assertEquals($this->homeTeam->id, $matches->first()->home_team_id, 'First match should have correct home team');
        $this->assertEquals($this->awayTeam->id, $matches->first()->away_team_id, 'First match should have correct away team');
    }

    /**
     * Tests retrieving matches for a specific week.
     *
     * @scenario Retrieve matches for a specific week when matches exist for multiple weeks
     * @expected Only matches for the specified week should be returned
     *           The collection should contain the correct number of matches
     *           Each match should have the correct week value
     */
    public function testGetMatchesByWeek(): void
    {
        // Arrange
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 2,
            'played' => true,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        // Act
        $week1Matches = $this->matchRepository->getMatchesByWeek(1);

        // Assert
        $this->assertEquals(1, $week1Matches->count(), 'Should return exactly 1 match for week 1');
        $this->assertEquals(1, $week1Matches->first()->week, 'Match should be from week 1');
    }

    /**
     * Tests retrieving all unplayed matches from the repository.
     *
     * @scenario Retrieve unplayed matches when both played and unplayed matches exist
     * @expected Only unplayed matches should be returned
     *           The collection should contain the correct number of matches
     *           Each match should have the played attribute set to false
     */
    public function testGetUnplayedMatches(): void
    {
        // Arrange
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 2,
            'played' => true,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        // Act
        $unplayedMatches = $this->matchRepository->getUnplayedMatches();

        // Assert
        $this->assertEquals(1, $unplayedMatches->count(), 'Should return exactly 1 unplayed match');
        $this->assertFalse($unplayedMatches->first()->played, 'Match should be marked as unplayed');
    }

    /**
     * Tests retrieving unplayed matches for a specific week.
     *
     * @scenario Retrieve unplayed matches for a specific week when both played and unplayed matches exist
     * @expected Only unplayed matches for the specified week should be returned
     *           The collection should contain the correct number of matches
     *           Each match should have the correct week value and played attribute set to false
     */
    public function testGetUnplayedMatchesByWeek(): void
    {
        // Arrange
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 2,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 2,
            'played' => true,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        // Act
        $week2UnplayedMatches = $this->matchRepository->getUnplayedMatchesByWeek(2);

        // Assert
        $this->assertEquals(1, $week2UnplayedMatches->count(), 'Should return exactly 1 unplayed match for week 2');
        $this->assertEquals(2, $week2UnplayedMatches->first()->week, 'Match should be from week 2');
        $this->assertFalse($week2UnplayedMatches->first()->played, 'Match should be marked as unplayed');
    }

    /**
     * Tests counting the total number of unplayed matches.
     *
     * @scenario Count unplayed matches when both played and unplayed matches exist
     * @expected The count should equal the number of unplayed matches in the database
     */
    public function testCountUnplayedMatches(): void
    {
        // Arrange
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 2,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 3,
            'played' => true,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        // Act
        $unplayedMatchCount = $this->matchRepository->countUnplayedMatches();

        // Assert
        $this->assertEquals(2, $unplayedMatchCount, 'Should count exactly 2 unplayed matches');
    }

    /**
     * Tests counting the remaining matches for a specific team.
     *
     * @scenario Count remaining matches for a team that has both played and unplayed matches
     * @expected The count should include all matches where the team is either home or away
     *           and the match has not been played yet
     */
    public function testCountRemainingMatchesForTeam(): void
    {
        // Arrange
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 2,
            'played' => true,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 3,
            'played' => false,
        ]);

        // Act - Count remaining matches for the home team
        $homeTeamRemainingMatches = $this->matchRepository->countRemainingMatchesForTeam($this->homeTeam);

        // Assert
        $this->assertEquals(2, $homeTeamRemainingMatches,
            'Home team should have 2 remaining matches (1 as home, 1 as away)');

        // Act - Count remaining matches for the away team
        $awayTeamRemainingMatches = $this->matchRepository->countRemainingMatchesForTeam($this->awayTeam);

        // Assert
        $this->assertEquals(2, $awayTeamRemainingMatches,
            'Away team should have 2 remaining matches (1 as home, 1 as away)');
    }

    /**
     * Tests finding the next week with unplayed matches.
     *
     * @scenario Find the next unplayed week when there are matches across multiple weeks
     *           with some weeks already played
     * @expected The method should return the earliest week that has unplayed matches
     */
    public function testFindNextUnplayedWeek(): void
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
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 3,
            'played' => false,
        ]);

        // Act
        $nextUnplayedWeek = $this->matchRepository->findNextUnplayedWeek();

        // Assert
        $this->assertEquals(2, $nextUnplayedWeek, 'Should find week 2 as the next unplayed week');
    }

    /**
     * Tests resetting all matches to unplayed status.
     *
     * @scenario Reset all matches when there are played matches with scores
     * @expected All matches should be marked as unplayed
     *           All match scores should be set to null
     */
    public function testResetAllMatches(): void
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

        // Act
        $this->matchRepository->resetAllMatches();

        // Assert
        $matches = GameMatch::all();
        $this->assertNotEmpty($matches, 'Matches should still exist after reset');

        foreach ($matches as $match) {
            $this->assertFalse($match->played, 'Match should be marked as unplayed');
            $this->assertNull($match->home_score, 'Home score should be null');
            $this->assertNull($match->away_score, 'Away score should be null');
        }
    }

    /**
     * Tests deleting all matches from the database.
     *
     * @scenario Delete all matches when matches exist in the database
     * @expected All matches should be removed from the database
     *           The match count should be zero after deletion
     */
    public function testDeleteAllMatches(): void
    {
        // Arrange
        GameMatch::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => false,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $this->awayTeam->id,
            'away_team_id' => $this->homeTeam->id,
            'week' => 2,
            'played' => true,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        // Verify initial state
        $this->assertEquals(2, GameMatch::count(), 'Should have 2 matches before deletion');

        // Act
        $this->matchRepository->deleteAllMatches();

        // Assert
        $this->assertEquals(0, GameMatch::count(), 'Should have 0 matches after deletion');
    }

    /**
     * Tests creating a new match in the database.
     *
     * @scenario Create a new match with specific attributes
     * @expected The match should be created with the correct attributes
     *           The match should exist in the database
     *           The returned match object should match the input data
     */
    public function testCreateMatch(): void
    {
        // Arrange
        $matchData = [
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => false,
        ];

        // Act
        $match = $this->matchRepository->createMatch($matchData);

        // Assert
        $this->assertEquals($this->homeTeam->id, $match->home_team_id, 'Match should have correct home team ID');
        $this->assertEquals($this->awayTeam->id, $match->away_team_id, 'Match should have correct away team ID');
        $this->assertEquals(1, $match->week, 'Match should have correct week number');
        $this->assertFalse($match->played, 'Match should be marked as unplayed');

        // Verify database state
        $this->assertDatabaseHas('matches', [
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'week' => 1,
            'played' => 0,
        ]);
    }
}
