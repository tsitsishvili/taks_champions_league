<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Team;
use App\Repositories\MatchRepository;
use App\Services\FixtureGeneratorService;
use Tests\TestCase;

class FixtureGeneratorTest extends TestCase
{
    protected FixtureGeneratorService $fixtureGenerator;

    protected MatchRepository $matchRepository;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->matchRepository = new MatchRepository;
        $this->fixtureGenerator = new FixtureGeneratorService($this->matchRepository);
    }

    /**
     * Tests the generation of a schedule pattern for teams.
     *
     * @scenario Generate a schedule pattern for an even number of teams (4 teams)
     * @expected The pattern should have (n-1) rounds, with n/2 matches per round
     *           Each team should play exactly once per round
     *           All teams should be included in each round
     *
     * @return void
     */
    public function testGenerateSchedulePattern(): void
    {
        // Arrange
        $teamCount = 4;

        // Act
        $pattern = $this->fixtureGenerator->generateSchedulePattern($teamCount);

        // Assert
        $this->assertIsArray($pattern, 'Pattern should be an array');
        $this->assertCount($teamCount - 1, $pattern, 'Pattern should have n-1 rounds');

        // Each round should have n/2 matches
        foreach ($pattern as $round) {
            $this->assertCount($teamCount / 2, $round, 'Each round should have n/2 matches');
        }

        // Check that each team plays exactly once per round
        foreach ($pattern as $roundIndex => $round) {
            $teamsInRound = [];
            foreach ($round as $match) {
                $teamsInRound[] = $match[0];
                $teamsInRound[] = $match[1];
            }

            // Sort and remove duplicates to check if all teams are included
            $uniqueTeams = array_unique($teamsInRound);
            sort($uniqueTeams);

            $this->assertEquals(range(0, $teamCount - 1), $uniqueTeams,
                "Round {$roundIndex} should include all teams exactly once");
        }
    }

    /**
     * Tests that an exception is thrown when generating a schedule pattern with an odd number of teams.
     *
     * @scenario Attempt to generate a schedule pattern with an odd number of teams (3 teams)
     * @expected An InvalidArgumentException should be thrown with the message 'Number of teams must be even'
     *
     * @return void
     */
    public function testGenerateSchedulePatternWithOddNumberOfTeams(): void
    {
        // Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Number of teams must be even');

        // Act
        $this->fixtureGenerator->generateSchedulePattern(3);
    }

    /**
     * Tests the creation of first half fixtures.
     *
     * @scenario Create first half fixtures for 4 teams
     * @expected The next week number should be 4 (for a 4-team league)
     *           6 matches should be created (3 rounds * 2 matches per round)
     *           All matches should be for weeks 1-3
     *           All matches should have valid home and away teams
     *
     * @return void
     */
    public function testCreateFirstHalfFixtures(): void
    {
        // Arrange
        Team::factory()->create(['name' => 'Team A']);
        Team::factory()->create(['name' => 'Team B']);
        Team::factory()->create(['name' => 'Team C']);
        Team::factory()->create(['name' => 'Team D']);

        $teams = Team::all();
        $teamCount = $teams->count();
        $pattern = $this->fixtureGenerator->generateSchedulePattern($teamCount);

        // Act
        $nextWeek = $this->fixtureGenerator->createFirstHalfFixtures($pattern, $teams);

        // Assert
        $expectedNextWeek = $teamCount;
        $this->assertEquals($expectedNextWeek, $nextWeek,
            "Next week should be {$expectedNextWeek} for a {$teamCount}-team league");

        $expectedMatchCount = ($teamCount - 1) * ($teamCount / 2);
        $rounds = $teamCount - 1;
        $matchesPerRound = $teamCount / 2;
        $this->assertEquals($expectedMatchCount, GameMatch::count(),
            "Should create {$expectedMatchCount} matches ({$rounds} rounds * {$matchesPerRound} matches per round)");

        $this->assertEquals(0, GameMatch::where('week', '>=', $expectedNextWeek)->count(),
            "All matches should be for weeks 1-" . ($expectedNextWeek - 1));

        // Assert that all matches have the correct home and away teams
        foreach (GameMatch::all() as $match) {
            $this->assertTrue($teams->contains('id', $match->home_team_id),
                "Home team ID {$match->home_team_id} should exist in the teams collection");
            $this->assertTrue($teams->contains('id', $match->away_team_id),
                "Away team ID {$match->away_team_id} should exist in the teams collection");
            $this->assertNotEquals($match->home_team_id, $match->away_team_id,
                "Home team ID should not equal away team ID");
        }
    }

    /**
     * Tests the creation of second half fixtures.
     *
     * @scenario Create second half fixtures for 4 teams starting from week 4
     * @expected The next week number should be 7 (for a 4-team league)
     *           6 matches should be created (3 rounds * 2 matches per round)
     *           All matches should be for weeks 4-6
     *           All matches should have valid home and away teams
     *
     * @return void
     */
    public function testCreateSecondHalfFixtures(): void
    {
        // Arrange
        Team::factory()->create(['name' => 'Team A']);
        Team::factory()->create(['name' => 'Team B']);
        Team::factory()->create(['name' => 'Team C']);
        Team::factory()->create(['name' => 'Team D']);

        $teams = Team::all();
        $teamCount = $teams->count();
        $pattern = $this->fixtureGenerator->generateSchedulePattern($teamCount);
        $startWeek = 4;

        // Act
        $nextWeek = $this->fixtureGenerator->createSecondHalfFixtures($pattern, $teams, $startWeek);

        // Assert
        $expectedNextWeek = $startWeek + $teamCount - 1;
        $this->assertEquals($expectedNextWeek, $nextWeek,
            "Next week should be {$expectedNextWeek} for a {$teamCount}-team league starting from week {$startWeek}");

        $expectedMatchCount = ($teamCount - 1) * ($teamCount / 2);
        $rounds = $teamCount - 1;
        $matchesPerRound = $teamCount / 2;
        $this->assertEquals($expectedMatchCount, GameMatch::count(),
            "Should create {$expectedMatchCount} matches ({$rounds} rounds * {$matchesPerRound} matches per round)");

        // Assert that all matches are for the second half (weeks 4-6)
        $this->assertEquals(0, GameMatch::where('week', '<', $startWeek)->count(),
            "All matches should be for week {$startWeek} or later");
        $this->assertEquals(0, GameMatch::where('week', '>=', $expectedNextWeek)->count(),
            "All matches should be before week {$expectedNextWeek}");

        // Assert that all matches have the correct home and away teams
        foreach (GameMatch::all() as $match) {
            $this->assertTrue($teams->contains('id', $match->home_team_id),
                "Home team ID {$match->home_team_id} should exist in the teams collection");
            $this->assertTrue($teams->contains('id', $match->away_team_id),
                "Away team ID {$match->away_team_id} should exist in the teams collection");
            $this->assertNotEquals($match->home_team_id, $match->away_team_id,
                "Home team ID should not equal away team ID");
        }
    }

    /**
     * Tests the generation of all fixtures for a complete season.
     *
     * @scenario Generate fixtures for 4 teams for a complete season (home and away matches)
     * @expected 12 matches should be created (4 teams * 3 opponents * 2 (home and away) / 2)
     *           Each team should play against every other team twice (once home, once away)
     *           There should be exactly 2 matches per week for 6 weeks
     *
     * @return void
     */
    public function testGenerateFixtures(): void
    {
        // Arrange
        Team::factory()->create(['name' => 'Team A']);
        Team::factory()->create(['name' => 'Team B']);
        Team::factory()->create(['name' => 'Team C']);
        Team::factory()->create(['name' => 'Team D']);

        $teams = Team::all();
        $teamCount = $teams->count();

        // Act
        $this->fixtureGenerator->generateFixtures($teams);

        // Assert
        $expectedMatchCount = $teamCount * ($teamCount - 1);
        $this->assertEquals($expectedMatchCount, GameMatch::count(),
            "Should create {$expectedMatchCount} matches for {$teamCount} teams");

        // Check that each team plays against every other team twice (once home, once away)
        foreach ($teams as $team) {
            foreach ($teams as $opponent) {
                if ($team->id != $opponent->id) {
                    // Check that there's one match where team is home and opponent is away
                    $homeMatchCount = GameMatch::where('home_team_id', $team->id)
                        ->where('away_team_id', $opponent->id)
                        ->count();
                    $this->assertEquals(1, $homeMatchCount,
                        "Team {$team->name} should play at home against {$opponent->name} exactly once");

                    // Check that there's one match where team is away and opponent is home
                    $awayMatchCount = GameMatch::where('home_team_id', $opponent->id)
                        ->where('away_team_id', $team->id)
                        ->count();
                    $this->assertEquals(1, $awayMatchCount,
                        "Team {$team->name} should play away against {$opponent->name} exactly once");
                }
            }
        }

        // Check that there are exactly 2 matches per week
        $weeks = GameMatch::select('week')->distinct()->get()->pluck('week')->toArray();
        $expectedWeekCount = ($teamCount - 1) * 2;
        $this->assertEquals($expectedWeekCount, count($weeks),
            "There should be {$expectedWeekCount} weeks of matches");

        $matchesPerWeek = $teamCount / 2;
        foreach ($weeks as $week) {
            $matchesInWeek = GameMatch::where('week', $week)->count();
            $this->assertEquals($matchesPerWeek, $matchesInWeek,
                "Week {$week} should have exactly {$matchesPerWeek} matches");
        }
    }
}
