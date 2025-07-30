<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamServiceTest extends TestCase
{
    protected TeamService $teamService;

    protected Team $team;

    protected Team $opponent1;

    protected Team $opponent2;

    /**
     * Set up the test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->teamService = new TeamService();

        // Create teams for testing
        $this->team = Team::factory()->create(['name' => 'Test Team', 'strength' => 80]);
        $this->opponent1 = Team::factory()->create(['name' => 'Opponent 1', 'strength' => 75]);
        $this->opponent2 = Team::factory()->create(['name' => 'Opponent 2', 'strength' => 70]);
    }

    /**
     * Helper method to create a match with specific parameters
     *
     * @param bool $isHome Whether the team is playing at home
     * @param int $teamScore The score of the team being tested
     * @param int $opponentScore The score of the opponent
     * @param int $week The week number
     * @param Team|null $opponent The opponent team (defaults to opponent1)
     * @param bool $played Whether the match has been played
     * @return GameMatch
     */
    private function createMatch(
        bool $isHome,
        int $teamScore,
        int $opponentScore,
        int $week = 1,
        ?Team $opponent = null,
        bool $played = true
    ): GameMatch {
        $opponent = $opponent ?? $this->opponent1;

        return GameMatch::factory()->create([
            'home_team_id' => $isHome ? $this->team->id : $opponent->id,
            'away_team_id' => $isHome ? $opponent->id : $this->team->id,
            'week' => $week,
            'played' => $played,
            'home_score' => $isHome ? $teamScore : $opponentScore,
            'away_score' => $isHome ? $opponentScore : $teamScore,
        ]);
    }

    /**
     * Helper method to assert that team stats match expected values
     *
     * @param array $stats The stats to check
     * @param int $played Expected number of played matches
     * @param int $wins Expected number of wins
     * @param int $draws Expected number of draws
     * @param int $losses Expected number of losses
     * @param int $goalsFor Expected number of goals scored
     * @param int $goalsAgainst Expected number of goals conceded
     * @param int $goalDifference Expected goal difference
     * @param int $points Expected number of points
     * @return void
     */
    private function assertTeamStats(
        array $stats,
        int $played,
        int $wins,
        int $draws,
        int $losses,
        int $goalsFor,
        int $goalsAgainst,
        int $goalDifference,
        int $points
    ): void {
        $this->assertEquals($played, $stats['played'], 'Played matches count should match');
        $this->assertEquals($wins, $stats['wins'], 'Wins count should match');
        $this->assertEquals($draws, $stats['draws'], 'Draws count should match');
        $this->assertEquals($losses, $stats['losses'], 'Losses count should match');
        $this->assertEquals($goalsFor, $stats['goals_for'], 'Goals for should match');
        $this->assertEquals($goalsAgainst, $stats['goals_against'], 'Goals against should match');
        $this->assertEquals($goalDifference, $stats['goal_difference'], 'Goal difference should match');
        $this->assertEquals($points, $stats['points'], 'Points should match');
    }

    /**
     * Test that a team with no matches has zero stats
     *
     * @return void
     */
    public function test_get_stats_with_no_matches(): void
    {
        // Act
        $stats = $this->teamService->getStats($this->team);

        // Assert
        $this->assertTeamStats(
            $stats,
            played: 0,
            wins: 0,
            draws: 0,
            losses: 0,
            goalsFor: 0,
            goalsAgainst: 0,
            goalDifference: 0,
            points: 0
        );
    }

    /**
     * Test that a team's stats correctly reflect a home win
     *
     * @return void
     */
    public function test_get_stats_with_home_wins(): void
    {
        // Arrange
        $this->createMatch(isHome: true, teamScore: 3, opponentScore: 1);

        // Act
        $stats = $this->teamService->getStats($this->team);

        // Assert
        $this->assertTeamStats(
            $stats,
            played: 1,
            wins: 1,
            draws: 0,
            losses: 0,
            goalsFor: 3,
            goalsAgainst: 1,
            goalDifference: 2,
            points: 3
        );
    }

    /**
     * Test that a team's stats correctly reflect an away win
     *
     * @return void
     */
    public function test_get_stats_with_away_wins(): void
    {
        // Arrange
        $this->createMatch(isHome: false, teamScore: 3, opponentScore: 1);

        // Act
        $stats = $this->teamService->getStats($this->team);

        // Assert
        $this->assertTeamStats(
            $stats,
            played: 1,
            wins: 1,
            draws: 0,
            losses: 0,
            goalsFor: 3,
            goalsAgainst: 1,
            goalDifference: 2,
            points: 3
        );
    }

    /**
     * Test that a team's stats correctly reflect a home draw
     *
     * @return void
     */
    public function test_get_stats_with_home_draws(): void
    {
        // Arrange
        $this->createMatch(isHome: true, teamScore: 2, opponentScore: 2);

        // Act
        $stats = $this->teamService->getStats($this->team);

        // Assert
        $this->assertTeamStats(
            $stats,
            played: 1,
            wins: 0,
            draws: 1,
            losses: 0,
            goalsFor: 2,
            goalsAgainst: 2,
            goalDifference: 0,
            points: 1
        );
    }

    /**
     * Test that a team's stats correctly reflect an away draw
     *
     * @return void
     */
    public function test_get_stats_with_away_draws(): void
    {
        // Arrange
        $this->createMatch(isHome: false, teamScore: 2, opponentScore: 2);

        // Act
        $stats = $this->teamService->getStats($this->team);

        // Assert
        $this->assertTeamStats(
            $stats,
            played: 1,
            wins: 0,
            draws: 1,
            losses: 0,
            goalsFor: 2,
            goalsAgainst: 2,
            goalDifference: 0,
            points: 1
        );
    }

    /**
     * Test that a team's stats correctly reflect a home loss
     *
     * @return void
     */
    public function test_get_stats_with_home_losses(): void
    {
        // Arrange
        $this->createMatch(isHome: true, teamScore: 1, opponentScore: 3);

        // Act
        $stats = $this->teamService->getStats($this->team);

        // Assert
        $this->assertTeamStats(
            $stats,
            played: 1,
            wins: 0,
            draws: 0,
            losses: 1,
            goalsFor: 1,
            goalsAgainst: 3,
            goalDifference: -2,
            points: 0
        );
    }

    /**
     * Test that a team's stats correctly reflect an away loss
     *
     * @return void
     */
    public function test_get_stats_with_away_losses(): void
    {
        // Arrange
        $this->createMatch(isHome: false, teamScore: 1, opponentScore: 3);

        // Act
        $stats = $this->teamService->getStats($this->team);

        // Assert
        $this->assertTeamStats(
            $stats,
            played: 1,
            wins: 0,
            draws: 0,
            losses: 1,
            goalsFor: 1,
            goalsAgainst: 3,
            goalDifference: -2,
            points: 0
        );
    }

    /**
     * Test that a team's stats correctly aggregate multiple matches with different results
     *
     * @return void
     */
    public function test_get_stats_with_multiple_matches(): void
    {
        // Arrange - Create multiple matches with different results

        // Home win (week 1)
        $this->createMatch(isHome: true, teamScore: 3, opponentScore: 1, week: 1);

        // Away draw (week 2)
        $this->createMatch(isHome: false, teamScore: 2, opponentScore: 2, week: 2, opponent: $this->opponent2);

        // Home loss (week 3)
        $this->createMatch(isHome: true, teamScore: 0, opponentScore: 2, week: 3, opponent: $this->opponent2);

        // Away win (week 4)
        $this->createMatch(isHome: false, teamScore: 3, opponentScore: 1, week: 4);

        // Act
        $stats = $this->teamService->getStats($this->team);

        // Assert
        $this->assertTeamStats(
            $stats,
            played: 4,
            wins: 2,
            draws: 1,
            losses: 1,
            goalsFor: 8,
            goalsAgainst: 6,
            goalDifference: 2,
            points: 7
        );
    }

    /**
     * Test that unplayed matches are not included in team stats
     *
     * @return void
     */
    public function test_unplayed_matches_not_included_in_stats(): void
    {
        // Arrange
        // Create a played match
        $this->createMatch(isHome: true, teamScore: 3, opponentScore: 1, week: 1);

        // Create an unplayed match
        $this->createMatch(
            isHome: false,
            teamScore: 0,
            opponentScore: 0,
            week: 2,
            opponent: $this->opponent2,
            played: false
        );

        // Act
        $stats = $this->teamService->getStats($this->team);

        // Assert
        $this->assertTeamStats(
            $stats,
            played: 1,
            wins: 1,
            draws: 0,
            losses: 0,
            goalsFor: 3,
            goalsAgainst: 1,
            goalDifference: 2,
            points: 3
        );
    }
}
