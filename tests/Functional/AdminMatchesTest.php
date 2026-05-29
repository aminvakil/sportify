<?php

namespace Tests\Functional;

use Devlabs\SportifyBundle\Entity\Tournament;

class AdminMatchesTest extends FunctionalTestCase
{
    public function testAdminCanLoadMatchesPageWithoutTournaments()
    {
        $this->createUser('admin_matches', 'testpass', true, array('ROLE_ADMIN'));
        $this->login('admin_matches@example.com', 'testpass');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $crawler = $this->client->request('GET', '/admin/matches');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Expected /admin/matches to load, got HTTP '.$this->client->getResponse()->getStatusCode()
        );
        $this->assertStringContainsString('Create/Edit/Delete match', $crawler->filter('body')->text());
    }

    public function testAdminCanLoadMatchesPageWithoutTeams()
    {
        $this->createUser('admin_matches_no_teams', 'testpass', true, array('ROLE_ADMIN'));
        $tournament = new Tournament();
        $tournament->setName('No Teams Cup');
        $tournament->setStartDate(new \DateTime('-1 day'));
        $tournament->setEndDate(new \DateTime('+1 month'));
        $this->em->persist($tournament);
        $this->em->flush();

        $this->login('admin_matches_no_teams@example.com', 'testpass');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $crawler = $this->client->request('GET', '/admin/matches');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Expected /admin/matches to load, got HTTP '.$this->client->getResponse()->getStatusCode()
        );
        $this->assertStringContainsString('Create/Edit/Delete match', $crawler->filter('body')->text());
    }
}
