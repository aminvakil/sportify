<?php

namespace Devlabs\SportifyBundle\Security;

use Devlabs\SportifyBundle\Entity\OAuthAccessToken;
use Devlabs\SportifyBundle\Entity\OAuthClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class OAuthTokenIssuer
{
    private $em;
    private $userProvider;
    private $passwordEncoder;
    private $userChecker;
    private $accessTokenLifetime;

    public function __construct(
        EntityManagerInterface $em,
        UserProvider $userProvider,
        UserPasswordHasherInterface $passwordEncoder,
        UserChecker $userChecker,
        $accessTokenLifetime
    ) {
        $this->em = $em;
        $this->userProvider = $userProvider;
        $this->passwordEncoder = $passwordEncoder;
        $this->userChecker = $userChecker;
        $this->accessTokenLifetime = $accessTokenLifetime;
    }

    public function issuePasswordGrantToken($clientId, $clientSecret, $username, $password)
    {
        $client = $this->findClientByPublicId($clientId);

        if (!$client || !$client->checkSecret($clientSecret)) {
            throw new BadCredentialsException('Invalid client credentials.');
        }

        if (!in_array('password', $client->getAllowedGrantTypes(), true)) {
            throw new BadCredentialsException('The client is not allowed to use the password grant.');
        }

        try {
            $user = $this->userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            throw new BadCredentialsException('Invalid username and password combination.');
        }

        if (!$this->passwordEncoder->isPasswordValid($user, $password)) {
            throw new BadCredentialsException('Invalid username and password combination.');
        }

        $this->userChecker->checkPreAuth($user);
        $this->userChecker->checkPostAuth($user);

        $accessToken = new OAuthAccessToken();
        $accessToken->setClient($client);
        $accessToken->setUser($user);
        $accessToken->setToken(bin2hex(random_bytes(40)));
        $accessToken->setExpiresAt(time() + $this->accessTokenLifetime);

        $this->em->persist($accessToken);
        $this->em->flush();

        return $accessToken;
    }

    private function findClientByPublicId($publicId)
    {
        if (!preg_match('/^(\d+)_/', (string) $publicId, $matches)) {
            return null;
        }

        $client = $this->em->getRepository(OAuthClient::class)->find($matches[1]);

        if (!$client || $client->getPublicId() !== $publicId) {
            return null;
        }

        return $client;
    }
}
