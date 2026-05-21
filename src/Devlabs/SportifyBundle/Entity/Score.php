<?php

namespace Devlabs\SportifyBundle\Entity;


class Score
{
    private $id;

    private $userId;

    private $tournamentId;

    private $points = 0;

    private $pointsOld = 0;

    private $posOld = 0;

    private $posNew = 0;

    private $exactPredictionPercentage = 0;

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
     * Set points
     *
     * @param integer $points
     *
     * @return Score
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

    /**
     * Set userId
     *
     * @param \Devlabs\SportifyBundle\Entity\User $userId
     *
     * @return Score
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
     * Set tournamentId
     *
     * @param \Devlabs\SportifyBundle\Entity\Tournament $tournamentId
     *
     * @return Score
     */
    public function setTournamentId(?\Devlabs\SportifyBundle\Entity\Tournament $tournamentId = null)
    {
        $this->tournamentId = $tournamentId;

        return $this;
    }

    /**
     * Get tournamentId
     *
     * @return \Devlabs\SportifyBundle\Entity\Tournament
     */
    public function getTournamentId()
    {
        return $this->tournamentId;
    }

    /**
     * Method for updating the user's points in a tournament
     * by passing the points to be added as an argument
     *
     * @param $addedPoints
     * @return mixed
     */
    public function updatePoints($addedPoints)
    {
        $this->points = $this->points + $addedPoints;

        return $this;
    }

    /**
     * Set posOld
     *
     * @param integer $posOld
     *
     * @return Score
     */
    public function setPosOld($posOld)
    {
        $this->posOld = $posOld;

        return $this;
    }

    /**
     * Get posOld
     *
     * @return integer
     */
    public function getPosOld()
    {
        return $this->posOld;
    }

    /**
     * Set posNew
     *
     * @param integer $posNew
     *
     * @return Score
     */
    public function setPosNew($posNew)
    {
        $this->posNew = $posNew;

        return $this;
    }

    /**
     * Get posNew
     *
     * @return integer
     */
    public function getPosNew()
    {
        return $this->posNew;
    }

    /**
     * Set exactPredictionPercentage
     *
     * @param integer $exactPredictionPercentage
     *
     * @return Score
     */
    public function setExactPredictionPercentage($exactPredictionPercentage)
    {
        $this->exactPredictionPercentage = $exactPredictionPercentage;

        return $this;
    }

    /**
     * Get exactPredictionPercentage
     *
     * @return integer
     */
    public function getExactPredictionPercentage()
    {
        return $this->exactPredictionPercentage;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->userId->getUsername();
    }

    /**
     * @return mixed
     */
    public function getUserEmail()
    {
        return $this->userId->getEmail();
    }

    /**
     * @return mixed
     */
    public function getTournamentName()
    {
        return $this->tournamentId->getName();
    }

    /**
     * Set pointsOld
     *
     * @param integer $pointsOld
     *
     * @return Score
     */
    public function setPointsOld($pointsOld)
    {
        $this->pointsOld = $pointsOld;

        return $this;
    }

    /**
     * Get pointsOld
     *
     * @return integer
     */
    public function getPointsOld()
    {
        return $this->pointsOld;
    }
}
