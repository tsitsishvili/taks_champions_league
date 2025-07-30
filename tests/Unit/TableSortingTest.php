<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Team;
use App\Services\TableGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableSortingTest extends TestCase
{
    private array $teams;

    private TableGeneratorService $tableGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tableGenerator = new TableGeneratorService();

        // Create teams with different points and goal differences
        $this->teams = [
            'A' => Team::factory()->create(['name' => 'Team A']),
            'B' => Team::factory()->create(['name' => 'Team B']),
            'C' => Team::factory()->create(['name' => 'Team C']),
            'D' => Team::factory()->create(['name' => 'Team D']),
        ];

        $this->createMatchesWithDifferentResults();
    }

    /**
     * Create matches with different results to test table sorting
     */
    private function createMatchesWithDifferentResults(): void
    {
        // Team A: 6 points, GD +4 (2 wins)
        GameMatch::factory()->played()->create([
            'home_team_id' => $this->teams['A']->id,
            'away_team_id' => $this->teams['B']->id,
            'home_score' => 3,
            'away_score' => 1,
            'week' => 1,
        ]);

        GameMatch::factory()->played()->create([
            'home_team_id' => $this->teams['A']->id,
            'away_team_id' => $this->teams['C']->id,
            'home_score' => 2,
            'away_score' => 0,
            'week' => 2,
        ]);

        // Team B: 3 points, GD +1 (1 win, 1 loss)
        GameMatch::factory()->played()->create([
            'home_team_id' => $this->teams['B']->id,
            'away_team_id' => $this->teams['D']->id,
            'home_score' => 2,
            'away_score' => 0,
            'week' => 3,
        ]);

        // Team C: 3 points, GD 0 (1 win, 1 loss)
        GameMatch::factory()->played()->create([
            'home_team_id' => $this->teams['C']->id,
            'away_team_id' => $this->teams['D']->id,
            'home_score' => 1,
            'away_score' => 1,
            'week' => 4,
        ]);
    }

    /**
     * Test that the table is not empty after matches are played
     */
    public function test_table_is_not_empty(): void
    {
        // Act
        $table = $this->tableGenerator->getTable();

        // Assert
        $this->assertNotEmpty($table, 'Table should not be empty after matches are played');
    }

    /**
     * Test that the table is sorted by points in descending order
     */
    public function test_table_is_sorted_by_points_descending(): void
    {
        // Act
        $table = $this->tableGenerator->getTable();

        // Assert
        $previousPoints = PHP_INT_MAX; // Start with a high value
        foreach ($table as $row) {
            $this->assertLessThanOrEqual(
                $previousPoints,
                $row['points'],
                'Teams should be sorted by points in descending order'
            );
            $previousPoints = $row['points'];
        }
    }

    /**
     * Test that teams with the same points are sorted by goal difference in descending order
     */
    public function test_teams_with_same_points_sorted_by_goal_difference(): void
    {
        // Act
        $table = $this->tableGenerator->getTable();

        // Arrange teams by points
        $pointGroups = [];
        foreach ($table as $row) {
            $pointGroups[$row['points']][] = $row;
        }

        // Assert
        foreach ($pointGroups as $points => $rows) {
            if (count($rows) > 1) {
                $previousGD = PHP_INT_MAX; // Start with a high value
                foreach ($rows as $row) {
                    $this->assertLessThanOrEqual(
                        $previousGD,
                        $row['goal_difference'],
                        "Teams with {$points} points should be sorted by goal difference in descending order"
                    );
                    $previousGD = $row['goal_difference'];
                }
            }
        }
    }
}
