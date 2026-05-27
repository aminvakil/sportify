<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Entity\Tournament;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

if (!defined('WEB_DIRECTORY')) {
    define('WEB_DIRECTORY', __DIR__.'/../../web');
}

class MatchHistoryTemplateTest extends TestCase
{
    public function testUsesScoringResultForPredictionClass()
    {
        $twig = new Environment(new FilesystemLoader(__DIR__.'/../../app/Resources/views'));
        $twig->addFunction(new \Twig\TwigFunction('asset', function ($path) { return $path; }));

        $match = $this->createMatch();
        $prediction = new Prediction();
        $prediction->setHomeGoals(2);
        $prediction->setAwayGoals(1);
        $prediction->setPoints(18);
        $prediction->setScoringResult(Prediction::SCORING_RESULT_EXACT);

        $html = $twig->render('templates/match_history.html.twig', array(
            'match' => $match,
            'predictions' => array($match->getId() => $prediction),
        ));

        $this->assertStringContainsString('points-gained text-green">18 pt', $html);
    }

    private function createMatch()
    {
        $tournament = new Tournament();
        $tournament->setName('History Cup');

        $homeTeam = new Team();
        $homeTeam->setName('History Home');

        $awayTeam = new Team();
        $awayTeam->setName('History Away');

        $match = new MatchEntity();
        $match->setId(123);
        $match->setTournamentId($tournament);
        $match->setHomeTeamId($homeTeam);
        $match->setAwayTeamId($awayTeam);
        $match->setDatetime(new \DateTime('2026-01-01 12:00:00'));
        $match->setHomeGoals(2);
        $match->setAwayGoals(1);

        return $match;
    }
}
