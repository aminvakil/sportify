<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Services\PredictionScorer;
use PHPUnit\Framework\TestCase;

class PredictionScorerTest extends TestCase
{
    /**
     * @dataProvider scoringExamples
     */
    public function testCalculatesBasePointsAndProbabilityBonus($predictedHome, $predictedAway, $homeProbabilityBps, $expectedResult, $expectedBase, $expectedBonus, $expectedTotal)
    {
        $match = $this->createFinishedMatch(2, 1);
        $match->setBaseOutcomePoints(2);
        $match->setBaseExactPoints(10);
        $match->setHomeWinProbabilityBps($homeProbabilityBps);
        $match->setDrawProbabilityBps(2500);
        $match->setAwayWinProbabilityBps(6500);

        $prediction = $this->createPrediction($predictedHome, $predictedAway);

        $result = (new PredictionScorer())->score($prediction, $match);

        $this->assertSame($expectedResult, $result->getResult());
        $this->assertSame($expectedBase, $result->getBasePoints());
        $this->assertSame($expectedBonus, $result->getProbabilityBonus());
        $this->assertSame($expectedTotal, $result->getTotalPoints());
    }

    public function scoringExamples()
    {
        return array(
            'exact score plus low-probability bonus' => array(2, 1, 1000, PredictionScorer::RESULT_EXACT, 10, 8, 18),
            'correct outcome plus low-probability bonus' => array(1, 0, 1000, PredictionScorer::RESULT_OUTCOME, 2, 8, 10),
            'wrong outcome scores zero' => array(0, 1, 1000, PredictionScorer::RESULT_WRONG, 0, 0, 0),
            'probability at fifty has no bonus' => array(1, 0, 5000, PredictionScorer::RESULT_OUTCOME, 2, 0, 2),
            'probability just below fifty rounds up' => array(1, 0, 4999, PredictionScorer::RESULT_OUTCOME, 2, 1, 3),
            'very low probability bonus is capped at exact points' => array(1, 0, 0, PredictionScorer::RESULT_OUTCOME, 2, 10, 12),
        );
    }

    public function testMissingProbabilitySnapshotHasNoBonus()
    {
        $match = $this->createFinishedMatch(2, 1);
        $prediction = $this->createPrediction(1, 0);

        $result = (new PredictionScorer())->score($prediction, $match);

        $this->assertSame(PredictionScorer::RESULT_OUTCOME, $result->getResult());
        $this->assertSame(2, $result->getBasePoints());
        $this->assertSame(0, $result->getProbabilityBonus());
        $this->assertSame(2, $result->getTotalPoints());
    }

    private function createFinishedMatch($homeGoals, $awayGoals)
    {
        $match = new MatchEntity();
        $match->setHomeGoals($homeGoals);
        $match->setAwayGoals($awayGoals);

        return $match;
    }

    private function createPrediction($homeGoals, $awayGoals)
    {
        $prediction = new Prediction();
        $prediction->setHomeGoals($homeGoals);
        $prediction->setAwayGoals($awayGoals);

        return $prediction;
    }
}
