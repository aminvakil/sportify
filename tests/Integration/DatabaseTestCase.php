<?php

namespace Tests\Integration;

use Devlabs\SportifyBundle\Entity\ApiMapping;
use Devlabs\SportifyBundle\Entity\Match;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Entity\PredictionChampion;
use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Entity\Tournament;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    /** @var EntityManager */
    protected $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::$kernel->getContainer()->get('test.doctrine.orm.entity_manager');
        $this->resetDatabase();
    }

    protected function tearDown(): void
    {
        if ($this->em) {
            $this->em->close();
        }

        $this->em = null;

        parent::tearDown();
    }

    protected function resetDatabase()
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

    protected function createUser($username, $enabled = true)
    {
        $user = new \Devlabs\SportifyBundle\Entity\User();
        $user->setUsername($username);
        $user->setUsernameCanonical($username);
        $user->setEmail($username.'@example.com');
        $user->setEmailCanonical($username.'@example.com');
        $user->setPassword('test-password');
        $user->setEnabled($enabled);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createTournament($name, \DateTime $startDate = null, \DateTime $endDate = null)
    {
        $tournament = new Tournament();
        $tournament->setName($name);
        $tournament->setStartDate($startDate ?: new \DateTime('-1 week'));
        $tournament->setEndDate($endDate ?: new \DateTime('+1 week'));

        $this->em->persist($tournament);
        $this->em->flush();

        return $tournament;
    }

    protected function createTeam($name, Tournament $tournament = null)
    {
        $team = new Team();
        $team->setName($name);

        if ($tournament) {
            $team->addTournament($tournament);
        }

        $this->em->persist($team);
        $this->em->flush();

        return $team;
    }

    protected function createMatch(Tournament $tournament, Team $homeTeam, Team $awayTeam, \DateTime $datetime, $homeGoals = null, $awayGoals = null)
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

    protected function createPrediction($user, Match $match, $homeGoals, $awayGoals, $scoreAdded = null, $points = null)
    {
        $prediction = new Prediction();
        $prediction->setUserId($user);
        $prediction->setMatchId($match);
        $prediction->setHomeGoals($homeGoals);
        $prediction->setAwayGoals($awayGoals);

        if ($scoreAdded !== null) {
            $prediction->setScoreAdded($scoreAdded);
        }

        if ($points !== null) {
            $prediction->setPoints($points);
        }

        $this->em->persist($prediction);
        $this->em->flush();

        return $prediction;
    }

    protected function createChampionPrediction($user, Tournament $tournament, Team $team, $scoreAdded = null, $points = null)
    {
        $predictionChampion = new PredictionChampion();
        $predictionChampion->setUserId($user);
        $predictionChampion->setTournamentId($tournament);
        $predictionChampion->setTeamId($team);

        if ($scoreAdded !== null) {
            $predictionChampion->setScoreAdded($scoreAdded);
        }

        if ($points !== null) {
            $predictionChampion->setPoints($points);
        }

        $this->em->persist($predictionChampion);
        $this->em->flush();

        return $predictionChampion;
    }

    protected function createApiMapping($entityObject, $entityType, $apiName, $apiObjectId)
    {
        $apiMapping = new ApiMapping();
        $apiMapping->setEntityId($entityObject->getId());
        $apiMapping->setEntityType($entityType);
        $apiMapping->setApiName($apiName);
        $apiMapping->setApiObjectId($apiObjectId);

        $this->em->persist($apiMapping);
        $this->em->flush();

        return $apiMapping;
    }
}
