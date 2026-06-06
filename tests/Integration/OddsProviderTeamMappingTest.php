<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

use Devlabs\SportifyBundle\Entity\OddsProviderTeamMapping;
use Devlabs\SportifyBundle\Services\Odds\OddsProbabilityNormalizer;
use Devlabs\SportifyBundle\Services\Odds\TheOddsApi;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\Container;

class OddsProviderTeamMappingTest extends DatabaseTestCase
{
    public function testAutoLearnsAndReusesProviderTeamMappings()
    {
        $tournament = $this->createTournament('FIFA World Cup');
        $homeTeam = $this->createTeam('United States', $tournament);
        $awayTeam = $this->createTeam('South Africa', $tournament);
        $api = $this->createApi(array(
            $this->sportsResponse(),
            $this->eventsResponse('USA', 'RSA'),
            $this->oddsResponse('USA', 'RSA'),
            $this->eventsResponse('USA', 'RSA'),
            $this->oddsResponse('USA', 'RSA'),
        ));

        $fixtureData = $this->fixtureData(array(
            'home_team_name' => 'United States',
            'home_team_short_name' => 'USA',
            'home_team_tla' => 'USA',
            'home_team_area_name' => 'United States',
            'home_team_area_code' => 'USA',
            'away_team_name' => 'South Africa',
            'away_team_short_name' => 'RSA',
            'away_team_tla' => 'RSA',
            'away_team_area_name' => 'South Africa',
            'away_team_area_code' => 'RSA',
        ));

        $this->assertNotNull($api->findProbabilitiesForFixture($fixtureData, $tournament, $homeTeam, $awayTeam));

        $usaMapping = $this->em->getRepository(OddsProviderTeamMapping::class)
            ->getByTournamentProviderAndNormalizedName($tournament->getId(), TheOddsApi::PROVIDER, 'usa');
        $rsaMapping = $this->em->getRepository(OddsProviderTeamMapping::class)
            ->getByTournamentProviderAndNormalizedName($tournament->getId(), TheOddsApi::PROVIDER, 'rsa');

        $this->assertSame($homeTeam->getId(), $usaMapping->getTeamId());
        $this->assertSame($awayTeam->getId(), $rsaMapping->getTeamId());

        $this->assertNotNull($api->findProbabilitiesForFixture($this->fixtureData(), $tournament, $homeTeam, $awayTeam));
    }

    private function createApi(array $responses)
    {
        $container = new Container();
        $container->setParameter('odds_api.token', 'test-token');
        $client = new Client(array('handler' => HandlerStack::create(new MockHandler($responses))));

        return new TheOddsApi($container, new OddsProbabilityNormalizer(), 'https://api.example.test', $client, null, $this->em);
    }

    private function sportsResponse()
    {
        return new Response(200, array(), json_encode(array(
            array('key' => 'soccer_fifa_world_cup'),
        )));
    }

    private function eventsResponse($homeTeam, $awayTeam)
    {
        return new Response(200, array(), json_encode(array(
            array(
                'id' => 'event-1',
                'commence_time' => '2026-06-11T19:00:00Z',
                'home_team' => $homeTeam,
                'away_team' => $awayTeam,
            ),
        )));
    }

    private function oddsResponse($homeTeam, $awayTeam)
    {
        return new Response(200, array(), json_encode(array(
            'id' => 'event-1',
            'bookmakers' => array(
                array(
                    'key' => 'pinnacle',
                    'markets' => array(
                        array(
                            'key' => 'h2h',
                            'outcomes' => array(
                                array('name' => $homeTeam, 'price' => 2.0),
                                array('name' => 'Draw', 'price' => 4.0),
                                array('name' => $awayTeam, 'price' => 4.0),
                            ),
                        ),
                    ),
                ),
            ),
        )));
    }

    private function fixtureData(array $data = array())
    {
        return array_merge(array(
            'match_id' => 500,
            'match_local_time' => '2026-06-11 19:00:00',
        ), $data);
    }
}
