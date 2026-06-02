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

    public function testParseTeamsAcceptsV4CrestProperty()
    {
        $team = new \stdClass();
        $team->id = 10;
        $team->name = 'Team A';
        $team->crest = 'https://example.com/team-a.svg';

        $parser = new FootballDataOrg();
        $parsed = $parser->parseTeams(array($team));

        $this->assertSame('https://example.com/team-a.svg', $parsed[0]['team_logo']);
    }

    public function testParseTournamentsMapsApiObjectsToImportRows()
    {
        $tournament = new \stdClass();
        $tournament->id = 2000;
        $tournament->name = 'Competition';
        $tournament->emblem = 'https://example.com/competition.png';

        $parser = new FootballDataOrg();
        $parsed = $parser->parseTournaments(array($tournament));

        $this->assertSame(array(
            array(
                'id' => 2000,
                'name' => 'Competition',
                'logo' => 'https://example.com/competition.png',
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

    public function testParseFixturesAcceptsV4ScoreProperties()
    {
        $finished = $this->createFixture(2, 'FINISHED', '2020-01-03T12:00:00Z', 4, 3, null, null, null, null, true);

        $parser = new FootballDataOrg();
        $parsed = $parser->parseFixtures(array($finished));

        $this->assertSame(4, $parsed[0]['home_team_goals']);
        $this->assertSame(3, $parsed[0]['away_team_goals']);
    }

    public function testParseFixturesStoresNinetyMinuteScoreWhenExtraTimeOrPenaltiesContainZeroes()
    {
        $finished = $this->createFixture(3, 'FINISHED', '2020-01-03T12:00:00Z', 3, 3, 0, 1, 1, 0);

        $parser = new FootballDataOrg();
        $parsed = $parser->parseFixtures(array($finished));

        $this->assertSame(2, $parsed[0]['home_team_goals']);
        $this->assertSame(2, $parsed[0]['away_team_goals']);
    }

    public function testParseFixturesSkipsUnresolvedTeams()
    {
        $valid = $this->createFixture(1, 'SCHEDULED', '2020-01-02T12:00:00Z');
        $v4Unresolved = $this->createFixture(2, 'SCHEDULED', '2020-01-03T12:00:00Z', null, null, null, null, null, null, false, null, 20);
        $v2Placeholder = $this->createFixture(3, 'SCHEDULED', '2020-01-04T12:00:00Z', null, null, null, null, null, null, false, 10, 757);

        $parser = new FootballDataOrg();
        $parsed = $parser->parseFixtures(array($valid, $v4Unresolved, $v2Placeholder));

        $this->assertCount(1, $parsed);
        $this->assertSame(1, $parsed[0]['match_id']);
    }

    private function createFixture($id, $status, $utcDate, $fullTimeHome = null, $fullTimeAway = null, $extraTimeHome = null, $extraTimeAway = null, $penaltiesHome = null, $penaltiesAway = null, $useV4ScoreProperties = false, $homeTeamId = 10, $awayTeamId = 20)
    {
        $fixture = new \stdClass();
        $fixture->id = $id;
        $fixture->utcDate = $utcDate;
        $fixture->status = $status;

        $fixture->season = new \stdClass();
        $fixture->season->id = 99;

        $fixture->homeTeam = new \stdClass();
        $fixture->homeTeam->id = $homeTeamId;

        $fixture->awayTeam = new \stdClass();
        $fixture->awayTeam->id = $awayTeamId;

        $fixture->score = new \stdClass();
        $fixture->score->fullTime = new \stdClass();
        $fixture->score->extraTime = new \stdClass();
        $fixture->score->penalties = new \stdClass();

        if ($useV4ScoreProperties) {
            $fixture->score->fullTime->home = $fullTimeHome;
            $fixture->score->fullTime->away = $fullTimeAway;
            $fixture->score->extraTime->home = $extraTimeHome;
            $fixture->score->extraTime->away = $extraTimeAway;
            $fixture->score->penalties->home = $penaltiesHome;
            $fixture->score->penalties->away = $penaltiesAway;
        } else {
            $fixture->score->fullTime->homeTeam = $fullTimeHome;
            $fixture->score->fullTime->awayTeam = $fullTimeAway;
            $fixture->score->extraTime->homeTeam = $extraTimeHome;
            $fixture->score->extraTime->awayTeam = $extraTimeAway;
            $fixture->score->penalties->homeTeam = $penaltiesHome;
            $fixture->score->penalties->awayTeam = $penaltiesAway;
        }

        return $fixture;
    }
}
