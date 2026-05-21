<?php

namespace Devlabs\SportifyBundle\Entity;


class OAuthRefreshToken
{
    protected $id;

    protected $token;

    protected $expiresAt;

    protected $scope;

    protected $user;

    protected $client;
}
