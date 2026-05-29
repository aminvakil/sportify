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
        $this->assertStringContainsString('These defaults are applied to newly added matches.', $crawler->filter('body')->text());
        $this->assertSame(2, $crawler->filter('.panel .form-control')->count());
        $this->assertSame(1, $crawler->filter('.panel .green-btn')->count());

        $form = $crawler->selectButton('Save')->form(array(
            'scoring_defaults[outcomePoints]' => 4,
            'scoring_defaults[exactPoints]' => 9,
        ));
        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect('/admin/scoring'));
        $this->client->followRedirect();

        $defaults = $this->client->getContainer()->get('app.scoring_defaults');
        $this->assertSame(4, $defaults->getOutcomePoints());
        $this->assertSame(9, $defaults->getExactPoints());
    }

    public function testAdminCannotSaveInvalidDefaultBaseScoring()
    {
        $this->createUser('admin_invalid_scoring_defaults', 'testpass', true, array('ROLE_ADMIN'));
        $this->login('admin_invalid_scoring_defaults@example.com', 'testpass');

        $this->client->request('POST', '/admin/scoring', array(
            'scoring_defaults' => array(
                'outcomePoints' => 5,
                'exactPoints' => 4,
                'button' => '',
            ),
        ));

        $this->assertFalse($this->client->getResponse()->isRedirect());

        $defaults = $this->client->getContainer()->get('app.scoring_defaults');
        $this->assertSame(2, $defaults->getOutcomePoints());
        $this->assertSame(5, $defaults->getExactPoints());
    }
}
