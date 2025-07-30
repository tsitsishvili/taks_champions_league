<?php

namespace App\Services;

use Illuminate\Support\Collection;

class TableGeneratorService
{
    private TeamService $teamService;

    /**
     * TableGenerator constructor.
     */
    public function __construct()
    {
        $this->teamService = app(TeamService::class);
    }

    /**
     * Get the league table.
     */
    public function getTable(): Collection
    {
        $teams = $this->teamService->getAllTeams();
        $tableData = [];

        foreach ($teams as $team) {
            $stats = $this->teamService->getStats($team);

            $tableData[] = [
                'team' => $team,
                'played' => $stats['played'],
                'wins' => $stats['wins'],
                'draws' => $stats['draws'],
                'losses' => $stats['losses'],
                'goals_for' => $stats['goals_for'],
                'goals_against' => $stats['goals_against'],
                'goal_difference' => $stats['goal_difference'],
                'points' => $stats['points'],
            ];
        }

        // Sort the table by points (desc), goal difference (desc), goals for (desc), wins (desc)
        $sortedTable = collect($tableData)
            ->sortBy([
                ['points', 'desc'],
                ['goal_difference', 'desc'],
                ['goals_for', 'desc'],
                ['wins', 'desc'],
            ])
            ->values();

        // Add positions to the table
        $position = 1;
        $sortedTable->transform(function ($item) use (&$position) {
            $item['position'] = $position++;

            return $item;
        });

        return $sortedTable;
    }
}
