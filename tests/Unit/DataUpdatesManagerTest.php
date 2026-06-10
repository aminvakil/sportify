<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\MatchRepository;
use Devlabs\SportifyBundle\Services\DataUpdates\Manager;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class DataUpdatesManagerTest extends TestCase
{
    public function testUpdateResultsSkipsApiWhenLocalDatabaseHasNoCandidates()
    {
        $matchRepository = $this->createMock(MatchRepository::class);
        $matchRepository->expects($this->once())
            ->method('hasResultUpdateCandidates')
            ->with(
                $this->callback(function ($date) {
                    return $date instanceof \DateTime && $date->format('Y-m-d H:i:s') === '2026-06-01 00:00:00';
                }),
                $this->callback(function ($date) {
                    return $date instanceof \DateTime && $date->format('Y-m-d H:i:s') === '2026-06-10 23:59:59';
                }),
                $this->isInstanceOf(\DateTime::class)
            )
            ->willReturn(false);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(MatchEntity::class)
            ->willReturn($matchRepository);

        $fetcher = new FakeDataUpdatesManagerFetcher();
        $manager = new Manager(
            new Container(),
            $entityManager,
            'football-data',
            $fetcher,
            new FakeDataUpdatesManagerParser(),
            new FakeDataUpdatesManagerImporter()
        );

        $status = $manager->updateResults('2026-06-01', '2026-06-10');

        $this->assertSame(array(
            'total_fetched' => 0,
            'total_added' => 0,
            'total_updated' => 0,
            'added_fixtures' => array(),
        ), $status);
        $this->assertFalse($fetcher->fixturesFetched);
    }
}

class FakeDataUpdatesManagerFetcher
{
    public $fixturesFetched = false;

    public function fetchFixturesByTournamentAndTimeRange($apiTournamentId, $dateFrom, $dateTo)
    {
        $this->fixturesFetched = true;

        return array();
    }
}

class FakeDataUpdatesManagerParser
{
}

class FakeDataUpdatesManagerImporter
{
}
