<?php

namespace Tests\Functional;

require_once __DIR__.'/FunctionalTestCase.php';

class ProtectedPageTest extends FunctionalTestCase
{
    /**
     * @dataProvider protectedPageProvider
     */
    public function testAnonymousUsersAreRedirectedToLogin($path)
    {
        $this->client->request('GET', $path);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertMatchesRegularExpression('#/login$#', $response->headers->get('Location'));
    }

    /**
     * @dataProvider protectedPageProvider
     */
    public function testAuthenticatedUsersCanLoadProtectedPages($path)
    {
        $this->createUser('protected_user', 'testpass');
        $this->login('protected_user@example.com', 'testpass');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->request('GET', $path);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function protectedPageProvider()
    {
        return array(
            array('/user/profile'),
            array('/tournaments'),
            array('/champion'),
            array('/matches'),
            array('/history'),
            array('/standings'),
            array('/standing'),
            array('/rules'),
        );
    }
}
