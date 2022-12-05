<?php

namespace Devlabs\SportifyBundle\Services\DataUpdates\Parsers;

/**
 * Class FootballDataOrg
 * @package Devlabs\SportifyBundle\Services\DataUpdates\Parsers
 */
class FootballDataOrg
{
    /**
     * Parse fetched Teams data
     *
     * @param array $teams
     * @return array
     */
    public function parseTeams(array $teams)
    {
        foreach ($teams as &$team) {
            $parsedTeam = array();

            $parsedTeam['team_id'] = $team->id;
            $parsedTeam['name'] = $team->name;
            $parsedTeam['team_logo'] = $team->crestUrl;

            $team = $parsedTeam;
        }

        return $teams;
    }

    /**
     * Parse fetched Fixtures data
     *
     * @param array $fixtures
     * @return array
     */
    public function parseFixtures(array $fixtures)
    {
        foreach ($fixtures as &$fixture) {
            $parsedFixture = array();

            $parsedFixture['match_id'] = $fixture->id;
            $parsedFixture['tournament_id'] = $fixture->season->id;
            $parsedFixture['home_team_id'] = $fixture->homeTeam->id;
            $parsedFixture['away_team_id'] = $fixture->awayTeam->id;

            // NOTE: team id 757 is just a placeholder used in this API,
            // when match is scheduled, but teams are still not clear.
            // This occurs in scheduled knock-out round matches.
            if ($parsedFixture['home_team_id'] == 757 || $parsedFixture['away_team_id'] == 757) {
                continue;
            }

            $parsedFixture['match_local_time'] = date('Y-m-d H:i:s', strtotime($fixture->utcDate));
            $parsedFixture['status'] = $fixture->status;

            if ($fixture->status === 'FINISHED') {
                $parsedFixture['home_team_goals'] = $fixture->score->fullTime->homeTeam;
                $parsedFixture['away_team_goals'] = $fixture->score->fullTime->awayTeam;
                if ($fixture->score->extraTime->homeTeam != null) {
                    $parsedFixture['home_team_goals'] = $parsedFixture['home_team_goals'] - $fixture->score->extraTime->homeTeam;
                    $parsedFixture['away_team_goals'] = $parsedFixture['away_team_goals'] - $fixture->score->extraTime->awayTeam;
                }
                if ($fixture->score->penalties->homeTeam != null) {
                    $parsedFixture['home_team_goals'] = $parsedFixture['home_team_goals'] - $fixture->score->penalties->homeTeam;
                    $parsedFixture['away_team_goals'] = $parsedFixture['away_team_goals'] - $fixture->score->penalties->awayTeam;
                }
            } else {
                $parsedFixture['home_team_goals'] = null;
                $parsedFixture['away_team_goals'] = null;
            }

/*            if ($fixture->odds && ($fixture->odds !== 'null')) {
                $parsedFixture['odds_home_win'] = $fixture->odds->homeWin;
                $parsedFixture['odds_draw'] = $fixture->odds->draw;
                $parsedFixture['odds_away_win'] = $fixture->odds->awayWin;
            } else {
                $parsedFixture['odds_home_win'] = null;
                $parsedFixture['odds_draw'] = null;
                $parsedFixture['odds_away_win'] = null;
            }
*/
            $fixture = $parsedFixture;
        }

        return $fixtures;
    }

    /**
     * Parse fetched tournaments data
     *
     * @param array $tournaments
     * @return array
     */
    public function parseTournaments(array $tournaments)
    {
        foreach ($tournaments as &$tournament) {
            $parsedTournament = array();

            $parsedTournament['id'] = $tournament->id;
            $parsedTournament['name'] = $tournament->name;
            $tournament = $parsedTournament;
        }

        return $tournaments;
    }

    /**
     * Extract a number located at the end of a string
     *
     * @param $string
     * @return mixed
     */
    private function getNumberAtEndOfString($string)
    {
        preg_match('/\d+$/', $string, $matches);

        return $matches[0];
    }
}
