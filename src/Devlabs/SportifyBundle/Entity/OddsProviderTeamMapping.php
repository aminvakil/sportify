<?php

namespace Devlabs\SportifyBundle\Entity;

class OddsProviderTeamMapping
{
    private $id;

    private $tournamentId;

    private $provider;

    private $providerTeamName;

    private $normalizedProviderTeamName;

    private $teamId;

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
     * Set tournamentId
     *
     * @param integer $tournamentId
     *
     * @return OddsProviderTeamMapping
     */
    public function setTournamentId($tournamentId)
    {
        $this->tournamentId = $tournamentId;

        return $this;
    }

    /**
     * Get tournamentId
     *
     * @return integer
     */
    public function getTournamentId()
    {
        return $this->tournamentId;
    }

    /**
     * Set provider
     *
     * @param string $provider
     *
     * @return OddsProviderTeamMapping
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get provider
     *
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set providerTeamName
     *
     * @param string $providerTeamName
     *
     * @return OddsProviderTeamMapping
     */
    public function setProviderTeamName($providerTeamName)
    {
        $this->providerTeamName = $providerTeamName;

        return $this;
    }

    /**
     * Get providerTeamName
     *
     * @return string
     */
    public function getProviderTeamName()
    {
        return $this->providerTeamName;
    }

    /**
     * Set normalizedProviderTeamName
     *
     * @param string $normalizedProviderTeamName
     *
     * @return OddsProviderTeamMapping
     */
    public function setNormalizedProviderTeamName($normalizedProviderTeamName)
    {
        $this->normalizedProviderTeamName = $normalizedProviderTeamName;

        return $this;
    }

    /**
     * Get normalizedProviderTeamName
     *
     * @return string
     */
    public function getNormalizedProviderTeamName()
    {
        return $this->normalizedProviderTeamName;
    }

    /**
     * Set teamId
     *
     * @param integer $teamId
     *
     * @return OddsProviderTeamMapping
     */
    public function setTeamId($teamId)
    {
        $this->teamId = $teamId;

        return $this;
    }

    /**
     * Get teamId
     *
     * @return integer
     */
    public function getTeamId()
    {
        return $this->teamId;
    }
}
