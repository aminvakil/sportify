<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Services\Odds\OddsProbabilityNormalizer;
use Devlabs\SportifyBundle\Services\Odds\TheOddsApi;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class TheOddsApiTest extends TestCase
{
    public function testFetchesWorldCupEventProbabilitySnapshots()
    {
        $api = $this->createApi(array(
            new Response(200, array(), json_encode(array(
                array(
                    'id' => 'event-1',
                    'commence_time' => '2026-06-11T19:00:00Z',
                    'home_team' => 'Mexico',
                    'away_team' => 'South Africa',
                ),
            ))),
            new Response(200, array(), json_encode(array(
                'id' => 'event-1',
                'bookmakers' => array(
                    array(
                        'key' => 'pinnacle',
                        'markets' => array(
                            array(
                                'key' => 'h2h',
                                'outcomes' => array(
                                    array('name' => 'Mexico', 'price' => 2.0),
                                    array('name' => 'Draw', 'price' => 4.0),
                                    array('name' => 'South Africa', 'price' => 4.0),
                                ),
                            ),
                        ),
                    ),
                ),
            ))),
        ));

        $events = $api->fetchEventProbabilitySnapshots(
            'soccer_fifa_world_cup',
            new \DateTimeImmutable('2026-06-11 00:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-06-13 00:00:00', new \DateTimeZone('UTC'))
        );

        $this->assertSame(array(array(
            'event_id' => 'event-1',
            'commence_time' => '2026-06-11T19:00:00Z',
            'home_team' => 'Mexico',
            'away_team' => 'South Africa',
            'snapshot' => array(
                'home_win_probability_percent' => 50,
                'draw_probability_percent' => 25,
                'away_win_probability_percent' => 25,
                'source' => 'the_odds_api:soccer_fifa_world_cup:event-1:pinnacle:h2h',
            ),
        )), $events);
    }

    public function testReturnsNullWhenTokenIsNotConfigured()
    {
        $api = $this->createApi(array(), 'check_the_README_file');

        $this->assertFalse($api->hasConfiguredApiToken());
        $this->assertNull($api->fetchEventProbabilitySnapshots(
            'soccer_fifa_world_cup',
            new \DateTimeImmutable('2026-06-11 00:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-06-13 00:00:00', new \DateTimeZone('UTC'))
        ));
    }

    private function createApi(array $responses, $token = 'test-token')
    {
        $container = new Container();
        $container->setParameter('odds_api.token', $token);
        $client = new Client(array('handler' => HandlerStack::create(new MockHandler($responses))));

        return new TheOddsApi($container, new OddsProbabilityNormalizer(), 'https://api.example.test', $client);
    }
}
