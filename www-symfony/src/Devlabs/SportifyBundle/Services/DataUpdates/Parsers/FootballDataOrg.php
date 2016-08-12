<?php

namespace Devlabs\SportifyBundle\Services\DataUpdates\Parsers;

/**
 * Class FootballDataOrg
 * @package Devlabs\SportifyBundle\Services\DataUpdates\Parsers
 */
class FootballDataOrg
{
    /**
     * Method for parsing fetched Teams data
     *
     * @param array $teams
     * @return array
     */
    public function parseTeams(array $teams)
    {
        foreach ($teams as &$team) {
            $parsedTeam = array();

            $parsedTeam['team_id'] = $this->getNumberAtEndOfString($team->_links->self->href);
            $parsedTeam['name'] = $team->name;
            $parsedTeam['name_short'] = ($team->code) ? $team->code : 'TEAM_'.$parsedTeam['api_team_id'];

            $team = $parsedTeam;
        }

        return $teams;
    }

    /**
     * Method for parsing fetched Fixtures data
     *
     * @param array $fixtures
     * @return array
     */
    public function parseFixtures(array $fixtures)
    {
        foreach ($fixtures as &$fixture) {
            $parsedFixture = array();

            $parsedFixture['match_id'] = $this->getNumberAtEndOfString($fixture->_links->self->href);
            $parsedFixture['tournament_id'] = $this->getNumberAtEndOfString($fixture->_links->competition->href);
            $parsedFixture['home_team_id'] = $this->getNumberAtEndOfString($fixture->_links->homeTeam->href);
            $parsedFixture['away_team_id'] = $this->getNumberAtEndOfString($fixture->_links->awayTeam->href);
            $parsedFixture['match_local_time'] = date('Y-m-d H:i:s', strtotime($fixture->date));
            $parsedFixture['status'] = $fixture->status;

            if ($fixture->status === 'FINISHED') {
                $parsedFixture['home_team_goals'] = $fixture->result->goalsHomeTeam;
                $parsedFixture['away_team_goals'] = $fixture->result->goalsAwayTeam;
            } else {
                $parsedFixture['home_team_goals'] = null;
                $parsedFixture['away_team_goals'] = null;
            }

            if ($fixture->odds && ($fixture->odds !== 'null')) {
                $parsedFixture['odds_home_win'] = $fixture->odds->homeWin;
                $parsedFixture['odds_draw'] = $fixture->odds->draw;
                $parsedFixture['odds_away_win'] = $fixture->odds->awayWin;
            } else {
                $parsedFixture['odds_home_win'] = null;
                $parsedFixture['odds_draw'] = null;
                $parsedFixture['odds_away_win'] = null;
            }

            $fixture = $parsedFixture;
        }

        return $fixtures;
    }

    /**
     * Method for extracting a number at end of a string
     *
     * @param $subject
     * @return mixed
     */
    private function getNumberAtEndOfString($string)
    {
        preg_match('/\d+$/', $string, $matches);

        return $matches[0];
    }
}