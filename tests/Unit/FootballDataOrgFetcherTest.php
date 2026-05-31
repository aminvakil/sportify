<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Services\DataUpdates\Fetchers\FootballDataOrg;
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
}
