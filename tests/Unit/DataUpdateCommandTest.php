<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Command\DataUpdateCommand;
use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Entity\Score;
use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Entity\Tournament;
use Devlabs\SportifyBundle\Entity\User;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

class DataUpdateCommandTest extends TestCase
{
    public function testSendsFixtureUpdateNotificationsThroughConfiguredServices()
    {
        $container = new Container();
        $slack = new FakeDataUpdateSlack();
        $telegram = new FakeDataUpdateTelegram();

        $this->configureContainer($container, $slack, $telegram);

        $tester = new CommandTester(new DataUpdateCommand($container));
        $tester->execute(array(
            'type' => 'matches-fixtures',
            'days' => 3,
        ));

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Match fixtures added for next 3 days. 2 fixture(s) added.', $slack->text);
        $this->assertStringContainsString('Home Nation vs Away Nation: home 45%, draw 30%, away 25%', $slack->text);
        $this->assertCount(1, $telegram->messages);
        $this->assertStringContainsString($slack->text, $telegram->messages[0]);
        $this->assertSame(array(321), $telegram->pinnedMessageIds);
    }

    public function testCanDisableTelegramMessagePinning()
    {
        $container = new Container();
        $slack = new FakeDataUpdateSlack();
        $telegram = new FakeDataUpdateTelegram();

        $this->configureContainer($container, $slack, $telegram);
        $container->setParameter('telegram.pin_messages', false);

        $tester = new CommandTester(new DataUpdateCommand($container));
        $tester->execute(array(
            'type' => 'matches-fixtures',
            'days' => 3,
        ));

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertCount(1, $telegram->messages);
        $this->assertSame(array(), $telegram->pinnedMessageIds);
    }

    public function testResultUpdateNotificationIncludesScoringBreakdown()
    {
        $container = new Container();
        $slack = new FakeDataUpdateSlack();
        $telegram = new FakeDataUpdateTelegram();
        $fixture = $this->createScoredFixture();

        $container->set('app.data_updates.manager', new FakeResultsDataUpdatesManager());
        $container->set('app.score_updater', new FakeDataUpdateScoreUpdater($fixture['tournament'], $fixture['match']));
        $container->set('doctrine.orm.entity_manager', new FakeDataUpdateEntityManager($fixture['match'], $fixture['predictions'], $fixture['score']));
        $container->set('app.slack', $slack);
        $container->set('app.telegram', $telegram);

        $tester = new CommandTester(new DataUpdateCommand($container));
        $tester->execute(array(
            'type' => 'matches-results',
            'days' => 1,
        ));

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Result Cup', $telegram->messages[0]);
        $this->assertStringContainsString('Home FC 2-1 Away FC', $telegram->messages[0]);
        $this->assertStringContainsString('Probabilities: home 10%, draw 25%, away 65%', $telegram->messages[0]);
        $this->assertStringContainsString('alice predicted 2-1 (home win): exact score, base 5 + probability bonus 4 = 9', $telegram->messages[0]);
        $this->assertStringContainsString('bob predicted 1-0 (home win): correct outcome, base 2 + probability bonus 4 = 6', $telegram->messages[0]);
        $this->assertStringContainsString('charlie predicted 0-1 (away win): wrong outcome, 0 points', $telegram->messages[0]);
        $this->assertStringContainsString('Standings changes:', $telegram->messages[0]);
        $this->assertStringContainsString('alice: Position: 1 (previous: 2), Points: 16 (gained: 9)', $telegram->messages[0]);
    }

