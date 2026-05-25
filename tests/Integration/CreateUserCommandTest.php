<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

use Devlabs\SportifyBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends DatabaseTestCase
{
    public function testCreateRegularUser()
    {
        $tester = $this->executeCommand(array(
            'email' => 'User@Example.com',
            'username' => 'regular_user',
            '--password' => 'user-password',
            '--slack-username' => 'user-slack',
        ));

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(array('emailCanonical' => 'user@example.com'));

        $this->assertNotNull($user);
        $this->assertSame('User@Example.com', $user->getEmail());
        $this->assertSame('regular_user', $user->getUsername());
        $this->assertSame('regular_user', $user->getUsernameCanonical());
        $this->assertSame('user-slack', $user->getSlackUsername());
        $this->assertTrue($user->isEnabled());
        $this->assertTrue($user->hasRole('ROLE_USER'));
        $this->assertFalse($user->hasRole('ROLE_ADMIN'));
        $this->assertTrue(self::$kernel->getContainer()->get('security.user_password_hasher')->isPasswordValid($user, 'user-password'));
    }

    public function testCommandRefusesExistingUser()
    {
        $this->createUser('existing_user');

        $tester = $this->executeCommand(array(
            'email' => 'existing_user@example.com',
            'username' => 'new_username',
            '--password' => 'user-password',
        ));

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('A user with this username or email already exists.', $tester->getDisplay());
        $this->assertNull($this->em->getRepository(User::class)->findOneBy(array('usernameCanonical' => 'new_username')));
    }

    private function executeCommand(array $arguments)
    {
        $application = new Application(self::$kernel);
        $command = $application->find('sportify:user:create');
        $tester = new CommandTester($command);
        $tester->execute($arguments);

        return $tester;
    }
}
