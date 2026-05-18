<?php

namespace Tests\Integration;

use Devlabs\SportifyBundle\Entity\Match;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Entity\Tournament;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PredictionWorkflowTest extends KernelTestCase
{
    /** @var EntityManager */
    private $em;

    protected function setUp()
    {
        self::bootKernel();

        $this->em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $this->resetDatabase();
    }

    protected function tearDown()
    {
        if ($this->em) {
            $this->em->close();
        }

        $this->em = null;

        parent::tearDown();
    }

    public function testTournamentUsersPredictionsAndResultsWorkflow()
    {
        $tournament = $this->createTournament('Integration Cup');
        $homeTeam = $this->createTeam('Home FC', $tournament);
        $awayTeam = $this->createTeam('Away FC', $tournament);
        $finishedMatch = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('-1 day'), 2, 1);
        $upcomingMatch = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('+1 day'));

        $exactUser = $this->createUser('exact_user');
        $outcomeUser = $this->createUser('outcome_user');

        $tournamentsHelper = self::$kernel->getContainer()->get('app.tournaments.helper');
        $tournamentsHelper->joinTournament($exactUser, $tournament);
        $tournamentsHelper->joinTournament($outcomeUser, $tournament);
        self::$kernel->getContainer()->get('app.score_updater')->updateUserPositionsForTournament($tournament->getId());

        $this->createPrediction($exactUser, $finishedMatch, 2, 1);
        $this->createPrediction($outcomeUser, $finishedMatch, 1, 0);

        $predictionRepository = $this->em->getRepository('DevlabsSportifyBundle:Prediction');
        $notScored = $predictionRepository->getFinishedNotScored($exactUser);
        $this->assertArrayHasKey($finishedMatch->getId(), $notScored);

        $modifiedTournaments = self::$kernel->getContainer()->get('app.score_updater')->updateAll();
        $this->assertCount(1, $modifiedTournaments);

        $this->em->clear();

        $finishedMatch = $this->em->getRepository('DevlabsSportifyBundle:Match')->find($finishedMatch->getId());
        $upcomingMatch = $this->em->getRepository('DevlabsSportifyBundle:Match')->find($upcomingMatch->getId());
        $exactUser = $this->em->getRepository('DevlabsSportifyBundle:User')->find($exactUser->getId());
        $outcomeUser = $this->em->getRepository('DevlabsSportifyBundle:User')->find($outcomeUser->getId());
        $tournament = $this->em->getRepository('DevlabsSportifyBundle:Tournament')->find($tournament->getId());

        $exactPrediction = $predictionRepository->getOneByUserAndMatch($exactUser, $finishedMatch);
        $outcomePrediction = $predictionRepository->getOneByUserAndMatch($outcomeUser, $finishedMatch);

        $this->assertSame(Prediction::POINTS_EXACT, $exactPrediction->getPoints());
        $this->assertSame(Prediction::POINTS_OUTCOME, $outcomePrediction->getPoints());
        $this->assertSame('Home FC', $exactPrediction->getHomeTeamName());
        $this->assertSame('Away FC', $exactPrediction->getAwayTeamName());
        $this->assertSame(2, $exactPrediction->getResultHomeGoals());
        $this->assertSame(1, $exactPrediction->getResultAwayGoals());

        $scoreRepository = $this->em->getRepository('DevlabsSportifyBundle:Score');
        $exactScore = $scoreRepository->getByUserAndTournament($exactUser, $tournament);
        $outcomeScore = $scoreRepository->getByUserAndTournament($outcomeUser, $tournament);

        $this->assertSame(3, $exactScore->getPoints());
        $this->assertSame(1, $outcomeScore->getPoints());
        $this->assertSame(100, $exactScore->getExactPredictionPercentage());
        $this->assertSame(0, $outcomeScore->getExactPredictionPercentage());
        $this->assertSame(1, $exactScore->getPosNew());
        $this->assertSame(2, $outcomeScore->getPosNew());

        $alreadyScored = $predictionRepository->getAlreadyScored($exactUser, 'all', '2000-01-01', '2100-01-01');
        $this->assertArrayHasKey($finishedMatch->getId(), $alreadyScored);

        $matchesHelper = self::$kernel->getContainer()->get('app.matches.helper');
        $newPrediction = $matchesHelper->getPrediction($exactUser, $upcomingMatch, array());
        $this->assertSame('BET', $matchesHelper->getPredictionButton($newPrediction));
        $this->assertSame($upcomingMatch->getId(), $newPrediction->getMatchId()->getId());
        $this->assertSame($exactUser->getId(), $newPrediction->getUserId()->getId());

        $historyParams = self::$kernel->getContainer()->get('app.history.helper')
            ->setCurrentUser($exactUser)
            ->initUrlParams('empty', 'empty', 'empty', 'empty');
        $this->assertSame($exactUser->getId(), $historyParams['user_id']);
        $this->assertSame('all', $historyParams['tournament_id']);
    }

    private function resetDatabase()
    {
        $connection = $this->em->getConnection();
        $schemaManager = $connection->getSchemaManager();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        foreach ($schemaManager->listTableNames() as $tableName) {
            $connection->executeQuery('DROP TABLE IF EXISTS '.$connection->quoteIdentifier($tableName));
        }
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($metadata);
    }

    private function createUser($username)
    {
        $userManager = self::$kernel->getContainer()->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $user->setUsername($username);
        $user->setEmail($username.'@example.com');
        $user->setPlainPassword('test-password');
        $user->setEnabled(true);

        $userManager->updateUser($user);

        return $user;
    }

    private function createTournament($name)
    {
        $tournament = new Tournament();
        $tournament->setName($name);
        $tournament->setStartDate(new \DateTime('-1 week'));
        $tournament->setEndDate(new \DateTime('+1 week'));

        $this->em->persist($tournament);
        $this->em->flush();

        return $tournament;
    }

    private function createTeam($name, Tournament $tournament)
    {
        $team = new Team();
        $team->setName($name);
        $team->addTournament($tournament);

        $this->em->persist($team);
        $this->em->flush();

        return $team;
    }

    private function createMatch(Tournament $tournament, Team $homeTeam, Team $awayTeam, \DateTime $datetime, $homeGoals = null, $awayGoals = null)
    {
        $match = new Match();
        $match->setTournamentId($tournament);
        $match->setHomeTeamId($homeTeam);
        $match->setAwayTeamId($awayTeam);
        $match->setDatetime($datetime);
        $match->setHomeGoals($homeGoals);
        $match->setAwayGoals($awayGoals);

        $this->em->persist($match);
        $this->em->flush();

        return $match;
    }

    private function createPrediction($user, Match $match, $homeGoals, $awayGoals)
    {
        $prediction = new Prediction();
        $prediction->setUserId($user);
        $prediction->setMatchId($match);
        $prediction->setHomeGoals($homeGoals);
        $prediction->setAwayGoals($awayGoals);

        $this->em->persist($prediction);
        $this->em->flush();

        return $prediction;
    }
}
