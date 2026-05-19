<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityPageTest extends WebTestCase
{
    public function testLoginPageLoads()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('Log in to place your bets', $crawler->filter('body')->text());
        $this->assertNotEmpty($crawler->filter('input[name="_csrf_token"]')->attr('value'));
    }

    public function testRegisterPageLoads()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register/');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('Sign up', $crawler->filter('body')->text());
    }
}
