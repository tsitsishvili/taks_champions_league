<?php

namespace App\Repositories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class TeamRepository
{
    /**
     * Get all teams.
     */
    public function getAllTeams(): Collection
    {
        return Team::all();
    }

    /**
     * Create a new team.
     */
    public function createTeam(array $data): Team
    {
        return Team::create($data);
    }

    /**
     * Delete all teams.
     */
    public function deleteAllTeams(): void
    {
        Team::query()->delete();
    }
}