    private function createScoredFixture()
    {
        $tournament = new Tournament();
        $tournament->setId(7);
        $tournament->setName('Result Cup');

        $homeTeam = new Team();
        $homeTeam->setName('Home FC');

        $awayTeam = new Team();
        $awayTeam->setName('Away FC');

        $match = new MatchEntity();
        $match->setId(11);
        $match->setTournamentId($tournament);
        $match->setHomeTeamId($homeTeam);
        $match->setAwayTeamId($awayTeam);
        $match->setHomeGoals(2);
        $match->setAwayGoals(1);
        $match->setHomeWinProbabilityPercent(10);
        $match->setDrawProbabilityPercent(25);
        $match->setAwayWinProbabilityPercent(65);

        $user = new User();
        $user->setId(3);
        $user->setUsername('alice');
        $outcomeUser = new User();
        $outcomeUser->setId(4);
        $outcomeUser->setUsername('bob');
        $wrongUser = new User();
        $wrongUser->setId(5);
        $wrongUser->setUsername('charlie');

        $prediction = new Prediction();
        $prediction->setUserId($user);
        $prediction->setMatchId($match);
        $prediction->setHomeGoals(2);
        $prediction->setAwayGoals(1);
        $prediction->setScoringResult(Prediction::SCORING_RESULT_EXACT);
        $prediction->setBasePoints(5);
        $prediction->setProbabilityBonus(4);
        $prediction->setTotalPoints(9);
        $prediction->setPoints(9);

        $outcomePrediction = new Prediction();
        $outcomePrediction->setUserId($outcomeUser);
        $outcomePrediction->setMatchId($match);
        $outcomePrediction->setHomeGoals(1);
        $outcomePrediction->setAwayGoals(0);
        $outcomePrediction->setScoringResult(Prediction::SCORING_RESULT_OUTCOME);
        $outcomePrediction->setBasePoints(2);
        $outcomePrediction->setProbabilityBonus(4);
        $outcomePrediction->setTotalPoints(6);
        $outcomePrediction->setPoints(6);

        $wrongPrediction = new Prediction();
        $wrongPrediction->setUserId($wrongUser);
        $wrongPrediction->setMatchId($match);
        $wrongPrediction->setHomeGoals(0);
        $wrongPrediction->setAwayGoals(1);
        $wrongPrediction->setScoringResult(Prediction::SCORING_RESULT_WRONG);
        $wrongPrediction->setBasePoints(0);
        $wrongPrediction->setProbabilityBonus(0);
        $wrongPrediction->setTotalPoints(0);
        $wrongPrediction->setPoints(0);

        $score = new Score();
        $score->setUserId($user);
        $score->setTournamentId($tournament);
        $score->setPosOld(2);
        $score->setPosNew(1);
        $score->setPointsOld(7);
        $score->setPoints(16);

        return array(
            'tournament' => $tournament,
            'match' => $match,
            'predictions' => array($prediction, $outcomePrediction, $wrongPrediction),
            'score' => $score,
        );
    }

    private function configureContainer(Container $container, FakeDataUpdateSlack $slack, FakeDataUpdateTelegram $telegram)
    {
        $container->set('app.data_updates.manager', new FakeDataUpdatesManager());
        $container->set('app.slack', $slack);
        $container->set('app.telegram', $telegram);
    }
}

class FakeDataUpdatesManager
{
    public function updateFixtures($dateFrom, $dateTo)
    {
        return array(
            'total_added' => 2,
            'total_updated' => 0,
            'added_fixtures' => array(
                array(
                    'home_team' => 'Home Nation',
                    'away_team' => 'Away Nation',
                    'home_win_probability_percent' => 45,
                    'draw_probability_percent' => 30,
                    'away_win_probability_percent' => 25,
                    'source' => 'the_odds_api:soccer_test:event-1:pinnacle:h2h',
                ),
            ),
        );
    }
}

class FakeResultsDataUpdatesManager
{
    public function updateFixtures($dateFrom, $dateTo)
    {
        return array(
            'total_added' => 0,
            'total_updated' => 1,
        );
    }
}

class FakeDataUpdateScoreUpdater
{
    private $tournament;
    private $match;

    public function __construct(Tournament $tournament, MatchEntity $match)
    {
        $this->tournament = $tournament;
        $this->match = $match;
    }

    public function updateAll()
    {
        return array($this->tournament);
    }

    public function getLastScoredMatchIds()
    {
        return array($this->match->getId());
    }
}

class FakeDataUpdateEntityManager
{
    private $match;
    private $predictions;
    private $score;

    public function __construct(MatchEntity $match, array $predictions, Score $score)
    {
        $this->match = $match;
        $this->predictions = $predictions;
        $this->score = $score;
    }

    public function getRepository($class)
    {
        if ($class === MatchEntity::class) {
            return new FakeDataUpdateMatchRepository($this->match);
        }
        if ($class === Prediction::class) {
            return new FakeDataUpdatePredictionRepository($this->predictions);
        }

        return new FakeDataUpdateScoreRepository($this->score);
    }
}

class FakeDataUpdateMatchRepository
{
    private $match;

    public function __construct(MatchEntity $match)
    {
        $this->match = $match;
    }

    public function findBy(array $criteria)
    {
        return in_array($this->match->getId(), $criteria['id']) ? array($this->match) : array();
    }
}

class FakeDataUpdatePredictionRepository
{
    private $predictions;

    public function __construct(array $predictions)
    {
        $this->predictions = $predictions;
    }

    public function getByMatches(array $matches)
    {
        return $matches ? $this->predictions : array();
    }
}

class FakeDataUpdateScoreRepository
{
    private $score;

    public function __construct(Score $score)
    {
        $this->score = $score;
    }

    public function getAllHashed()
    {
        return array(
            $this->score->getTournamentId()->getId() => array($this->score),
        );
    }
}

class FakeDataUpdateSlack
{
    public $text;

    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    public function post()
    {
    }
}

class FakeDataUpdateTelegram
{
    public $messages = array();
    public $pinnedMessageIds = array();

    public function sendMessage($text)
    {
        $this->messages[] = $text;

        return new Response(200, array(), json_encode(array(
            'result' => array(
                'message_id' => 321,
            ),
        )));
    }

    public function pinMessage($messageId)
    {
        $this->pinnedMessageIds[] = $messageId;

        return new Response(200);
    }
}
