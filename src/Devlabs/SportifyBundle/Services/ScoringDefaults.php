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
