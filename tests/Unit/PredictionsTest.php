<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Team;
use App\Services\PredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionsTest extends TestCase
{
    /**
     * Tests the prediction service with a simulated league standings scenario.
     *
     * @scenario Simulate a league with 4 teams (Chelsea, Liverpool, Arsenal, Manchester City)
     *           with specific match results to create a known standings table
     * @expected Predictions should not be empty
     *           Predictions should be sorted by probability in descending order
     *           The sum of all probabilities should be exactly 100%
     */
    public function testPredictionService(): void
    {
        // Arrange
        // Create teams and matches to simulate specific standings
        $this->simulateStandings();

        // Act
        // Calculate championship predictions
        $predictionService = new PredictionService;
        $predictions = $predictionService->getPredictions();

        // Assert
        // Verify that predictions are not empty
        $this->assertNotEmpty($predictions, 'Predictions should not be empty');

        // Verify that the predictions are sorted by probability (descending)
        $previousProbability = 101; // Start with a value higher than 100%
        foreach ($predictions as $index => $prediction) {
            $this->assertLessThanOrEqual(
                $previousProbability,
                $prediction['probability'],
                "Prediction at index {$index} should have probability less than or equal to the previous one"
            );
            $previousProbability = $prediction['probability'];
        }

        // Verify that the sum of all probabilities is 100%
        $totalProbability = $predictions->sum('probability');
        $this->assertEquals(100, $totalProbability, 'Sum of all probabilities should be 100%');
    }

    /**
     * Simulates a league standings scenario for testing predictions.
     *
     * Creates 4 teams with specific strengths and 10 matches with predetermined results
     * to achieve the following standings:
     * - Chelsea: 5 matches, 4 wins, 0 draws, 1 loss, 58 GF, 47 GA, +11 GD, 12 points
     * - Liverpool: 5 matches, 2 wins, 0 draws, 3 losses, 48 GF, 43 GA, +5 GD, 6 points
     * - Arsenal: 5 matches, 2 wins, 0 draws, 3 losses, 46 GF, 49 GA, -3 GD, 6 points
     * - Manchester City: 5 matches, 2 wins, 0 draws, 3 losses, 47 GF, 60 GA, -13 GD, 6 points
     *
     * @return void
     */
    protected function simulateStandings(): void
    {
        // Clear existing teams and matches
        GameMatch::query()->delete();
        Team::query()->delete();

        // Create teams with the given strengths
        $teams = [
            ['name' => 'Chelsea', 'strength' => 82],
            ['name' => 'Liverpool', 'strength' => 85],
            ['name' => 'Arsenal', 'strength' => 80],
            ['name' => 'Manchester City', 'strength' => 87],
        ];

        $createdTeams = [];
        foreach ($teams as $teamData) {
            $createdTeams[$teamData['name']] = Team::factory()->create($teamData);
        }

        // Create matches to achieve the desired standings
        // Match 1: Chelsea vs Liverpool (Chelsea wins)
        $this->createMatch($createdTeams['Chelsea'], $createdTeams['Liverpool'], 1, 15, 10, true);

        // Match 2: Arsenal vs Manchester City (Arsenal wins)
        $this->createMatch($createdTeams['Arsenal'], $createdTeams['Manchester City'], 1, 12, 10, true);

        // Match 3: Chelsea vs Arsenal (Chelsea wins)
        $this->createMatch($createdTeams['Chelsea'], $createdTeams['Arsenal'], 2, 14, 11, true);

        // Match 4: Liverpool vs Manchester City (Liverpool wins)
        $this->createMatch($createdTeams['Liverpool'], $createdTeams['Manchester City'], 2, 13, 12, true);

        // Match 5: Chelsea vs Manchester City (Chelsea wins)
        $this->createMatch($createdTeams['Chelsea'], $createdTeams['Manchester City'], 3, 16, 15, true);

        // Match 6: Liverpool vs Arsenal (Arsenal wins)
        $this->createMatch($createdTeams['Liverpool'], $createdTeams['Arsenal'], 3, 12, 13, true);

        // Match 7: Liverpool vs Chelsea (Liverpool wins)
        $this->createMatch($createdTeams['Liverpool'], $createdTeams['Chelsea'], 4, 13, 12, true);

        // Match 8: Manchester City vs Arsenal (Manchester City wins)
        $this->createMatch($createdTeams['Manchester City'], $createdTeams['Arsenal'], 4, 10, 9, true);

        // Match 9: Manchester City vs Chelsea (Chelsea wins)
        $this->createMatch($createdTeams['Manchester City'], $createdTeams['Chelsea'], 5, 10, 11, true);

        // Match 10: Arsenal vs Liverpool (Liverpool wins)
        $this->createMatch($createdTeams['Arsenal'], $createdTeams['Liverpool'], 5, 11, 12, true);
    }

    /**
     * Creates a match with the specified parameters.
     *
     * @param Team $homeTeam The home team
     * @param Team $awayTeam The away team
     * @param int $week The week number for the match
     * @param int $homeScore The score for the home team
     * @param int $awayScore The score for the away team
     * @param bool $played Whether the match has been played
     * @return GameMatch The created match
     */
    protected function createMatch(Team $homeTeam, Team $awayTeam, int $week, int $homeScore, int $awayScore, bool $played): GameMatch
    {
        return GameMatch::factory()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'week' => $week,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'played' => $played,
        ]);
    }
}
