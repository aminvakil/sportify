<?php

namespace Devlabs\SportifyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OAuthTokenController extends AbstractController
{
    public function tokenAction(Request $request)
    {
        if ('password' !== $request->request->get('grant_type')) {
            return $this->errorResponse('unsupported_grant_type', 'Only the password grant type is supported.');
        }

        try {
            $accessToken = $this->container->get('app.oauth_token_issuer')->issuePasswordGrantToken(
                $request->request->get('client_id'),
                $request->request->get('client_secret'),
                $request->request->get('username'),
                $request->request->get('password')
            );
        } catch (AuthenticationException $e) {
            return $this->errorResponse('invalid_grant', $e->getMessage());
        }

        return new JsonResponse(array(
            'access_token' => $accessToken->getToken(),
            'expires_in' => $accessToken->getExpiresIn(),
            'token_type' => 'bearer',
            'scope' => $accessToken->getScope(),
        ));
    }

    private function errorResponse($error, $description)
    {
        return new JsonResponse(array(
            'error' => $error,
            'error_description' => $description,
        ), 400);
    }
}
