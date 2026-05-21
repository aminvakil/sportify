<?php

namespace Devlabs\SportifyBundle\Entity;


class PredictionChampion
{
    const POINTS_WIN = 5;

    private $id;

    private $userId;

    private $tournamentId;

    private $teamId;

    private $points;

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
     * Set points
     *
     * @param integer $points
     *
     * @return PredictionWinner
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
     * Set scoreAdded
     *
     * @param boolean $scoreAdded
     *
     * @return PredictionWinner
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
     * Set userId
     *
     * @param \Devlabs\SportifyBundle\Entity\User $userId
     *
     * @return PredictionWinner
     */
    public function setUserId(\Devlabs\SportifyBundle\Entity\User $userId = null)
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
     * @return PredictionWinner
     */
    public function setTournamentId(\Devlabs\SportifyBundle\Entity\Tournament $tournamentId = null)
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
     * Set teamId
     *
     * @param \Devlabs\SportifyBundle\Entity\Team $teamId
     *
     * @return PredictionWinner
     */
    public function setTeamId(\Devlabs\SportifyBundle\Entity\Team $teamId = null)
    {
        $this->teamId = $teamId;

        return $this;
    }

    /**
     * Get teamId
     *
     * @return \Devlabs\SportifyBundle\Entity\Team
     */
    public function getTeamId()
    {
        return $this->teamId;
    }

    /**
     * Calculate the points from the prediction
     *
     * @return int
     */
    public function calculatePoints()
    {
        if ($this->teamId === $this->tournamentId->getChampionTeamId()) {
            return self::POINTS_WIN;
        }

        return 0;
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
     * @return mixed
     */
    public function getTeamName()
    {
        return $this->teamId->getName();
    }
}
