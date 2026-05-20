<?php

namespace Devlabs\SportifyBundle\Security;

use Devlabs\SportifyBundle\Entity\OAuthAccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class OAuthTokenAuthenticator extends AbstractGuardAuthenticator
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function supports(Request $request)
    {
        return null !== $this->getTokenForRequest($request);
    }

    public function getCredentials(Request $request)
    {
        return $this->getTokenForRequest($request);
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $accessToken = $this->em->getRepository(OAuthAccessToken::class)->findOneBy(array('token' => $credentials));

        if (!$accessToken || $accessToken->hasExpired()) {
            return null;
        }

        return $accessToken->getUser();
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse(array(
            'error' => 'invalid_token',
            'error_description' => 'The access token is invalid or has expired.',
        ), 401);
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse(array(
            'error' => 'access_denied',
            'error_description' => 'OAuth2 authentication required.',
        ), 401);
    }

    public function supportsRememberMe()
    {
        return false;
    }

    private function getTokenForRequest(Request $request)
    {
        if ($request->query->has('access_token')) {
            return $request->query->get('access_token');
        }

        if ($request->request->has('access_token')) {
            return $request->request->get('access_token');
        }

        $authorization = $request->headers->get('Authorization');
        if ($authorization && 0 === strpos($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }

        return null;
    }
}
