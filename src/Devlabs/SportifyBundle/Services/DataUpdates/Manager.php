<?php

namespace Devlabs\SportifyBundle\Services\DataUpdates;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Devlabs\SportifyBundle\Entity\ApiMapping;
use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Tournament;

/**
 * Class Manager
 * @package Devlabs\SportifyBundle\Services\DataUpdates
 */
class Manager
{
    use ContainerAwareTrait;

    private $em;
    private $footballApi;
    private $dataFetcher;
    private $dataParser;
    private $dataImporter;

    public function __construct(ContainerInterface $container, EntityManager $entityManager, $footballApi,
                                $dataFetcher, $dataParser, $dataImporter)
    {
        $this->container = $container;
        $this->em = $entityManager;
        $this->footballApi = $footballApi;

        $this->dataFetcher = $dataFetcher;
        $this->dataParser = $dataParser;
        $this->dataImporter = $dataImporter;
    }

    /**
     * Update teams via API Fetch, Parse and Import services
     *
     * @param Tournament $tournament
     */
    public function updateTeamsByTournament(Tournament $tournament)
    {
        $apiMapping = $this->em->getRepository(ApiMapping::class)
            ->getByEntityAndApiProvider($tournament, 'Tournament', $this->footballApi);

        if (!$apiMapping) {
            return;
        }

        $apiTournamentId = $apiMapping->getApiObjectId();

        // fetch tournament from API and import its logo if available
        $fetchedTournament = $this->dataFetcher->fetchTournament($apiTournamentId);
        if ($fetchedTournament) {
            $this->dataImporter->importTournamentLogo(
                $this->dataParser->parseTournament($fetchedTournament),
                $tournament
            );
        }

        // fetch teams from API for given tournament
        $fetchedTeams = $this->dataFetcher->fetchTeamsByTournament($apiTournamentId);

        // parse the fetched data
        $parsedTeams = $this->dataParser->parseTeams($fetchedTeams);

        // invoke Importer service and import parsed data
        $this->dataImporter->importTeams($parsedTeams, $tournament, $this->footballApi);
    }

    public function updateResults($dateFrom, $dateTo)
    {
        $status = $this->createFixturesStatus();

        if (!$this->em->getRepository(MatchEntity::class)->hasResultUpdateCandidates(
            $this->createDateTime($dateFrom, false),
            $this->createDateTime($dateTo, true),
            new \DateTime()
        )) {
            return $status;
        }

        return $this->updateFixtures($dateFrom, $dateTo);
    }

    /**
     * Update fixtures data via API Fetch, Parse and Import services
     * for a given time range (start date and end date)
     */
    public function updateFixtures($dateFrom, $dateTo)
    {
        $status = $this->createFixturesStatus();

        // get all tournaments
        $tournaments = $this->em->getRepository(Tournament::class)->findAll();

        // return if no tournaments in db
        if (!$tournaments) {
           return $status;
        }

        // iterate the following actions for each tournament
        foreach ($tournaments as $tournament) {
            $apiMapping = $this->em->getRepository(ApiMapping::class)
                ->getByEntityAndApiProvider($tournament, 'Tournament', $this->footballApi);

            // skip tournament if finished or there is no API mapping for it
            if (($tournament->getChampionTeamId() !== null) || (!$apiMapping)) continue;

            $status['tournaments'][$tournament->getId()]['name'] = $tournament->getName();

            // get the API tournament ID
            $apiTournamentId = $apiMapping->getApiObjectId();

            // fetch fixture data from API for given time range
            $fetchedFixtures = $this->dataFetcher->fetchFixturesByTournamentAndTimeRange($apiTournamentId, $dateFrom, $dateTo);

            // parse the fetched fixture data from API
            $parsedFixtures = $this->dataParser->parseFixtures($fetchedFixtures);

            // use the Importer service to import parsed data and get status and stats of the operation
            $status['tournaments'][$tournament->getId()]['status'] = $this->dataImporter->importFixtures($parsedFixtures, $tournament, $this->footballApi);

            $status['total_fetched'] = $status['total_fetched'] + $status['tournaments'][$tournament->getId()]['status']['fixtures_fetched'];
            $status['total_added'] = $status['total_added'] + $status['tournaments'][$tournament->getId()]['status']['fixtures_added'];
            $status['total_updated'] = $status['total_updated'] + $status['tournaments'][$tournament->getId()]['status']['fixtures_updated'];
            $status['added_fixtures'] = array_merge($status['added_fixtures'], $status['tournaments'][$tournament->getId()]['status']['added_fixtures']);
        }

        return $status;
    }

    private function createFixturesStatus()
    {
        return array(
            'total_fetched' => 0,
            'total_added' => 0,
            'total_updated' => 0,
            'added_fixtures' => array(),
        );
    }

    private function createDateTime($date, $endOfDay)
    {
        if ($date instanceof \DateTime) {
            return $date;
        }

        if (strpos($date, ' ') !== false) {
            return new \DateTime($date);
        }

        $time = $endOfDay ? '23:59:59' : '00:00:00';
        return new \DateTime($date.' '.$time);
    }

    /**
     * Get a list of all tournaments from API
     *
     * @return mixed
     */
    public function getTournaments()
    {
        // fetch tournaments from API
        $fetchedTournaments = $this->dataFetcher->fetchAllTournaments();

        // parse the fetched data
        $parsedTournaments = $this->dataParser->parseTournaments($fetchedTournaments);

        return $parsedTournaments;
    }
}
