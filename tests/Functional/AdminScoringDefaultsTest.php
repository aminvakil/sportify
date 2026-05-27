<?php

namespace Tests\Functional;

class AdminScoringDefaultsTest extends FunctionalTestCase
{
    public function testAdminCanUpdateDefaultBaseScoring()
    {
        $this->createUser('admin_scoring_defaults', 'testpass', true, array('ROLE_ADMIN'));
        $this->login('admin_scoring_defaults@example.com', 'testpass');

        $crawler = $this->client->request('GET', '/admin/scoring');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertStringContainsString('Default base scoring', $crawler->filter('body')->text());

        $form = $crawler->selectButton('Save')->form(array(
            'scoring_defaults[outcomePoints]' => 3,
            'scoring_defaults[exactPoints]' => 7,
        ));
        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect('/admin/scoring'));
        $this->client->followRedirect();

        $defaults = $this->client->getContainer()->get('app.scoring_defaults');
        $this->assertSame(3, $defaults->getOutcomePoints());
        $this->assertSame(7, $defaults->getExactPoints());
    }
}
