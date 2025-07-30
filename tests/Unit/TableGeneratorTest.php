<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Team;
use App\Services\TableGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableGeneratorTest extends TestCase
{
    protected TableGeneratorService $tableGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tableGenerator = new TableGeneratorService;
    }

    /**
     * Tests retrieving an empty league table when no teams exist in the database.
     *
     * @scenario Request a league table when the database has no teams
     * @expected An empty collection should be returned
     */
    public function testGetEmptyTable(): void
    {
        // Act
        $table = $this->tableGenerator->getTable();

        // Assert
        $this->assertEmpty($table, 'Table should be empty when no teams exist');
    }

    /**
     * Tests retrieving a league table when teams exist but no matches have been played.
     *
     * @scenario Request a league table when teams exist but no matches have been played
     * @expected The table should contain all teams with zero values for all statistics
     */
    public function testGetTableWithTeamsButNoMatches(): void
    {
        // Arrange
        Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        Team::factory()->create(['name' => 'Team B', 'strength' => 75]);

        // Act
        $table = $this->tableGenerator->getTable();

        // Assert
        // Verify the table has the correct number of teams
        $this->assertEquals(2, $table->count(), 'Table should contain 2 teams');

        // Verify that all stats are zero for each team
        foreach ($table as $index => $row) {
            $teamName = $row['team']->name;
            $this->assertEquals(0, $row['played'], "{$teamName} should have 0 played matches");
            $this->assertEquals(0, $row['wins'], "{$teamName} should have 0 wins");
            $this->assertEquals(0, $row['draws'], "{$teamName} should have 0 draws");
            $this->assertEquals(0, $row['losses'], "{$teamName} should have 0 losses");
            $this->assertEquals(0, $row['goals_for'], "{$teamName} should have 0 goals for");
            $this->assertEquals(0, $row['goals_against'], "{$teamName} should have 0 goals against");
            $this->assertEquals(0, $row['goal_difference'], "{$teamName} should have 0 goal difference");
            $this->assertEquals(0, $row['points'], "{$teamName} should have 0 points");
        }
    }

    /**
     * Tests retrieving a league table when teams exist and matches have been played.
     *
     * @scenario Request a league table when teams exist and matches have been played
     * @expected The table should contain all teams with correct statistics
     *           Teams should be sorted by points in descending order
     *           Each team's statistics should accurately reflect their match results
     */
    public function testGetTableWithTeamsAndMatches(): void
    {
        // Arrange
        // Create teams
        $teamA = Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        $teamB = Team::factory()->create(['name' => 'Team B', 'strength' => 75]);
        $teamC = Team::factory()->create(['name' => 'Team C', 'strength' => 70]);

        // Create matches with specific results
        // Team A: 1 win, 1 draw (4 points)
        GameMatch::factory()->create([
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'week' => 1,
            'played' => true,
            'home_score' => 3,
            'away_score' => 1,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $teamC->id,
            'away_team_id' => $teamA->id,
            'week' => 2,
            'played' => true,
            'home_score' => 2,
            'away_score' => 2,
        ]);

        // Team B: 1 win, 1 loss (3 points)
        GameMatch::factory()->create([
            'home_team_id' => $teamB->id,
            'away_team_id' => $teamC->id,
            'week' => 3,
            'played' => true,
            'home_score' => 2,
            'away_score' => 0,
        ]);

        // Team C: 1 draw, 1 loss (1 point)

        // Act
        $table = $this->tableGenerator->getTable();

        // Assert
        // Verify the table has the correct number of teams
        $this->assertEquals(3, $table->count(), 'Table should contain 3 teams');

        // Verify that the table is sorted by points (descending)
        $this->assertEquals($teamA->id, $table[0]['team']->id, 'Team A should be first with 4 points');
        $this->assertEquals($teamB->id, $table[1]['team']->id, 'Team B should be second with 3 points');
        $this->assertEquals($teamC->id, $table[2]['team']->id, 'Team C should be third with 1 point');

        // Verify Team A's statistics
        $this->assertEquals(2, $table[0]['played'], 'Team A should have played 2 matches');
        $this->assertEquals(1, $table[0]['wins'], 'Team A should have 1 win');
        $this->assertEquals(1, $table[0]['draws'], 'Team A should have 1 draw');
        $this->assertEquals(0, $table[0]['losses'], 'Team A should have 0 losses');
        $this->assertEquals(5, $table[0]['goals_for'], 'Team A should have 5 goals for');
        $this->assertEquals(3, $table[0]['goals_against'], 'Team A should have 3 goals against');
        $this->assertEquals(2, $table[0]['goal_difference'], 'Team A should have +2 goal difference');
        $this->assertEquals(4, $table[0]['points'], 'Team A should have 4 points');

        // Verify Team B's statistics
        $this->assertEquals(2, $table[1]['played'], 'Team B should have played 2 matches');
        $this->assertEquals(1, $table[1]['wins'], 'Team B should have 1 win');
        $this->assertEquals(0, $table[1]['draws'], 'Team B should have 0 draws');
        $this->assertEquals(1, $table[1]['losses'], 'Team B should have 1 loss');
        $this->assertEquals(3, $table[1]['goals_for'], 'Team B should have 3 goals for');
        $this->assertEquals(3, $table[1]['goals_against'], 'Team B should have 3 goals against');
        $this->assertEquals(0, $table[1]['goal_difference'], 'Team B should have 0 goal difference');
        $this->assertEquals(3, $table[1]['points'], 'Team B should have 3 points');

        // Verify Team C's statistics
        $this->assertEquals(2, $table[2]['played'], 'Team C should have played 2 matches');
        $this->assertEquals(0, $table[2]['wins'], 'Team C should have 0 wins');
        $this->assertEquals(1, $table[2]['draws'], 'Team C should have 1 draw');
        $this->assertEquals(1, $table[2]['losses'], 'Team C should have 1 loss');
        $this->assertEquals(2, $table[2]['goals_for'], 'Team C should have 2 goals for');
        $this->assertEquals(4, $table[2]['goals_against'], 'Team C should have 4 goals against');
        $this->assertEquals(-2, $table[2]['goal_difference'], 'Team C should have -2 goal difference');
        $this->assertEquals(1, $table[2]['points'], 'Team C should have 1 point');
    }

    /**
     * Tests that the league table is sorted correctly when teams have the same points.
     *
     * @scenario Create a scenario where two teams have the same points but different goal differences
     * @expected Teams with the same points should be sorted by goal difference (descending)
     *           Team A (3 points, +2 GD) should be ranked higher than Team B (3 points, +1 GD)
     */
    public function testTableSortingWithSamePoints(): void
    {
        // Arrange
        // Create teams
        $teamA = Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        $teamB = Team::factory()->create(['name' => 'Team B', 'strength' => 75]);
        $teamC = Team::factory()->create(['name' => 'Team C', 'strength' => 70]);

        // Create matches with specific results to create teams with same points
        // Team A: 1 win, 0 draws, 0 losses (3 points, +2 GD)
        GameMatch::factory()->create([
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamC->id,
            'week' => 1,
            'played' => true,
            'home_score' => 3,
            'away_score' => 1,
        ]);

        // Team B: 1 win, 0 draws, 0 losses (3 points, +1 GD)
        GameMatch::factory()->create([
            'home_team_id' => $teamB->id,
            'away_team_id' => $teamC->id,
            'week' => 2,
            'played' => true,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        // Team C: 0 wins, 0 draws, 2 losses (0 points)

        // Act
        $table = $this->tableGenerator->getTable();

        // Assert
        // Verify that the table is sorted by points (descending) and then by goal difference (descending)
        $this->assertEquals($teamA->id, $table[0]['team']->id,
            'Team A should be first with 3 points and +2 goal difference');
        $this->assertEquals($teamB->id, $table[1]['team']->id,
            'Team B should be second with 3 points and +1 goal difference');
        $this->assertEquals($teamC->id, $table[2]['team']->id,
            'Team C should be third with 0 points');

        // Verify the points and goal differences
        $this->assertEquals(3, $table[0]['points'], 'Team A should have 3 points');
        $this->assertEquals(2, $table[0]['goal_difference'], 'Team A should have +2 goal difference');

        $this->assertEquals(3, $table[1]['points'], 'Team B should have 3 points');
        $this->assertEquals(1, $table[1]['goal_difference'], 'Team B should have +1 goal difference');
    }

    /**
     * Test that the table is sorted correctly when teams have the same points and goal difference.
     */
    public function test_table_sorting_with_same_points_and_goal_difference()
    {
        // Create teams
        $teamA = Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        $teamB = Team::factory()->create(['name' => 'Team B', 'strength' => 75]);
        $teamC = Team::factory()->create(['name' => 'Team C', 'strength' => 70]);

        // Create matches
        // Team A: 1 win, 0 draws, 0 losses (3 points, +2 GD, 3 GF)
        GameMatch::factory()->create([
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamC->id,
            'week' => 1,
            'played' => true,
            'home_score' => 3,
            'away_score' => 1,
        ]);

        // Team B: 1 win, 0 draws, 0 losses (3 points, +2 GD, 2 GF)
        GameMatch::factory()->create([
            'home_team_id' => $teamB->id,
            'away_team_id' => $teamC->id,
            'week' => 2,
            'played' => true,
            'home_score' => 2,
            'away_score' => 0,
        ]);

        // Team C: 0 wins, 0 draws, 2 losses (0 points)

        $table = $this->tableGenerator->getTable();

        // Assert that the table is sorted by points (descending), then by goal difference (descending), then by goals for (descending)
        $this->assertEquals($teamA->id, $table[0]['team']->id); // Team A: 3 points, +2 GD, 3 GF
        $this->assertEquals($teamB->id, $table[1]['team']->id); // Team B: 3 points, +2 GD, 2 GF
        $this->assertEquals($teamC->id, $table[2]['team']->id); // Team C: 0 points
    }

    /**
     * Test that the table includes positions.
     */
    public function test_table_includes_positions()
    {
        // Create teams
        $teamA = Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        $teamB = Team::factory()->create(['name' => 'Team B', 'strength' => 75]);

        // Create a match
        GameMatch::factory()->create([
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'week' => 1,
            'played' => true,
            'home_score' => 3,
            'away_score' => 1,
        ]);

        $table = $this->tableGenerator->getTable();

        // Assert that positions are included and correct
        $this->assertEquals(1, $table[0]['position']);
        $this->assertEquals(2, $table[1]['position']);
    }
}
