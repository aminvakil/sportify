<?php

namespace Devlabs\SportifyBundle\Services;

use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\ScoringSettings;
use Doctrine\ORM\EntityManager;

class ScoringDefaults
{
    private $em;
    private $defaultOutcomePoints;
    private $defaultExactPoints;

    public function __construct(EntityManager $entityManager, $defaultOutcomePoints, $defaultExactPoints)
    {
        $this->em = $entityManager;
        $this->defaultOutcomePoints = (int) $defaultOutcomePoints;
        $this->defaultExactPoints = (int) $defaultExactPoints;
    }

    public function getOutcomePoints()
    {
        return $this->getSettings()->getOutcomePoints();
    }

    public function getExactPoints()
    {
        return $this->getSettings()->getExactPoints();
    }

    public function updateDefaults($outcomePoints, $exactPoints)
    {
        $settings = $this->getSettings();
        $settings->setOutcomePoints((int) $outcomePoints);
        $settings->setExactPoints((int) $exactPoints);

        $this->em->persist($settings);
        $this->em->flush();

        return $settings;
    }

    public function applyToMatch(MatchEntity $match)
    {
        $match->setBaseOutcomePoints($this->getOutcomePoints());
        $match->setBaseExactPoints($this->getExactPoints());

        return $match;
    }

    public function applyFootballDataStageScoring(MatchEntity $match, $stage)
    {
        $points = $this->getWorldCup2026StagePoints($match, $stage);
        if ($points === null) {
            return $match;
        }

        $match->setBaseOutcomePoints($points['outcome']);
        $match->setBaseExactPoints($points['exact']);

        return $match;
    }

    private function getWorldCup2026StagePoints(MatchEntity $match, $stage)
    {
        $tournament = $match->getTournamentId();
        if ($tournament === null || stripos($tournament->getName(), 'World Cup') === false) {
            return null;
        }

        $stages = array(
            'LAST_32' => array('outcome' => 3, 'exact' => 6),
            'LAST_16' => array('outcome' => 4, 'exact' => 8),
            'QUARTER_FINALS' => array('outcome' => 4, 'exact' => 8),
            'SEMI_FINALS' => array('outcome' => 5, 'exact' => 10),
            'THIRD_PLACE' => array('outcome' => 5, 'exact' => 10),
            'FINAL' => array('outcome' => 5, 'exact' => 10),
        );

        return isset($stages[$stage]) ? $stages[$stage] : null;
    }

    private function getSettings()
    {
        $settings = $this->em->getRepository(ScoringSettings::class)->find(1);
        if (!$settings) {
            $settings = new ScoringSettings();
            $settings->setId(1);
            $settings->setOutcomePoints($this->defaultOutcomePoints);
            $settings->setExactPoints($this->defaultExactPoints);
        }

        return $settings;
    }
}
