<?php

namespace Tests\Functional;

require_once __DIR__.'/FunctionalTestCase.php';

if (!defined('WEB_DIRECTORY')) {
    define('WEB_DIRECTORY', __DIR__.'/../../web');
}

use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Entity\PredictionChampion;
use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Entity\Tournament;

class AdminPredictionFlowTest extends FunctionalTestCase
{
    public function testAdminCanJoinTournamentAndSubmitPredictions()
    {
        $admin = $this->createUser('admin_flow', 'testpass', true, array('ROLE_ADMIN'));
        list($tournament, $homeTeam, $awayTeam, $match) = $this->createTournamentWithMatch();

        $this->login('admin_flow@example.com', 'testpass');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $crawler = $this->client->request('GET', '/tournaments');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->submit($crawler->selectButton('JOIN')->form());
        $this->assertTrue($this->client->getResponse()->isRedirect('/tournaments'));
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('LEAVE', $crawler->text());

        $crawler = $this->client->request('GET', '/matches');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertStringContainsString('Betting probabilities: home win 45%, draw 30%, away win 25%.', $crawler->text());
        $this->assertStringContainsString('Correct outcome points: home 3, draw 4, away 5.', $crawler->text());
        $this->assertStringContainsString('Exact score points: home 6, draw 7, away 8.', $crawler->text());

        $form = $crawler->filter('button.match-btn')->form(array(
            'prediction[homeGoals]' => 2,
            'prediction[awayGoals]' => 1,
        ));
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $prediction = $this->em->getRepository(Prediction::class)->getOneByUserAndMatch($admin, $match);
        $this->assertSame(2, $prediction->getHomeGoals());
        $this->assertSame(1, $prediction->getAwayGoals());

        $crawler = $this->client->request('GET', '/champion');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $form = $crawler->selectButton('BET')->form(array(
            'champion_select[team][id]' => $awayTeam->getId(),
        ));
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect('/champion'));
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $championPrediction = $this->em->getRepository(PredictionChampion::class)->findOneBy(array(
            'userId' => $admin,
            'tournamentId' => $tournament,
        ));
        $this->assertNotNull($championPrediction);
        $this->assertSame($awayTeam->getId(), $championPrediction->getTeamId()->getId());
    }

    private function createTournamentWithMatch()
    {
        $tournament = new Tournament();
        $tournament->setName('Admin Flow Cup');
        $tournament->setStartDate(new \DateTime('+1 day'));
        $tournament->setEndDate(new \DateTime('+30 days'));

        $homeTeam = new Team();
        $homeTeam->setName('Admin Flow Home');
        $homeTeam->addTournament($tournament);

        $awayTeam = new Team();
        $awayTeam->setName('Admin Flow Away');
        $awayTeam->addTournament($tournament);

        $match = new MatchEntity();
        $match->setTournamentId($tournament);
        $match->setHomeTeamId($homeTeam);
        $match->setAwayTeamId($awayTeam);
        $match->setDatetime(new \DateTime('+2 days'));
        $match->setBaseOutcomePoints(2);
        $match->setBaseExactPoints(5);
        $match->setHomeWinProbabilityPercent(45);
        $match->setDrawProbabilityPercent(30);
        $match->setAwayWinProbabilityPercent(25);

        $this->em->persist($tournament);
        $this->em->persist($homeTeam);
        $this->em->persist($awayTeam);
        $this->em->persist($match);
        $this->em->flush();

        return array($tournament, $homeTeam, $awayTeam, $match);
    }
}
