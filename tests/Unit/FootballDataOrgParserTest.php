<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Services\DataUpdates\Parsers\FootballDataOrg;

class FootballDataOrgParserTest extends \PHPUnit\Framework\TestCase
{
    public function testParseTeamsMapsApiObjectsToImportRows()
    {
        $team = new \stdClass();
        $team->id = 10;
        $team->name = 'Team A';
        $team->crestUrl = 'https://example.com/team-a.svg';

        $parser = new FootballDataOrg();
        $parsed = $parser->parseTeams(array($team));

        $this->assertSame(array(
            array(
                'team_id' => 10,
                'name' => 'Team A',
                'team_logo' => 'https://example.com/team-a.svg',
            ),
        ), $parsed);
    }

    public function testParseTournamentsMapsApiObjectsToImportRows()
    {
        $tournament = new \stdClass();
        $tournament->id = 2000;
        $tournament->name = 'Competition';

        $parser = new FootballDataOrg();
        $parsed = $parser->parseTournaments(array($tournament));

        $this->assertSame(array(
            array(
                'id' => 2000,
                'name' => 'Competition',
            ),
        ), $parsed);
    }

    public function testParseFixturesMapsScheduledAndFinishedMatches()
    {
        $scheduled = $this->createFixture(1, 'SCHEDULED', '2020-01-02T12:00:00Z');
        $finished = $this->createFixture(2, 'FINISHED', '2020-01-03T12:00:00Z', 4, 3, 1, 1, 1, 0);

        $parser = new FootballDataOrg();
        $parsed = $parser->parseFixtures(array($scheduled, $finished));

        $this->assertSame(array(
            'match_id' => 1,
            'tournament_id' => 99,
            'home_team_id' => 10,
            'away_team_id' => 20,
            'match_local_time' => date('Y-m-d H:i:s', strtotime('2020-01-02T12:00:00Z')),
            'status' => 'SCHEDULED',
            'home_team_goals' => null,
            'away_team_goals' => null,
        ), $parsed[0]);

        $this->assertSame(array(
            'match_id' => 2,
            'tournament_id' => 99,
            'home_team_id' => 10,
            'away_team_id' => 20,
            'match_local_time' => date('Y-m-d H:i:s', strtotime('2020-01-03T12:00:00Z')),
            'status' => 'FINISHED',
            'home_team_goals' => 2,
            'away_team_goals' => 2,
        ), $parsed[1]);
    }

    private function createFixture($id, $status, $utcDate, $fullTimeHome = null, $fullTimeAway = null, $extraTimeHome = null, $extraTimeAway = null, $penaltiesHome = null, $penaltiesAway = null)
    {
        $fixture = new \stdClass();
        $fixture->id = $id;
        $fixture->utcDate = $utcDate;
        $fixture->status = $status;

        $fixture->season = new \stdClass();
        $fixture->season->id = 99;

        $fixture->homeTeam = new \stdClass();
        $fixture->homeTeam->id = 10;

        $fixture->awayTeam = new \stdClass();
        $fixture->awayTeam->id = 20;

        $fixture->score = new \stdClass();
        $fixture->score->fullTime = new \stdClass();
        $fixture->score->fullTime->homeTeam = $fullTimeHome;
        $fixture->score->fullTime->awayTeam = $fullTimeAway;
        $fixture->score->extraTime = new \stdClass();
        $fixture->score->extraTime->homeTeam = $extraTimeHome;
        $fixture->score->extraTime->awayTeam = $extraTimeAway;
        $fixture->score->penalties = new \stdClass();
        $fixture->score->penalties->homeTeam = $penaltiesHome;
        $fixture->score->penalties->awayTeam = $penaltiesAway;

        return $fixture;
    }
}
