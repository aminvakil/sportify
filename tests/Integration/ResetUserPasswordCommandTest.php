<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

use Devlabs\SportifyBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ResetUserPasswordCommandTest extends DatabaseTestCase
{
    public function testResetUserPassword()
    {
        $user = $this->createUser('reset_user');
        $passwordHasher = self::$kernel->getContainer()->get('security.user_password_hasher');
        $user->setPassword($passwordHasher->hashPassword($user, 'old-password'));
        $user->setConfirmationToken('existing-token');
        $user->setPasswordRequestedAt(new \DateTime('-1 hour'));
        $this->em->flush();

        $tester = $this->executeCommand(array(
            'email' => 'RESET_USER@example.com',
            '--password' => 'new-password',
        ));

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(array('emailCanonical' => 'reset_user@example.com'));

        $this->assertNotNull($user);
        $this->assertTrue($passwordHasher->isPasswordValid($user, 'new-password'));
        $this->assertFalse($passwordHasher->isPasswordValid($user, 'old-password'));
        $this->assertNull($user->getConfirmationToken());
        $this->assertNull($user->getPasswordRequestedAt());
        $this->assertStringContainsString('Password for user reset_user@example.com was reset.', $tester->getDisplay());
    }

    public function testCommandRefusesDisabledUnconfirmedUser()
    {
        $user = $this->createUser('unconfirmed_user', false);
        $passwordHasher = self::$kernel->getContainer()->get('security.user_password_hasher');
        $user->setPassword($passwordHasher->hashPassword($user, 'old-password'));
        $user->setConfirmationToken('registration-token');
        $user->setPasswordRequestedAt(null);
        $this->em->flush();

        $tester = $this->executeCommand(array(
            'email' => 'unconfirmed_user@example.com',
            '--password' => 'new-password',
        ));

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('User unconfirmed_user@example.com is disabled or unconfirmed. Enable or confirm the account before resetting its password.', $tester->getDisplay());

        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(array('emailCanonical' => 'unconfirmed_user@example.com'));

        $this->assertNotNull($user);
        $this->assertFalse($user->isEnabled());
        $this->assertSame('registration-token', $user->getConfirmationToken());
        $this->assertNull($user->getPasswordRequestedAt());
        $this->assertTrue($passwordHasher->isPasswordValid($user, 'old-password'));
        $this->assertFalse($passwordHasher->isPasswordValid($user, 'new-password'));
    }

    public function testCommandRefusesUnknownUser()
    {
        $tester = $this->executeCommand(array(
            'email' => 'missing@example.com',
            '--password' => 'new-password',
        ));

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('User missing@example.com was not found.', $tester->getDisplay());
    }

    private function executeCommand(array $arguments)
    {
        $application = new Application(self::$kernel);
        $command = $application->find('sportify:user:reset-password');
        $tester = new CommandTester($command);
        $tester->execute($arguments);

        return $tester;
    }
}
