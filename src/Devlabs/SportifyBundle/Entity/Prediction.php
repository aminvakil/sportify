<?php

namespace Devlabs\SportifyBundle\Entity;


class Prediction
{
    const POINTS_OUTCOME = 2;
    const POINTS_EXACT = 5;
    const SCORING_RESULT_WRONG = 'wrong';
    const SCORING_RESULT_OUTCOME = 'outcome';
    const SCORING_RESULT_EXACT = 'exact';

    private $id;

    private $matchId;

    private $userId;

    private $homeGoals;

    private $awayGoals;

    private $points;

    private $scoringResult;

    private $basePoints;

    private $probabilityBonus;

    private $totalPoints;

    private $scoreAdded = 0;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param string $id
     *
     * @return Prediction
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set homeGoals
     *
     * @param integer $homeGoals
     *
     * @return Prediction
     */
    public function setHomeGoals($homeGoals)
    {
        $this->homeGoals = $homeGoals;

        return $this;
    }

    /**
     * Get homeGoals
     *
     * @return integer
     */
    public function getHomeGoals()
    {
        return $this->homeGoals;
    }

    /**
     * Set awayGoals
     *
     * @param integer $awayGoals
     *
     * @return Prediction
     */
    public function setAwayGoals($awayGoals)
    {
        $this->awayGoals = $awayGoals;

        return $this;
    }

    /**
     * Get awayGoals
     *
     * @return integer
     */
    public function getAwayGoals()
    {
        return $this->awayGoals;
    }

    /**
     * Set points
     *
     * @param integer $points
     *
     * @return Prediction
     */
    public function setPoints($points)
    {
        $this->points = $points;

        return $this;
    }

    /**
     * Get points
     *
     * @return integer
     */
    public function getPoints()
    {
        return $this->points;
    }

    public function setScoringResult($scoringResult)
    {
        $this->scoringResult = $scoringResult;

        return $this;
    }

    public function getScoringResult()
    {
        return $this->scoringResult;
    }

    public function setBasePoints($basePoints)
    {
        $this->basePoints = $basePoints;

        return $this;
    }

    public function getBasePoints()
    {
        return $this->basePoints;
    }

    public function setProbabilityBonus($probabilityBonus)
    {
        $this->probabilityBonus = $probabilityBonus;

        return $this;
    }

    public function getProbabilityBonus()
    {
        return $this->probabilityBonus;
    }

    public function setTotalPoints($totalPoints)
    {
        $this->totalPoints = $totalPoints;

        return $this;
    }

    public function getTotalPoints()
    {
        return $this->totalPoints;
    }

    /**
     * Set scoreAdded
     *
     * @param boolean $scoreAdded
     *
     * @return Prediction
     */
    public function setScoreAdded($scoreAdded)
    {
        $this->scoreAdded = $scoreAdded;

        return $this;
    }

    /**
     * Get scoreAdded
     *
     * @return boolean
     */
    public function getScoreAdded()
    {
        return $this->scoreAdded;
    }

    /**
     * Set matchId
     *
     * @param \Devlabs\SportifyBundle\Entity\MatchEntity $matchId
     *
     * @return Prediction
     */
    public function setMatchId(?\Devlabs\SportifyBundle\Entity\MatchEntity $matchId = null)
    {
        $this->matchId = $matchId;

        return $this;
    }

    /**
     * Get matchId
     *
     * @return \Devlabs\SportifyBundle\Entity\MatchEntity
     */
    public function getMatchId()
    {
        return $this->matchId;
    }

    /**
     * Set userId
     *
     * @param \Devlabs\SportifyBundle\Entity\User $userId
     *
     * @return Prediction
     */
    public function setUserId(?\Devlabs\SportifyBundle\Entity\User $userId = null)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return \Devlabs\SportifyBundle\Entity\User
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Get the outcome of the match
     *
     * @return string
     */
    public function getResultOutcome()
    {
        if ($this->homeGoals > $this->awayGoals) {
            return '1';
        } else if ($this->homeGoals < $this->awayGoals) {
            return '2';
        }

        return 'X';
    }

    /**
     * Calculate the points from the prediction
     *
     * @param MatchEntity $match
     * @return int
     */
    public function calculatePoints(MatchEntity $match)
    {
        return (new \Devlabs\SportifyBundle\Services\PredictionScorer())->score($this, $match)->getTotalPoints();
    }

    /**
     * @return mixed
     */
    public function getHomeTeamName()
    {
        return $this->matchId->getHomeTeamId()->getName();
    }

    /**
     * @return mixed
     */
    public function getAwayTeamName()
    {
        return $this->matchId->getAwayTeamId()->getName();
    }

    /**
     * @return mixed
     */
    public function getResultHomeGoals()
    {
        return $this->matchId->getHomeGoals();
    }

    /**
     * @return mixed
     */
    public function getResultAwayGoals()
    {
        return $this->matchId->getAwayGoals();
    }
}
