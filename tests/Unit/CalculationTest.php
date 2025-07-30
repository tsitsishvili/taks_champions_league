<?php

namespace Tests\Unit;

use Tests\TestCase;

class CalculationTest extends TestCase
{
    /**
     * Tests the calculation of championship probabilities based on team statistics.
     *
     * @scenario Calculate championship probabilities for 4 teams with different points,
     *           goal differences, and strength ratings
     * @expected Probabilities should be calculated correctly based on weighted factors
     *           Chelsea should have the highest probability (33%)
     *           Liverpool should be second (26%)
     *           Arsenal should be third (22%)
     *           Manchester City should be fourth (19%)
     */
    public function testChampionshipProbabilityCalculation(): void
    {
        // Arrange
        // Standings data with team statistics
        $standings = [
            'Chelsea' => [
                'points' => 12,
                'goal_difference' => 11,
                'strength' => 82,
            ],
            'Liverpool' => [
                'points' => 6,
                'goal_difference' => 5,
                'strength' => 85,
            ],
            'Arsenal' => [
                'points' => 6,
                'goal_difference' => -3,
                'strength' => 80,
            ],
            'Manchester City' => [
                'points' => 6,
                'goal_difference' => -13,
                'strength' => 87,
            ],
        ];

        // Define weight factors for different statistics
        $pointsWeight = 4;
        $goalDifferenceWeight = 2;
        $strengthWeight = 1;

        // Act
        // Calculate weighted scores
        $weightedScores = [];
        $totalWeightedScore = 0;

        foreach ($standings as $team => $stats) {
            $weightedScore =
                ($stats['points'] * $pointsWeight) +
                ($stats['goal_difference'] * $goalDifferenceWeight) +
                ($stats['strength'] * $strengthWeight);

            $weightedScores[$team] = $weightedScore;
            $totalWeightedScore += $weightedScore;
        }

        // Calculate probabilities
        $probabilities = [];
        foreach ($weightedScores as $team => $score) {
            $probabilities[$team] = round(($score / $totalWeightedScore) * 100);
        }

        // Sort by probability (descending)
        arsort($probabilities);

        // Assert
        // Expected probabilities based on the calculation formula
        $expectedProbabilities = [
            'Chelsea' => 33,
            'Liverpool' => 26,
            'Arsenal' => 22,
            'Manchester City' => 19,
        ];

        // Verify each team's probability matches the expected value
        foreach ($probabilities as $team => $probability) {
            $this->assertEquals(
                $expectedProbabilities[$team],
                $probability,
                "Probability for {$team} should be {$expectedProbabilities[$team]}% but got {$probability}%"
            );
        }

        // Verify the sum of probabilities is 100%
        $this->assertEquals(100, array_sum($probabilities), "Sum of all probabilities should be 100%");
    }
}
