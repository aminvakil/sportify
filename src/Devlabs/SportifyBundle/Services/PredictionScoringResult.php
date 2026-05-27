<?php

namespace Devlabs\SportifyBundle\Services;

class PredictionScoringResult
{
    private $result;
    private $basePoints;
    private $probabilityBonus;
    private $totalPoints;

    public function __construct($result, $basePoints, $probabilityBonus)
    {
        $this->result = $result;
        $this->basePoints = $basePoints;
        $this->probabilityBonus = $probabilityBonus;
        $this->totalPoints = $basePoints + $probabilityBonus;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getBasePoints()
    {
        return $this->basePoints;
    }

    public function getProbabilityBonus()
    {
        return $this->probabilityBonus;
    }

    public function getTotalPoints()
    {
        return $this->totalPoints;
    }
}
