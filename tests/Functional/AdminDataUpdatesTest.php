<?php

namespace Tests\Functional;

use Devlabs\SportifyBundle\Entity\Tournament;

class AdminDataUpdatesTest extends FunctionalTestCase
{
    public function testAdminDataUpdatesShowsFlashWhenNoUpdatesAreApplied()
    {
        $this->createUser('admin_data_updates', 'testpass', true, array('ROLE_ADMIN'));
        $tournament = new Tournament();
        $tournament->setName('No Updates Cup');
        $tournament->setStartDate(new \DateTime('-1 month'));
        $tournament->setEndDate(new \DateTime('+1 month'));
        $this->em->persist($tournament);
        $this->em->flush();

        $this->login('admin_data_updates@example.com', 'testpass');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $crawler = $this->client->request('GET', '/admin/data_updates/match_results');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->submit($crawler->selectButton('Update')->form());
        $this->assertTrue($this->client->getResponse()->isRedirect('/admin/data_updates'));

        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertStringContainsString('No fixtures/results added or updated.', $crawler->filter('body')->text());
    }
}
