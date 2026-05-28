<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

use Devlabs\SportifyBundle\Command\TelegramPredictionsCommand;
use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Services\Telegram;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TelegramPredictionsCommandTest extends DatabaseTestCase
{
    public function testSendsPredictionsForRecentlyStartedMatches()
    {
        $tournament = $this->createTournament('Telegram Cup');
        $homeTeam = $this->createTeam('Home FC', $tournament);
        $awayTeam = $this->createTeam('Away FC', $tournament);
        $recentMatch = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('-1 minute'));
        $recentMatch->setHomeWinProbabilityPercent(45);
        $recentMatch->setDrawProbabilityPercent(30);
        $recentMatch->setAwayWinProbabilityPercent(25);
        $this->em->flush();
        $oldMatch = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('-6 minutes'));

        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $this->createPrediction($bob, $recentMatch, 1, 1);
        $this->createPrediction($alice, $recentMatch, 2, 1);
        $this->createPrediction($alice, $oldMatch, 0, 0);

        $telegram = new FakeTelegram();
        $tester = new CommandTester(new TelegramPredictionsCommand($this->em, $telegram));
        $tester->execute(array());

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertCount(1, $telegram->messages);
        $this->assertStringContainsString('alice', $telegram->messages[0]);
        $this->assertStringContainsString('2-1', $telegram->messages[0]);
        $this->assertStringContainsString('home win', $telegram->messages[0]);
        $this->assertStringContainsString('Probabilities: home 45%, draw 30%, away 25%', $telegram->messages[0]);
        $this->assertStringContainsString('bob', $telegram->messages[0]);
        $this->assertStringContainsString('1-1', $telegram->messages[0]);
        $this->assertStringContainsString('draw', $telegram->messages[0]);
        $this->assertStringNotContainsString('0-0', $telegram->messages[0]);

        $this->em->clear();
        $recentMatch = $this->em->getRepository(MatchEntity::class)->find($recentMatch->getId());
        $oldMatch = $this->em->getRepository(MatchEntity::class)->find($oldMatch->getId());
        $this->assertTrue($recentMatch->getPredictionsNotificationSent());
        $this->assertFalse($oldMatch->getPredictionsNotificationSent());
    }

    public function testSkipsAlreadySentMatches()
    {
        $tournament = $this->createTournament('Telegram Cup');
        $homeTeam = $this->createTeam('Home FC', $tournament);
        $awayTeam = $this->createTeam('Away FC', $tournament);
        $match = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('-1 minute'));
        $match->setPredictionsNotificationSent('1');
        $this->em->persist($match);
        $this->em->flush();

        $telegram = new FakeTelegram();
        $tester = new CommandTester(new TelegramPredictionsCommand($this->em, $telegram));
        $tester->execute(array());

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertCount(0, $telegram->messages);
        $this->assertStringContainsString('No recently started matches found.', $tester->getDisplay());
    }
}

class FakeTelegram extends Telegram
{
    public $messages = array();

    public function __construct()
    {
    }

    public function sendMessage($text)
    {
        $this->messages[] = $text;

        return new Response(200);
    }
}
