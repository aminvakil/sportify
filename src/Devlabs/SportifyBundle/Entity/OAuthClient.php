<?php

namespace Devlabs\SportifyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oauth_clients")
 */
class OAuthClient
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, name="name")
     */
    protected $name;

    /**
     * @ORM\Column(type="string", name="random_id")
     */
    protected $randomId;

    /**
     * @ORM\Column(type="string", name="secret")
     */
    protected $secret;

    /**
     * @ORM\Column(type="array", name="redirect_uris")
     */
    protected $redirectUris = array();

    /**
     * @ORM\Column(type="array", name="allowed_grant_types")
     */
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
