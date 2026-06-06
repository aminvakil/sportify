<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Entity\Tournament;
use Devlabs\SportifyBundle\Services\Odds\OddsProbabilityNormalizer;
use Devlabs\SportifyBundle\Services\Odds\TheOddsApi;
use Devlabs\SportifyBundle\Services\Telegram;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class TheOddsApiTest extends TestCase
{
    public function testFindsProbabilitySnapshotForFixture()
    {
        $api = $this->createApi(array(
            $this->sportsResponse(),
            $this->eventsResponse(),
            $this->oddsResponse(array(
                array('name' => 'Mexico', 'price' => 2.0),
                array('name' => 'Draw', 'price' => 4.0),
                array('name' => 'South Africa', 'price' => 4.0),
            )),
        ));

        $this->assertSame(array(
            'home_win_probability_percent' => 50,
            'draw_probability_percent' => 25,
            'away_win_probability_percent' => 25,
            'source' => 'the_odds_api:soccer_fifa_world_cup:event-1:pinnacle:h2h',
        ), $api->findProbabilitiesForFixture(
            $this->fixtureData(),
            $this->tournament(),
            $this->team('Mexico'),
            $this->team('South Africa')
        ));
    }

    public function testMatchesProviderTeamsWithFootballDataMetadata()
    {
        $api = $this->createApi(array(
            $this->sportsResponse(),
            $this->eventsResponse('USA', 'RSA'),
            $this->oddsResponse(array(
                array('name' => 'USA', 'price' => 2.0),
                array('name' => 'Draw', 'price' => 4.0),
                array('name' => 'RSA', 'price' => 4.0),
            )),
        ));

        $this->assertSame(array(
            'home_win_probability_percent' => 50,
            'draw_probability_percent' => 25,
            'away_win_probability_percent' => 25,
            'source' => 'the_odds_api:soccer_fifa_world_cup:event-1:pinnacle:h2h',
        ), $api->findProbabilitiesForFixture(
            $this->fixtureData(500, array(
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
            )),
            $this->tournament(),
            $this->team('United States'),
            $this->team('South Africa')
        ));
    }

    public function testNotifiesAdminWhenProviderTeamMappingIsMissing()
    {
        $telegram = new FakeTheOddsApiAdminTelegram();
        $api = $this->createApi(array(
            $this->sportsResponse(),
            $this->eventsResponse('USA', 'South Africa'),
        ), 'test-token', $telegram);

        $this->assertNull($api->findProbabilitiesForFixture(
            $this->fixtureData(),
            $this->tournament(),
            $this->team('United States'),
            $this->team('South Africa')
        ));

        $api->flushOddsUnavailableNotifications();

        $this->assertCount(1, $telegram->adminMessages);
        $this->assertStringContainsString('Reason: No safely matched The Odds API event was found.', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Missing The Odds API team mapping for provider team "USA".', $telegram->adminMessages[0]);
    }

    public function testThrowsWhenOddsEndpointFails()
    {
        $telegram = new FakeTheOddsApiAdminTelegram();
        $api = $this->createApi(array(
            $this->sportsResponse(),
            $this->eventsResponse(),
            new Response(429),
        ), 'test-token', $telegram);

        try {
            $api->findProbabilitiesForFixture(
                $this->fixtureData(),
                $this->tournament(),
                $this->team('Mexico'),
                $this->team('South Africa')
            );
            $this->fail('Expected The Odds API failure to throw.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('The Odds API request failed for "/v4/sports/soccer_fifa_world_cup/events/event-1/odds" (HTTP 429', $e->getMessage());
        }

        $this->assertCount(0, $telegram->adminMessages);
        $api->flushOddsUnavailableNotifications();

        $this->assertCount(1, $telegram->adminMessages);
        $this->assertStringContainsString('Fixture skipped because odds snapshot is unavailable.', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Match: Mexico vs South Africa', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Reason: The Odds API request failed for "/v4/sports/soccer_fifa_world_cup/events/event-1/odds" (HTTP 429', $telegram->adminMessages[0]);
    }

    public function testReturnsNullForSuccessfulOddsResponseWithoutCompleteNinetyMinuteSnapshot()
    {
        $telegram = new FakeTheOddsApiAdminTelegram();
        $api = $this->createApi(array(
            $this->sportsResponse(),
            $this->eventsResponse(),
            $this->oddsResponse(array(
                array('name' => 'Mexico', 'price' => 2.0),
                array('name' => 'South Africa', 'price' => 4.0),
            )),
        ), 'test-token', $telegram);

        $this->assertNull($api->findProbabilitiesForFixture(
            $this->fixtureData(),
            $this->tournament(),
            $this->team('Mexico'),
            $this->team('South Africa')
        ));
        $this->assertCount(0, $telegram->adminMessages);

        $api->flushOddsUnavailableNotifications();

        $this->assertCount(1, $telegram->adminMessages);
        $this->assertStringContainsString('Fixture skipped because odds snapshot is unavailable.', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Football-Data match id: 500', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Reason: No complete home/draw/away odds snapshot was found.', $telegram->adminMessages[0]);
    }

    public function testBatchesUnavailableOddsNotificationsUntilFlush()
    {
        $telegram = new FakeTheOddsApiAdminTelegram();
        $api = $this->createApi(array(), 'check_the_README_file', $telegram);

        $this->assertNull($api->findProbabilitiesForFixture(
            $this->fixtureData(500),
            $this->tournament(),
            $this->team('Mexico'),
            $this->team('South Africa')
        ));
        $this->assertNull($api->findProbabilitiesForFixture(
            $this->fixtureData(501),
            $this->tournament(),
            $this->team('Canada'),
            $this->team('Morocco')
        ));
        $this->assertCount(0, $telegram->adminMessages);

        $api->flushOddsUnavailableNotifications();

        $this->assertCount(1, $telegram->adminMessages);
        $this->assertStringContainsString('2 fixtures skipped because odds snapshots are unavailable.', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Match: Mexico vs South Africa', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Football-Data match id: 500', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Match: Canada vs Morocco', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Football-Data match id: 501', $telegram->adminMessages[0]);
    }

    public function testRequestsSoccerHeadToHeadMarketWithDrawInsteadOfOutrightsOutcome()
    {
        $history = array();
        $api = $this->createApiWithHistory(array(
            $this->sportsResponse(),
            $this->eventsResponse(),
            $this->oddsResponse(array(
                array('name' => 'Mexico', 'price' => 2.0),
                array('name' => 'Draw', 'price' => 4.0),
                array('name' => 'South Africa', 'price' => 4.0),
            )),
        ), $history);

        $snapshot = $api->findProbabilitiesForFixture(
            $this->fixtureData(),
            $this->tournament(),
            $this->team('Mexico'),
            $this->team('South Africa')
        );

        $this->assertSame('the_odds_api:soccer_fifa_world_cup:event-1:pinnacle:h2h', $snapshot['source']);
        $oddsRequest = $history[2]['request'];
        parse_str($oddsRequest->getUri()->getQuery(), $query);
        $this->assertSame('h2h', $query['markets']);
        $this->assertNotSame('outrights', $query['markets']);
    }

    public function testReturnsNullWhenTokenIsNotConfigured()
    {
        $api = $this->createApi(array(), 'check_the_README_file');

        $this->assertNull($api->findProbabilitiesForFixture(
            $this->fixtureData(),
            $this->tournament(),
            $this->team('Mexico'),
            $this->team('South Africa')
        ));
    }

    private function createApi(array $responses, $token = 'test-token', ?Telegram $telegram = null)
    {
        $container = new Container();
        $container->setParameter('odds_api.token', $token);
        $client = new Client(array('handler' => HandlerStack::create(new MockHandler($responses))));

        return new TheOddsApi($container, new OddsProbabilityNormalizer(), 'https://api.example.test', $client, $telegram);
    }

    private function createApiWithHistory(array $responses, array &$history)
    {
        $container = new Container();
        $container->setParameter('odds_api.token', 'test-token');
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($history));
        $client = new Client(array('handler' => $handler));

        return new TheOddsApi($container, new OddsProbabilityNormalizer(), 'https://api.example.test', $client);
    }

    private function sportsResponse()
    {
        return new Response(200, array(), json_encode(array(
            array('key' => 'soccer_fifa_world_cup'),
        )));
    }

    private function eventsResponse($homeTeam = 'Mexico', $awayTeam = 'South Africa')
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

    private function oddsResponse(array $outcomes)
    {
        return new Response(200, array(), json_encode(array(
            'id' => 'event-1',
            'bookmakers' => array(
                array(
                    'key' => 'pinnacle',
                    'markets' => array(
                        array(
                            'key' => 'h2h',
                            'outcomes' => $outcomes,
                        ),
                    ),
                ),
            ),
        )));
    }

    private function fixtureData($matchId = 500, array $data = array())
    {
        return array_merge(array(
            'match_id' => $matchId,
            'match_local_time' => '2026-06-11 19:00:00',
        ), $data);
    }

    private function tournament()
    {
        $tournament = new Tournament();
        $tournament->setName('FIFA World Cup');

        return $tournament;
    }

    private function team($name)
    {
        $team = new Team();
        $team->setName($name);

        return $team;
    }
}

class FakeTheOddsApiAdminTelegram extends Telegram
{
    public $adminMessages = array();

    public function __construct()
    {
    }

    public function sendAdminMessage($text)
    {
        $this->adminMessages[] = $text;

        return new Response(200);
    }
}
