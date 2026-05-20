<?php

namespace Devlabs\SportifyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Devlabs\SportifyBundle\Entity\OAuthAccessTokenRepository")
 * @ORM\Table(name="oauth_access_tokens")
 */
class OAuthAccessToken
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true)
     */
    protected $token;

    /**
     * @ORM\Column(type="integer", name="expires_at", nullable=true)
     */
    protected $expiresAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $scope;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="OAuthClient")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    protected $client;

    public function getId()
    {
        return $this->id;
    }

    public function getClientId()
    {
        return $this->getClient()->getPublicId();
    }

    public function setExpiresAt($timestamp)
    {
        $this->expiresAt = $timestamp;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function getExpiresIn()
    {
        if ($this->expiresAt) {
            return $this->expiresAt - time();
        }

        return PHP_INT_MAX;
    }

    public function hasExpired()
    {
        if ($this->expiresAt) {
            return time() > $this->expiresAt;
        }

        return false;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setClient(OAuthClient $client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }
}
