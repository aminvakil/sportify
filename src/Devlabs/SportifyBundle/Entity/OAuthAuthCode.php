<?php

namespace Devlabs\SportifyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oauth_auth_codes")
 */
class OAuthAuthCode
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
     * @ORM\Column(type="text", name="redirect_uri")
     */
    protected $redirectUri;

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
}
