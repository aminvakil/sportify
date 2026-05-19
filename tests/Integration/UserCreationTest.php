<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

use Devlabs\SportifyBundle\Entity\User;

class UserCreationTest extends DatabaseTestCase
{
    public function testCreateUserPersistsAllRequiredFields()
    {
        $user = $this->createUser('alice_test');

        $this->em->clear();
        $fetched = $this->em->getRepository(User::class)->find($user->getId());

        $this->assertNotNull($fetched);
        $this->assertSame('alice_test', $fetched->getUsername());
        $this->assertSame('alice_test', $fetched->getUsernameCanonical());
        $this->assertSame('alice_test@example.com', $fetched->getEmail());
        $this->assertSame('alice_test@example.com', $fetched->getEmailCanonical());
        $this->assertTrue($fetched->isEnabled());
    }

    public function testCreateDisabledUserIsStoredAsDisabled()
    {
        $user = $this->createUser('bob_disabled', false);

        $this->em->clear();
        $fetched = $this->em->getRepository(User::class)->find($user->getId());

        $this->assertNotNull($fetched);
        $this->assertFalse($fetched->isEnabled());
    }

    public function testGetAllEnabledExcludesDisabledUsers()
    {
        $enabled = $this->createUser('enabled_user');
        $disabled = $this->createUser('disabled_user', false);

        $allEnabled = $this->em->getRepository(User::class)->getAllEnabled();
        $enabledIds = array_map(function ($u) { return $u->getId(); }, $allEnabled);

        $this->assertContains($enabled->getId(), $enabledIds);
        $this->assertNotContains($disabled->getId(), $enabledIds);
    }

    public function testMultipleUsersReceiveUniqueIds()
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');

        $this->assertNotNull($alice->getId());
        $this->assertNotNull($bob->getId());
        $this->assertNotSame($alice->getId(), $bob->getId());
    }
}
