<?php

namespace Devlabs\SportifyBundle\Security;

use Devlabs\SportifyBundle\Entity\OAuthAccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class OAuthTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function supports(Request $request): ?bool
    {
        return null !== $this->getTokenForRequest($request);
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $credentials = $this->getTokenForRequest($request);

        return new SelfValidatingPassport(new UserBadge($credentials, function ($credentials) {
            $accessToken = $this->em->getRepository(OAuthAccessToken::class)->findOneBy(array('token' => $credentials));

            if (!$accessToken || $accessToken->hasExpired()) {
                throw new CustomUserMessageAuthenticationException('The access token is invalid or has expired.');
            }

            return $accessToken->getUser();
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(array(
            'error' => 'invalid_token',
            'error_description' => 'The access token is invalid or has expired.',
        ), 401);
    }

    public function start(Request $request, ?AuthenticationException $authException = null)
    {
        return new JsonResponse(array(
            'error' => 'access_denied',
            'error_description' => 'OAuth2 authentication required.',
        ), 401);
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
