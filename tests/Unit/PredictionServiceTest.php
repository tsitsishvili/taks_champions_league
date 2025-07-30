<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Team;
use App\Services\PredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionServiceTest extends TestCase
{
    protected PredictionService $predictionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->predictionService = new PredictionService;
    }

    /**
     * Tests championship predictions when all matches are played and there is a clear winner.
     *
     * @scenario All matches are played with Team A winning all of its matches
     * @expected Team A should have 100% probability of winning the championship
     *           All other teams should have 0% probability
     */
    public function testPredictionsWithAllMatchesPlayed(): void
    {
        // Arrange
        // Create teams with different strengths
        $teamA = Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        $teamB = Team::factory()->create(['name' => 'Team B', 'strength' => 75]);
        $teamC = Team::factory()->create(['name' => 'Team C', 'strength' => 70]);
        $teamD = Team::factory()->create(['name' => 'Team D', 'strength' => 65]);

        // Create matches where Team A wins all matches
        $this->createMatchWithResult($teamA, $teamB, 3, 1);
        $this->createMatchWithResult($teamA, $teamC, 2, 0);
        $this->createMatchWithResult($teamA, $teamD, 4, 2);
        $this->createMatchWithResult($teamB, $teamA, 1, 2);
        $this->createMatchWithResult($teamC, $teamA, 0, 1);
        $this->createMatchWithResult($teamD, $teamA, 1, 3);

        // Create matches between other teams
        $this->createMatchWithResult($teamB, $teamC, 2, 2);
        $this->createMatchWithResult($teamB, $teamD, 3, 1);
        $this->createMatchWithResult($teamC, $teamB, 1, 2);
        $this->createMatchWithResult($teamC, $teamD, 2, 1);
        $this->createMatchWithResult($teamD, $teamB, 0, 2);
        $this->createMatchWithResult($teamD, $teamC, 1, 1);

        // Act
        // Get championship predictions
        $predictions = $this->predictionService->getPredictions();

        // Assert
        // Find Team A's prediction
        $teamAPrediction = $predictions->first(function ($prediction) use ($teamA) {
            return $prediction['team']->id === $teamA->id;
        });

        // Verify Team A has 100% probability
        $this->assertNotNull($teamAPrediction, 'Team A should be in the predictions');
        $this->assertEquals(100, $teamAPrediction['probability'],
            'Team A should have 100% probability of winning the championship');

        // Verify all other teams have 0% probability
        foreach ($predictions as $prediction) {
            if ($prediction['team']->id !== $teamA->id) {
                $this->assertEquals(0, $prediction['probability'],
                    "Team {$prediction['team']->name} should have 0% probability");
            }
        }
    }

    /**
     * Tests championship predictions when only some matches are played.
     *
     * @scenario Some matches are played and some are still unplayed
     * @expected All teams should have a non-zero probability of winning
     *           The sum of all probabilities should be exactly 100%
     *           Team A should have the highest probability (most points and highest strength)
     */
    public function testPredictionsWithSomeMatchesPlayed(): void
    {
        // Arrange
        // Create teams with different strengths
        $teamA = Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        $teamB = Team::factory()->create(['name' => 'Team B', 'strength' => 75]);
        $teamC = Team::factory()->create(['name' => 'Team C', 'strength' => 70]);
        $teamD = Team::factory()->create(['name' => 'Team D', 'strength' => 65]);

        // Create some played matches
        $this->createMatchWithResult($teamA, $teamB, 3, 1); // Team A wins
        $this->createMatchWithResult($teamC, $teamD, 2, 2); // Draw

        // Create some unplayed matches
        $this->createUnplayedMatch($teamA, $teamC);
        $this->createUnplayedMatch($teamA, $teamD);
        $this->createUnplayedMatch($teamB, $teamC);
        $this->createUnplayedMatch($teamB, $teamD);

        // Act
        // Get championship predictions
        $predictions = $this->predictionService->getPredictions();

        // Assert
        // Verify predictions are not empty
        $this->assertNotEmpty($predictions, 'Predictions should not be empty');

        // Verify the sum of all probabilities is 100%
        $totalProbability = $predictions->sum('probability');
        $this->assertEquals(100, $totalProbability, 'Sum of all probabilities should be 100%');

        // Verify Team A has the highest probability
        $this->assertEquals($teamA->id, $predictions->first()['team']->id,
            'Team A should have the highest probability');

        // Verify all teams have a non-zero probability
        foreach ($predictions as $prediction) {
            $this->assertGreaterThan(0, $prediction['probability'],
                "Team {$prediction['team']->name} should have a non-zero probability");
        }
    }

    /**
     * Tests championship predictions when some teams have no mathematical chance to win.
     *
     * @scenario Create a scenario where Team A has many points, Team B and C have some points,
     *           and Team D has no points and no mathematical chance to win
     * @expected Team D should have 0% probability of winning
     *           Team A should have the highest probability
     *           Teams with a mathematical chance should have non-zero probabilities
     */
    public function testPredictionsWithNoMathematicalChance(): void
    {
        // Arrange
        // Create teams with different strengths
        $teamA = Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        $teamB = Team::factory()->create(['name' => 'Team B', 'strength' => 75]);
        $teamC = Team::factory()->create(['name' => 'Team C', 'strength' => 70]);
        $teamD = Team::factory()->create(['name' => 'Team D', 'strength' => 65]);

        // Create matches with specific results to create a scenario where Team D has no chance

        // Team A: 9 points (3 wins)
        $this->createMatchWithResult($teamA, $teamB, 3, 1); // Team A wins
        $this->createMatchWithResult($teamA, $teamC, 2, 0); // Team A wins
        $this->createMatchWithResult($teamA, $teamD, 4, 2); // Team A wins

        // Team B: 4 points (1 win, 1 draw)
        $this->createMatchWithResult($teamB, $teamC, 2, 2); // Draw
        $this->createMatchWithResult($teamB, $teamD, 3, 1); // Team B wins

        // Team C: 1 point (1 draw)
        $this->createMatchWithResult($teamC, $teamD, 1, 1); // Draw

        // Team D: 0 points (all losses or draws)

        // Create one unplayed match
        $this->createUnplayedMatch($teamB, $teamA);

        // Act
        // Get championship predictions
        $predictions = $this->predictionService->getPredictions();

        // Assert
        // Find Team D's prediction
        $teamDPrediction = $predictions->first(function ($prediction) use ($teamD) {
            return $prediction['team']->id === $teamD->id;
        });

        // Verify Team D has 0% probability
        $this->assertNotNull($teamDPrediction, 'Team D should be in the predictions');
        $this->assertEquals(0, $teamDPrediction['probability'],
            'Team D should have 0% probability (no mathematical chance)');

        // Verify Team A has the highest probability
        $this->assertEquals($teamA->id, $predictions->first()['team']->id,
            'Team A should have the highest probability');
    }

    /**
     * Helper method to create a match with a specific result.
     *
     * Creates a played match between two teams with the specified scores.
     * If no week is provided, it uses an auto-incrementing counter starting from 1.
     *
     * @param Team $homeTeam The home team
     * @param Team $awayTeam The away team
     * @param int $homeScore The score for the home team
     * @param int $awayScore The score for the away team
     * @param int|null $week The week number for the match (optional)
     * @return GameMatch The created match
     */
    private function createMatchWithResult(Team $homeTeam, Team $awayTeam, int $homeScore, int $awayScore, ?int $week = null): GameMatch
    {
        static $weekCounter = 1;

        if ($week === null) {
            $week = $weekCounter++;
        }

        return GameMatch::factory()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'week' => $week,
            'played' => true,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ]);
    }

    /**
     * Helper method to create an unplayed match.
     *
     * Creates an unplayed match between two teams.
     * If no week is provided, it uses an auto-incrementing counter starting from 100
     * to avoid conflicts with played matches.
     *
     * @param Team $homeTeam The home team
     * @param Team $awayTeam The away team
     * @param int|null $week The week number for the match (optional)
     * @return GameMatch The created match
     */
    private function createUnplayedMatch(Team $homeTeam, Team $awayTeam, ?int $week = null): GameMatch
    {
        static $weekCounter = 100; // Start from a high number to avoid conflicts

        if ($week === null) {
            $week = $weekCounter++;
        }

        return GameMatch::factory()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'week' => $week,
            'played' => false,
        ]);
    }
}
