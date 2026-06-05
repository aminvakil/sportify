<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Services\DataUpdates\Fetchers\FootballDataOrg;
use Devlabs\SportifyBundle\Services\Telegram;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class FootballDataOrgFetcherTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessResponseAddsFlashAndReturnsEmptyArrayForNonOkResponse()
    {
        $request = Request::create('/admin/data_updates/teams');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $fetcher = new FootballDataOrg($requestStack, 'https://api.football-data.org/v4', 'test-token');

        $this->assertSame(array(), $fetcher->processResponse(new Response(500), 'teams'));
        $this->assertSame(
            array('Football-Data request failed (HTTP 500 Internal Server Error).'),
            $request->getSession()->getFlashBag()->peek('message')
        );
    }

    public function testProcessResponseThrowsForNonOkResponseWithoutRequestSession()
    {
        $fetcher = new FootballDataOrg(new RequestStack(), 'https://api.football-data.org/v4', 'test-token');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Football-Data request failed (HTTP 429 Too Many Requests).');

        $fetcher->processResponse(new Response(429), 'teams');
    }

    public function testProcessResponseSendsAdminAlertForNonOkResponse()
    {
        $telegram = new FakeFootballDataOrgAdminTelegram();
        $fetcher = new FootballDataOrg(new RequestStack(), 'https://api.football-data.org/v4', 'test-token', $telegram);

        try {
            $fetcher->processResponse(new Response(429), 'teams');
            $this->fail('Expected Football-Data response processing to throw.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Football-Data request failed (HTTP 429 Too Many Requests).', $e->getMessage());
        }

        $this->assertCount(1, $telegram->adminMessages);
        $this->assertStringContainsString('External API failure: football-data.org', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Football-Data request failed (HTTP 429 Too Many Requests).', $telegram->adminMessages[0]);
    }

    public function testConnectionFailureSendsAdminAlertAndThrows()
    {
        $telegram = new FakeFootballDataOrgAdminTelegram();
        $fetcher = new FootballDataOrg(new RequestStack(), 'https://api.football-data.org/v4', 'test-token', $telegram);
        $property = new \ReflectionProperty(FootballDataOrg::class, 'httpClient');
        $property->setAccessible(true);
        $property->setValue($fetcher, new FailingFootballDataOrgHttpClient());

        try {
            $fetcher->processResponse($fetcher->getResponse('https://api.football-data.org/v4/competitions'));
            $this->fail('Expected Football-Data connection failure to throw.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Football-Data request failed (HTTP 500 Request Failed). Reason: Connection failed', $e->getMessage());
        }

        $this->assertCount(1, $telegram->adminMessages);
        $this->assertStringContainsString('External API failure: football-data.org', $telegram->adminMessages[0]);
        $this->assertStringContainsString('Reason: Connection failed', $telegram->adminMessages[0]);
        $this->assertStringContainsString('URL: https://api.football-data.org/v4/competitions', $telegram->adminMessages[0]);
    }
}

class FakeFootballDataOrgAdminTelegram extends Telegram
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

class FailingFootballDataOrgHttpClient
{
    public function get($url, array $options)
    {
        throw new ConnectException('Connection failed', new GuzzleRequest('GET', $url));
    }
}
