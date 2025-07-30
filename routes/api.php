<?php

use App\Http\Controllers\LeagueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// League routes
Route::get('/league', [LeagueController::class, 'index']);
Route::post('/league/simulate', [LeagueController::class, 'simulate']);
Route::post('/league/simulate-next-week', [LeagueController::class, 'simulateNextWeek']);
Route::post('/league/reset', [LeagueController::class, 'reset']);
Route::post('/league/initialize', [LeagueController::class, 'initialize']);
