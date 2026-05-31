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
            $parsedTeam['team_logo'] = property_exists($team, 'crest') ? $team->crest : $team->crestUrl;

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
                $parsedFixture['home_team_goals'] = $this->getTeamScore($fixture->score->fullTime, 'home');
                $parsedFixture['away_team_goals'] = $this->getTeamScore($fixture->score->fullTime, 'away');
                $extraTimeHomeGoals = property_exists($fixture->score, 'extraTime') ? $this->getTeamScore($fixture->score->extraTime, 'home') : null;
                if ($extraTimeHomeGoals != null) {
                    $parsedFixture['home_team_goals'] = $parsedFixture['home_team_goals'] - $extraTimeHomeGoals;
                    $parsedFixture['away_team_goals'] = $parsedFixture['away_team_goals'] - $this->getTeamScore($fixture->score->extraTime, 'away');
                }
                $penaltyHomeGoals = property_exists($fixture->score, 'penalties') ? $this->getTeamScore($fixture->score->penalties, 'home') : null;
                if ($penaltyHomeGoals != null) {
                    $parsedFixture['home_team_goals'] = $parsedFixture['home_team_goals'] - $penaltyHomeGoals;
                    $parsedFixture['away_team_goals'] = $parsedFixture['away_team_goals'] - $this->getTeamScore($fixture->score->penalties, 'away');
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

    private function getTeamScore($score, $team)
    {
        $property = property_exists($score, $team) ? $team : $team.'Team';

        return $score->$property;
    }
}
