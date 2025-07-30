<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Repositories\TeamRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamRepositoryTest extends TestCase
{
    protected TeamRepository $teamRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamRepository = new TeamRepository();
    }

    /**
     * Test that the repository can retrieve all teams from the database
     *
     * @return void
     */
    public function test_get_all_teams(): void
    {
        // Arrange
        Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        Team::factory()->create(['name' => 'Team B', 'strength' => 75]);

        // Act
        $teams = $this->teamRepository->getAllTeams();

        // Assert
        $this->assertEquals(
            2,
            $teams->count(),
            'Repository should return the correct number of teams'
        );

        $this->assertTrue(
            $teams->contains('name', 'Team A'),
            'Repository should return Team A'
        );

        $this->assertTrue(
            $teams->contains('name', 'Team B'),
            'Repository should return Team B'
        );
    }

    /**
     * Test that the repository can create a new team with the provided attributes
     *
     * @return void
     */
    public function test_create_team(): void
    {
        // Arrange
        $teamData = [
            'name' => 'Test Team',
            'strength' => 85,
        ];

        // Act
        $team = $this->teamRepository->createTeam($teamData);

        // Assert
        $this->assertEquals(
            'Test Team',
            $team->name,
            'Created team should have the correct name'
        );

        $this->assertEquals(
            85,
            $team->strength,
            'Created team should have the correct strength'
        );

        $this->assertDatabaseHas('teams', $teamData);
    }

    /**
     * Test that the repository can delete all teams from the database
     *
     * @return void
     */
    public function test_delete_all_teams(): void
    {
        // Arrange
        Team::factory()->create(['name' => 'Team A', 'strength' => 80]);
        Team::factory()->create(['name' => 'Team B', 'strength' => 75]);

        $this->assertEquals(
            2,
            Team::query()->count(),
            'Setup should create exactly 2 teams'
        );

        // Act
        $this->teamRepository->deleteAllTeams();

        // Assert
        $this->assertEquals(
            0,
            Team::query()->count(),
            'All teams should be deleted from the database'
        );
    }
}
