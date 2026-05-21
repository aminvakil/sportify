<?php

namespace Devlabs\SportifyBundle\Entity;


class OAuthAuthCode
{
    protected $id;

    protected $token;

    protected $redirectUri;

    protected $expiresAt;

    protected $scope;

    protected $user;

    protected $client;
}
