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
        $this->applyTournamentScoringRules($match);

        return $match;
    }

    public function applyTournamentScoringRules(MatchEntity $match)
    {
        $points = $this->getWorldCup2026Points($match);
        if ($points === null) {
            return $match;
        }

        $match->setBaseOutcomePoints($points['outcome']);
        $match->setBaseExactPoints($points['exact']);

        return $match;
    }

    private function getWorldCup2026Points(MatchEntity $match)
    {
        $tournament = $match->getTournamentId();
        $datetime = $match->getDatetime();
        if ($tournament === null || $datetime === null || stripos($tournament->getName(), 'World Cup') === false) {
            return null;
        }

        $date = $datetime->format('Y-m-d');
        $stages = array(
            array('from' => '2026-06-28', 'to' => '2026-07-03', 'outcome' => 3, 'exact' => 6),
            array('from' => '2026-07-04', 'to' => '2026-07-07', 'outcome' => 4, 'exact' => 8),
            array('from' => '2026-07-09', 'to' => '2026-07-11', 'outcome' => 4, 'exact' => 8),
            array('from' => '2026-07-14', 'to' => '2026-07-15', 'outcome' => 5, 'exact' => 10),
            array('from' => '2026-07-19', 'to' => '2026-07-19', 'outcome' => 5, 'exact' => 10),
        );

        foreach ($stages as $stage) {
            if ($date >= $stage['from'] && $date <= $stage['to']) {
                return $stage;
            }
        }

        return null;
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
