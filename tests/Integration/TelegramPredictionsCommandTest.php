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
    public function testSendsPredictionsForMultipleRecentlyStartedMatches()
    {
        $tournament = $this->createTournament('Telegram Cup');
        $homeTeam = $this->createTeam('Home FC', $tournament);
        $awayTeam = $this->createTeam('Away FC', $tournament);
        $secondHomeTeam = $this->createTeam('East FC', $tournament);
        $secondAwayTeam = $this->createTeam('West FC', $tournament);
        $recentMatch = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('-1 minute'));
        $recentMatch->setHomeWinProbabilityPercent(45);
        $recentMatch->setDrawProbabilityPercent(30);
        $recentMatch->setAwayWinProbabilityPercent(25);
        $secondRecentMatch = $this->createMatch($tournament, $secondHomeTeam, $secondAwayTeam, new \DateTime('-2 minutes'));
        $secondRecentMatch->setHomeWinProbabilityPercent(60);
        $secondRecentMatch->setDrawProbabilityPercent(20);
        $secondRecentMatch->setAwayWinProbabilityPercent(20);
        $this->em->flush();
        $oldMatch = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('-6 minutes'));

        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $charlie = $this->createUser('charlie');
        $this->createPrediction($bob, $recentMatch, 1, 1);
        $this->createPrediction($alice, $recentMatch, 2, 1);
        $this->createPrediction($charlie, $secondRecentMatch, 0, 1);
        $this->createPrediction($alice, $oldMatch, 0, 0);

        $telegram = new FakeTelegram();
        $tester = new CommandTester(new TelegramPredictionsCommand($this->em, $telegram));
        $tester->execute(array());

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertCount(1, $telegram->messages);
        $this->assertStringContainsString('Home FC - Away FC', $telegram->messages[0]);
        $this->assertStringContainsString('Probabilities: home 45%, draw 30%, away 25%', $telegram->messages[0]);
        $this->assertStringContainsString('East FC - West FC', $telegram->messages[0]);
        $this->assertStringContainsString('Probabilities: home 60%, draw 20%, away 20%', $telegram->messages[0]);
        $this->assertStringContainsString('user                 pred   bonus pts', $telegram->messages[0]);
        $this->assertMatchesRegularExpression('/alice\s+2-1\s+-/', $telegram->messages[0]);
        $this->assertMatchesRegularExpression('/bob\s+1-1\s+\+2/', $telegram->messages[0]);
        $this->assertMatchesRegularExpression('/charlie\s+0-1\s+\+3/', $telegram->messages[0]);
        $this->assertStringNotContainsString('home win', $telegram->messages[0]);
        $this->assertStringNotContainsString('0-0', $telegram->messages[0]);

        $this->em->clear();
        $recentMatch = $this->em->getRepository(MatchEntity::class)->find($recentMatch->getId());
        $secondRecentMatch = $this->em->getRepository(MatchEntity::class)->find($secondRecentMatch->getId());
        $oldMatch = $this->em->getRepository(MatchEntity::class)->find($oldMatch->getId());
        $this->assertTrue($recentMatch->getPredictionsNotificationSent());
        $this->assertTrue($secondRecentMatch->getPredictionsNotificationSent());
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
