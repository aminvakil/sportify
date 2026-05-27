<?php

namespace Devlabs\SportifyBundle\Services;

use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Prediction;

class PredictionScorer
{
    const RESULT_WRONG = 'wrong';
    const RESULT_OUTCOME = 'outcome';
    const RESULT_EXACT = 'exact';

    public function score(Prediction $prediction, MatchEntity $match)
    {
        if (($prediction->getHomeGoals() === $match->getHomeGoals()) && ($prediction->getAwayGoals() === $match->getAwayGoals())) {
            $result = self::RESULT_EXACT;
            $basePoints = $match->getBaseExactPoints();
        } elseif ($prediction->getResultOutcome() === $match->getResultOutcome()) {
            $result = self::RESULT_OUTCOME;
            $basePoints = $match->getBaseOutcomePoints();
        } else {
            return new PredictionScoringResult(self::RESULT_WRONG, 0, 0);
        }

        return new PredictionScoringResult($result, $basePoints, $this->calculateProbabilityBonus($prediction, $match));
    }

    private function calculateProbabilityBonus(Prediction $prediction, MatchEntity $match)
    {
        $probability = $match->getProbabilityBpsForOutcome($prediction->getResultOutcome());
        if ($probability === null || $probability >= 5000) {
            return 0;
        }

        $cap = $match->getBaseExactPoints();
        $bonus = intdiv(((5000 - $probability) * $cap) + 4999, 5000);

        return min($cap, $bonus);
    }
}
