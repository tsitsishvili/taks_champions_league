<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameMatch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'week' => $this->faker->numberBetween(1, 10),
            'home_score' => $this->faker->optional(0.8, null)->numberBetween(0, 5),
            'away_score' => $this->faker->optional(0.8, null)->numberBetween(0, 5),
            'played' => $this->faker->boolean(80), // 80% chance of being played
        ];
    }

    /**
     * Indicate that the match has been played.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function played()
    {
        return $this->state(function (array $attributes) {
            return [
                'played' => true,
                'home_score' => $this->faker->numberBetween(0, 5),
                'away_score' => $this->faker->numberBetween(0, 5),
            ];
        });
    }

    /**
     * Indicate that the match has not been played.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function notPlayed()
    {
        return $this->state(function (array $attributes) {
            return [
                'played' => false,
                'home_score' => null,
                'away_score' => null,
            ];
        });
    }
}
