<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

use Devlabs\SportifyBundle\Entity\ScoringSettings;

class ScoringDefaultsTest extends DatabaseTestCase
{
    public function testDefaultsCanBeUpdatedAndAppliedToNewMatches()
    {
        $defaults = self::$kernel->getContainer()->get('app.scoring_defaults');

        $this->assertSame(2, $defaults->getOutcomePoints());
        $this->assertSame(5, $defaults->getExactPoints());

        $defaults->updateDefaults(5, 12);
        $this->em->clear();

        $settings = $this->em->getRepository(ScoringSettings::class)->find(1);
        $this->assertSame(5, $settings->getOutcomePoints());
        $this->assertSame(12, $settings->getExactPoints());

        $tournament = $this->createTournament('Scoring Defaults Cup');
        $homeTeam = $this->createTeam('Default Home', $tournament);
        $awayTeam = $this->createTeam('Default Away', $tournament);
        $match = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('+1 day'));
        $defaults->applyToMatch($match);

        $this->assertSame(5, $match->getBaseOutcomePoints());
        $this->assertSame(12, $match->getBaseExactPoints());
    }
}
