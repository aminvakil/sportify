<?php

namespace Devlabs\SportifyBundle\Entity;


class OAuthClient
{
    protected $id;

    protected $name;

    protected $randomId;

    protected $secret;

    protected $redirectUris = array();

    protected $allowedGrantTypes = array('authorization_code');

    public function __construct()
    {
        $this->randomId = bin2hex(random_bytes(20));
        $this->secret = bin2hex(random_bytes(20));
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return OAuthClient
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setRandomId($randomId)
    {
        $this->randomId = $randomId;
    }

    public function getRandomId()
    {
        return $this->randomId;
    }

    public function getPublicId()
    {
        return sprintf('%s_%s', $this->getId(), $this->getRandomId());
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function checkSecret($secret)
    {
        return null === $this->secret || $secret === $this->secret;
    }

    public function setRedirectUris(array $redirectUris)
    {
        $this->redirectUris = $redirectUris;
    }

    public function getRedirectUris()
    {
        return $this->redirectUris;
    }

    public function setAllowedGrantTypes(array $allowedGrantTypes)
    {
        $this->allowedGrantTypes = $allowedGrantTypes;
    }

    public function getAllowedGrantTypes()
    {
        return $this->allowedGrantTypes;
    }
}
