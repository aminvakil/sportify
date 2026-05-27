<?php

namespace Devlabs\SportifyBundle\Entity;

class ScoringSettings
{
    private $id;
    private $outcomePoints;
    private $exactPoints;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getOutcomePoints()
    {
        return $this->outcomePoints;
    }

    public function setOutcomePoints($outcomePoints)
    {
        $this->outcomePoints = $outcomePoints;

        return $this;
    }

    public function getExactPoints()
    {
        return $this->exactPoints;
    }

    public function setExactPoints($exactPoints)
    {
        $this->exactPoints = $exactPoints;

        return $this;
    }
}
