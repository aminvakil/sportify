<?php

namespace Tests\Functional;

require_once __DIR__.'/FunctionalTestCase.php';

class ApiAuthenticationTest extends FunctionalTestCase
{
    public function testApiRequiresAnAccessToken()
    {
        $this->client->request('GET', '/api/users');

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testInvalidAccessTokenIsRejected()
    {
        $this->client->request('GET', '/api/users', array('access_token' => 'invalid-token'));

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testPasswordGrantTokenAllowsAccessToCurrentUserApiResource()
    {
        $user = $this->createUser('api_user', 'api-password');
        $oauthClient = $this->createOAuthClient(array('password'));

        $this->client->request('POST', '/oauth/v2/token', array(
            'client_id' => $oauthClient->getPublicId(),
            'client_secret' => $oauthClient->getSecret(),
            'grant_type' => 'password',
            'username' => 'api_user@example.com',
            'password' => 'api-password',
        ));

        $this->assertSame(200, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $tokenResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $tokenResponse);

        $this->client->request('GET', '/api/users/'.$user->getId(), array('access_token' => $tokenResponse['access_token']));

        $this->assertSame(200, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertContains('api_user@example.com', $this->client->getResponse()->getContent());
    }

    public function testPasswordGrantRejectsBadPassword()
    {
        $this->createUser('api_user', 'api-password');
        $oauthClient = $this->createOAuthClient(array('password'));

        $this->client->request('POST', '/oauth/v2/token', array(
            'client_id' => $oauthClient->getPublicId(),
            'client_secret' => $oauthClient->getSecret(),
            'grant_type' => 'password',
            'username' => 'api_user@example.com',
            'password' => 'wrong-password',
        ));

        $this->assertSame(400, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertContains('invalid_grant', $this->client->getResponse()->getContent());
    }
}
