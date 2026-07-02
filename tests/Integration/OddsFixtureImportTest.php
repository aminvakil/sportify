<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

use Devlabs\SportifyBundle\Entity\ApiMapping;
use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Services\DataUpdates\Importer;

class OddsFixtureImportTest extends DatabaseTestCase
{
    public function testAddsUpcomingFixtureWithOddsSnapshotAndCurrentBaseScoring()
    {
        self::$kernel->getContainer()->get('app.scoring_defaults')->updateDefaults(3, 7);

        $tournament = $this->createTournament('Odds Cup');
        $homeTeam = $this->createTeam('Home Nation', $tournament);
        $awayTeam = $this->createTeam('Away Nation', $tournament);
        $this->createApiMapping($homeTeam, 'Team', 'football_data_org', 10);
        $this->createApiMapping($awayTeam, 'Team', 'football_data_org', 20);

        $oddsProvider = new FakeFixtureOddsProvider(array(
            500 => array(
                'home_win_probability_percent' => 45,
                'draw_probability_percent' => 30,
                'away_win_probability_percent' => 25,
                'source' => 'the_odds_api:soccer_test:event-1:pinnacle:h2h',
            ),
        ));
        $importer = $this->createImporter($oddsProvider);

        $status = $importer->importFixtures(array($this->scheduledFixture(500)), $tournament, 'football_data_org');

        $this->assertSame(1, $status['fixtures_added']);
        $this->assertSame(array(array(
            'home_team' => 'Home Nation',
            'away_team' => 'Away Nation',
            'home_win_probability_percent' => 45,
            'draw_probability_percent' => 30,
            'away_win_probability_percent' => 25,
            'source' => 'the_odds_api:soccer_test:event-1:pinnacle:h2h',
        )), $status['added_fixtures']);

        $matchMapping = $this->em->getRepository(ApiMapping::class)
            ->getByEntityTypeAndApiObjectId('Match', 'football_data_org', 500);
        $match = $this->em->getRepository(MatchEntity::class)->find($matchMapping->getEntityId());

        $this->assertSame(3, $match->getBaseOutcomePoints());
        $this->assertSame(7, $match->getBaseExactPoints());
        $this->assertSame(45, $match->getHomeWinProbabilityPercent());
        $this->assertSame(30, $match->getDrawProbabilityPercent());
        $this->assertSame(25, $match->getAwayWinProbabilityPercent());
        $this->assertSame('the_odds_api:soccer_test:event-1:pinnacle:h2h', $match->getProbabilitySource());
    }

    public function testAddsWorldCup2026KnockoutFixtureWithFootballDataStageScoring()
    {
        $tournament = $this->createTournament('FIFA World Cup');
        $homeTeam = $this->createTeam('Home Nation', $tournament);
        $awayTeam = $this->createTeam('Away Nation', $tournament);
        $this->createApiMapping($homeTeam, 'Team', 'football_data_org', 10);
        $this->createApiMapping($awayTeam, 'Team', 'football_data_org', 20);

        $importer = $this->createImporter(new FakeFixtureOddsProvider(array(
            504 => array(
                'home_win_probability_percent' => 45,
                'draw_probability_percent' => 30,
                'away_win_probability_percent' => 25,
                'source' => 'the_odds_api:soccer_test:event-1:pinnacle:h2h',
            ),
        )));

        $status = $importer->importFixtures(array($this->scheduledFixture(504, '2026-06-28 12:00:00', 'LAST_16')), $tournament, 'football_data_org');

        $this->assertSame(1, $status['fixtures_added']);

        $matchMapping = $this->em->getRepository(ApiMapping::class)
            ->getByEntityTypeAndApiObjectId('Match', 'football_data_org', 504);
        $match = $this->em->getRepository(MatchEntity::class)->find($matchMapping->getEntityId());

        $this->assertSame(4, $match->getBaseOutcomePoints());
        $this->assertSame(8, $match->getBaseExactPoints());
    }

    public function testSkipsUpcomingFixtureWhenOddsSnapshotIsUnavailable()
    {
        $tournament = $this->createTournament('Skipped Odds Cup');
        $homeTeam = $this->createTeam('Home Nation', $tournament);
        $awayTeam = $this->createTeam('Away Nation', $tournament);
        $this->createApiMapping($homeTeam, 'Team', 'football_data_org', 10);
        $this->createApiMapping($awayTeam, 'Team', 'football_data_org', 20);

        $oddsProvider = new FakeFixtureOddsProvider(array());
        $importer = $this->createImporter($oddsProvider);

        $status = $importer->importFixtures(array($this->scheduledFixture(501)), $tournament, 'football_data_org');

        $this->assertSame(1, $oddsProvider->flushCount);
        $this->assertSame(0, $status['fixtures_added']);
        $this->assertSame(array(), $status['added_fixtures']);
        $this->assertNull($this->em->getRepository(ApiMapping::class)
            ->getByEntityTypeAndApiObjectId('Match', 'football_data_org', 501));
    }

    public function testExistingWorldCup2026KnockoutFixturesUseFootballDataStageScoringOnReimport()
    {
        $tournament = $this->createTournament('FIFA World Cup');
        $homeTeam = $this->createTeam('Home Nation', $tournament);
        $awayTeam = $this->createTeam('Away Nation', $tournament);
        $last16Match = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('2026-06-28 12:00:00'));
        $last32Match = $this->createMatch($tournament, $awayTeam, $homeTeam, new \DateTime('2026-07-04 20:30:00'));
        $this->createApiMapping($last16Match, 'Match', 'football_data_org', 502);
        $this->createApiMapping($last32Match, 'Match', 'football_data_org', 503);

        $importer = $this->createImporter(new FakeFixtureOddsProvider(array()));

        $status = $importer->importFixtures(array(
            $this->scheduledFixture(502, '2026-06-28 12:00:00', 'LAST_16'),
            $this->scheduledFixture(503, '2026-07-04 20:30:00', 'LAST_32'),
        ), $tournament, 'football_data_org');

        $this->assertSame(0, $status['fixtures_added']);
        $this->assertSame(2, $status['fixtures_updated']);
        $this->assertSame(4, $last16Match->getBaseOutcomePoints());
        $this->assertSame(8, $last16Match->getBaseExactPoints());
        $this->assertSame(3, $last32Match->getBaseOutcomePoints());
        $this->assertSame(6, $last32Match->getBaseExactPoints());
    }

    private function createImporter($oddsProvider)
    {
        return new Importer(
            self::$kernel->getContainer(),
            $this->em,
            self::$kernel->getContainer()->get('app.scoring_defaults'),
            $oddsProvider
        );
    }

    private function scheduledFixture($matchId, $datetime = '2030-06-01 12:00:00', $stage = 'GROUP_STAGE')
    {
        return array(
            'match_id' => $matchId,
            'tournament_id' => 99,
            'stage' => $stage,
            'home_team_id' => 10,
            'away_team_id' => 20,
            'match_local_time' => $datetime,
            'status' => 'SCHEDULED',
            'home_team_goals' => null,
            'away_team_goals' => null,
        );
    }
}

class FakeFixtureOddsProvider
{
    public $flushCount = 0;
    private $snapshots;

    public function __construct(array $snapshots)
    {
        $this->snapshots = $snapshots;
    }

    public function findProbabilitiesForFixture(array $fixtureData, $tournament, $homeTeam, $awayTeam)
    {
        if (!isset($this->snapshots[$fixtureData['match_id']])) {
            return null;
        }

        return $this->snapshots[$fixtureData['match_id']];
    }

    public function flushOddsUnavailableNotifications()
    {
        $this->flushCount++;
    }
}
