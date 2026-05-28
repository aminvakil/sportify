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
                'home_win_probability_bps' => 4500,
                'draw_probability_bps' => 3000,
                'away_win_probability_bps' => 2500,
                'source' => 'the_odds_api:soccer_test:event-1:pinnacle:h2h',
            ),
        ));
        $importer = $this->createImporter($oddsProvider);

        $status = $importer->importFixtures(array($this->scheduledFixture(500)), $tournament, 'football_data_org');

        $this->assertSame(1, $status['fixtures_added']);
        $this->assertSame(array(array(
            'home_team' => 'Home Nation',
            'away_team' => 'Away Nation',
            'home_win_probability_bps' => 4500,
            'draw_probability_bps' => 3000,
            'away_win_probability_bps' => 2500,
            'source' => 'the_odds_api:soccer_test:event-1:pinnacle:h2h',
        )), $status['added_fixtures']);

        $matchMapping = $this->em->getRepository(ApiMapping::class)
            ->getByEntityTypeAndApiObjectId('Match', 'football_data_org', 500);
        $match = $this->em->getRepository(MatchEntity::class)->find($matchMapping->getEntityId());

        $this->assertSame(3, $match->getBaseOutcomePoints());
        $this->assertSame(7, $match->getBaseExactPoints());
        $this->assertSame(4500, $match->getHomeWinProbabilityBps());
        $this->assertSame(3000, $match->getDrawProbabilityBps());
        $this->assertSame(2500, $match->getAwayWinProbabilityBps());
        $this->assertSame('the_odds_api:soccer_test:event-1:pinnacle:h2h', $match->getProbabilitySource());
    }

    public function testSkipsUpcomingFixtureWhenOddsSnapshotIsUnavailable()
    {
        $tournament = $this->createTournament('Skipped Odds Cup');
        $homeTeam = $this->createTeam('Home Nation', $tournament);
        $awayTeam = $this->createTeam('Away Nation', $tournament);
        $this->createApiMapping($homeTeam, 'Team', 'football_data_org', 10);
        $this->createApiMapping($awayTeam, 'Team', 'football_data_org', 20);

        $importer = $this->createImporter(new FakeFixtureOddsProvider(array()));

        $status = $importer->importFixtures(array($this->scheduledFixture(501)), $tournament, 'football_data_org');

        $this->assertSame(0, $status['fixtures_added']);
        $this->assertSame(array(), $status['added_fixtures']);
        $this->assertNull($this->em->getRepository(ApiMapping::class)
            ->getByEntityTypeAndApiObjectId('Match', 'football_data_org', 501));
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

    private function scheduledFixture($matchId)
    {
        return array(
            'match_id' => $matchId,
            'tournament_id' => 99,
            'home_team_id' => 10,
            'away_team_id' => 20,
            'match_local_time' => '2030-06-01 12:00:00',
            'status' => 'SCHEDULED',
            'home_team_goals' => null,
            'away_team_goals' => null,
        );
    }
}

class FakeFixtureOddsProvider
{
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
}
