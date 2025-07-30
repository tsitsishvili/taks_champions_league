<?php

namespace App\Http\Controllers;

use App\Services\LeagueService;
use Illuminate\Http\JsonResponse;

class LeagueController extends Controller
{
    protected LeagueService $leagueService;

    public function __construct()
    {
        $this->leagueService = app(LeagueService::class);
    }

    /**
     * Get league data including matches and table.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->leagueService->getLeagueData());
    }

    /**
     * Simulate all remaining matches.
     */
    public function simulate(): JsonResponse
    {
        $this->leagueService->simulateAllMatches();

        return response()->json(['success' => true]);
    }

    /**
     * Simulate the next week of matches.
     */
    public function simulateNextWeek(): JsonResponse
    {
        $result = $this->leagueService->simulateNextWeek();

        return response()->json($result);
    }

    /**
     * Reset all match results.
     */
    public function reset(): JsonResponse
    {
        $this->leagueService->resetMatches();

        return response()->json(['success' => true]);
    }

    /**
     * Initialize the league with teams and fixtures.
     */
    public function initialize(): JsonResponse
    {
        $this->leagueService->initialize();

        return response()->json(['success' => true]);
    }
}
