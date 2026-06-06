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
            $parsedTeam['team_logo'] = $this->getImageUrl($team, array('crest', 'crestUrl'));
            $parsedTeam = array_merge($parsedTeam, $this->getTeamMetadata($team));

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
        $parsedFixtures = array();

        foreach ($fixtures as $fixture) {
            $parsedFixture = array();

            $parsedFixture['match_id'] = $fixture->id;
            $parsedFixture['tournament_id'] = $fixture->season->id;
            $parsedFixture['home_team_id'] = $fixture->homeTeam->id;
            $parsedFixture['away_team_id'] = $fixture->awayTeam->id;
            $parsedFixture = array_merge($parsedFixture, $this->getFixtureTeamMetadata($fixture->homeTeam, 'home'));
            $parsedFixture = array_merge($parsedFixture, $this->getFixtureTeamMetadata($fixture->awayTeam, 'away'));

            // NOTE: v2 used team id 757 as a placeholder, while v4 uses null
            // team ids for unresolved scheduled teams.
            if ($parsedFixture['home_team_id'] === null || $parsedFixture['away_team_id'] === null ||
                $parsedFixture['home_team_id'] == 757 || $parsedFixture['away_team_id'] == 757) {
                continue;
            }

            $parsedFixture['match_local_time'] = date('Y-m-d H:i:s', strtotime($fixture->utcDate));
            $parsedFixture['status'] = $fixture->status;

            if ($fixture->status === 'FINISHED') {
                $parsedFixture['home_team_goals'] = $this->getTeamScore($fixture->score->fullTime, 'home');
                $parsedFixture['away_team_goals'] = $this->getTeamScore($fixture->score->fullTime, 'away');
                $extraTimeHomeGoals = property_exists($fixture->score, 'extraTime') ? $this->getTeamScore($fixture->score->extraTime, 'home') : null;
                if ($extraTimeHomeGoals !== null) {
                    $parsedFixture['home_team_goals'] = $parsedFixture['home_team_goals'] - $extraTimeHomeGoals;
                    $parsedFixture['away_team_goals'] = $parsedFixture['away_team_goals'] - $this->getTeamScore($fixture->score->extraTime, 'away');
                }
                $penaltyHomeGoals = property_exists($fixture->score, 'penalties') ? $this->getTeamScore($fixture->score->penalties, 'home') : null;
                if ($penaltyHomeGoals !== null) {
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
            $parsedFixtures[] = $parsedFixture;
        }

        return $parsedFixtures;
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
            $tournament = $this->parseTournament($tournament);
        }

        return $tournaments;
    }

    public function parseTournament($tournament)
    {
        return array(
            'id' => $tournament->id,
            'name' => $tournament->name,
            'logo' => $this->getImageUrl($tournament, array('emblem', 'emblemUrl')),
        );
    }

    private function getTeamScore($score, $team)
    {
        $property = property_exists($score, $team) ? $team : $team.'Team';

        return $score->$property;
    }

    private function getFixtureTeamMetadata($team, $prefix)
    {
        $metadata = array();
        foreach ($this->getTeamMetadata($team) as $key => $value) {
            $metadata[$prefix.'_team_'.$key] = $value;
        }

        return $metadata;
    }

    private function getTeamMetadata($team)
    {
        $metadata = array();
        foreach (array('name', 'shortName', 'tla') as $property) {
            if (property_exists($team, $property) && $team->$property) {
                $metadata[$this->camelToSnake($property)] = $team->$property;
            }
        }

        if (property_exists($team, 'area') && is_object($team->area)) {
            if (property_exists($team->area, 'name') && $team->area->name) {
                $metadata['area_name'] = $team->area->name;
            }
            if (property_exists($team->area, 'code') && $team->area->code) {
                $metadata['area_code'] = $team->area->code;
            }
        }

        return $metadata;
    }

    private function camelToSnake($name)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    private function getImageUrl($object, array $properties)
    {
        foreach ($properties as $property) {
            if (property_exists($object, $property) && $object->$property) {
                return $object->$property;
            }
        }

        return null;
    }
}
