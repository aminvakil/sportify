<?php

namespace Devlabs\SportifyBundle\Entity;


class MatchEntity
{
    private $id;

    private $datetime;

    private $homeTeamId;

    private $awayTeamId;

    private $homeGoals;

    private $awayGoals;

    private $homeWinProbabilityBps;

    private $drawProbabilityBps;

    private $awayWinProbabilityBps;

    private $probabilitySource;

    private $baseOutcomePoints = 2;

    private $baseExactPoints = 5;

    private $tournamentId;

    private $predictions;

    /**
     * Property for indicating whether match form should be locked/disabled
     *
     * @var bool
     */
    private $disabledAttribute = false;

    private $notificationSent = 0;

    private $predictionsNotificationSent = 0;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->predictions = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * @return Match
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set datetime
     *
     * @param \DateTime $datetime
     *
     * @return Match
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * Get datetime
     *
     * @return \DateTime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * Set homeGoals
     *
     * @param integer $homeGoals
     *
     * @return Match
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
     * @return Match
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

    public function setHomeWinProbabilityBps($homeWinProbabilityBps)
    {
        $this->homeWinProbabilityBps = $homeWinProbabilityBps;

        return $this;
    }

    public function getHomeWinProbabilityBps()
    {
        return $this->homeWinProbabilityBps;
    }

    public function setDrawProbabilityBps($drawProbabilityBps)
    {
        $this->drawProbabilityBps = $drawProbabilityBps;

        return $this;
    }

    public function getDrawProbabilityBps()
    {
        return $this->drawProbabilityBps;
    }

    public function setAwayWinProbabilityBps($awayWinProbabilityBps)
    {
        $this->awayWinProbabilityBps = $awayWinProbabilityBps;

        return $this;
    }

    public function getAwayWinProbabilityBps()
    {
        return $this->awayWinProbabilityBps;
    }

    public function setProbabilitySource($probabilitySource)
    {
        $this->probabilitySource = $probabilitySource;

        return $this;
    }

    public function getProbabilitySource()
    {
        return $this->probabilitySource;
    }

    public function setBaseOutcomePoints($baseOutcomePoints)
    {
        $this->baseOutcomePoints = $baseOutcomePoints;

        return $this;
    }

    public function getBaseOutcomePoints()
    {
        return $this->baseOutcomePoints;
    }

    public function setBaseExactPoints($baseExactPoints)
    {
        $this->baseExactPoints = $baseExactPoints;

        return $this;
    }

    public function getBaseExactPoints()
    {
        return $this->baseExactPoints;
    }

    public function getProbabilityBpsForOutcome($outcome)
    {
        if ($outcome === '1') {
            return $this->homeWinProbabilityBps;
        }
        if ($outcome === 'X') {
            return $this->drawProbabilityBps;
        }
        if ($outcome === '2') {
            return $this->awayWinProbabilityBps;
        }

        return null;
    }

    /**
     * Set tournamentId
     *
     * @param \Devlabs\SportifyBundle\Entity\Tournament $tournamentId
     *
     * @return Match
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

    public function __toString() {
        return "$this->id";
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
     * Check if match has started by comparing the current time with the match's datetime
     *
     * @return bool
     */
    public function hasStarted()
    {
        return (time() >= strtotime($this->datetime->format('Y-m-d H:i:s')));
    }

    /**
     * Get disabled
     *
     * @return mixed
     */
    public function getDisabledAttribute()
    {
        return $this->disabledAttribute;
    }

    /**
     * Set disabled
     */
    public function setDisabledAttribute()
    {
        $this->disabledAttribute = true;
    }

    /**
     * Add prediction
     *
     * @param \Devlabs\SportifyBundle\Entity\Prediction $prediction
     *
     * @return Match
     */
    public function addPrediction(\Devlabs\SportifyBundle\Entity\Prediction $prediction)
    {
        $this->predictions[] = $prediction;

        return $this;
    }

    /**
     * Remove prediction
     *
     * @param \Devlabs\SportifyBundle\Entity\Prediction $prediction
     */
    public function removePrediction(\Devlabs\SportifyBundle\Entity\Prediction $prediction)
    {
        $this->predictions->removeElement($prediction);
    }

    /**
     * Get predictions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPredictions()
    {
        return $this->predictions;
    }

    /**
     * Set homeTeamId
     *
     * @param \Devlabs\SportifyBundle\Entity\Team $homeTeamId
     *
     * @return Match
     */
    public function setHomeTeamId(?\Devlabs\SportifyBundle\Entity\Team $homeTeamId = null)
    {
        $this->homeTeamId = $homeTeamId;

        return $this;
    }

    /**
     * Get homeTeamId
     *
     * @return \Devlabs\SportifyBundle\Entity\Team
     */
    public function getHomeTeamId()
    {
        return $this->homeTeamId;
    }

    /**
     * Set awayTeamId
     *
     * @param \Devlabs\SportifyBundle\Entity\Team $awayTeamId
     *
     * @return Match
     */
    public function setAwayTeamId(?\Devlabs\SportifyBundle\Entity\Team $awayTeamId = null)
    {
        $this->awayTeamId = $awayTeamId;

        return $this;
    }

    /**
     * Get awayTeamId
     *
     * @return \Devlabs\SportifyBundle\Entity\Team
     */
    public function getAwayTeamId()
    {
        return $this->awayTeamId;
    }

    /**
     * Set notificationSent
     *
     * @param boolean $notificationSent
     *
     * @return Match
     */
    public function setNotificationSent($notificationSent)
    {
        $this->notificationSent = $notificationSent;

        return $this;
    }

    /**
     * Get notificationSent
     *
     * @return boolean
     */
    public function getNotificationSent()
    {
        return (bool) $this->notificationSent;
    }

    /**
     * Set predictionsNotificationSent
     *
     * @param boolean $predictionsNotificationSent
     *
     * @return Match
     */
    public function setPredictionsNotificationSent($predictionsNotificationSent)
    {
        $this->predictionsNotificationSent = $predictionsNotificationSent;

        return $this;
    }

    /**
     * Get predictionsNotificationSent
     *
     * @return boolean
     */
    public function getPredictionsNotificationSent()
    {
        return (bool) $this->predictionsNotificationSent;
    }

    /**
     * @return mixed
     */
    public function getHomeTeamName()
    {
        return $this->homeTeamId->getName();
    }

    /**
     * @return mixed
     */
    public function getAwayTeamName()
    {
        return $this->awayTeamId->getName();
    }

    /**
     * @return mixed
     */
    public function getTournamentName()
    {
        return $this->tournamentId->getName();
    }
}
