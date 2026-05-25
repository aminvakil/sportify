<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

use Devlabs\SportifyBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CreateAdminUserCommandTest extends DatabaseTestCase
{
    public function testCreateInitialAdminUser()
    {
        $tester = $this->executeCommand(array(
            'email' => 'Admin@Example.com',
            'username' => 'admin_user',
            '--password' => 'admin-password',
            '--slack-username' => 'admin-slack',
        ));

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(array('emailCanonical' => 'admin@example.com'));

        $this->assertNotNull($user);
        $this->assertSame('Admin@Example.com', $user->getEmail());
        $this->assertSame('admin_user', $user->getUsername());
        $this->assertSame('admin_user', $user->getUsernameCanonical());
        $this->assertSame('admin-slack', $user->getSlackUsername());
        $this->assertTrue($user->isEnabled());
        $this->assertTrue($user->hasRole('ROLE_ADMIN'));
        $this->assertTrue(self::$kernel->getContainer()->get('security.user_password_hasher')->isPasswordValid($user, 'admin-password'));
    }

    public function testCommandRefusesToCreateSecondAdminUser()
    {
        $admin = $this->createUser('existing_admin');
        $admin->addRole('ROLE_ADMIN');
        $this->em->flush();

        $tester = $this->executeCommand(array(
            'email' => 'second-admin@example.com',
            'username' => 'second_admin',
            '--password' => 'admin-password',
        ));

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('An admin user already exists.', $tester->getDisplay());
        $this->assertNull($this->em->getRepository(User::class)->findOneBy(array('emailCanonical' => 'second-admin@example.com')));
    }

    private function executeCommand(array $arguments)
    {
        $application = new Application(self::$kernel);
        $command = $application->find('sportify:user:create-admin');
        $tester = new CommandTester($command);
        $tester->execute($arguments);

        return $tester;
    }
}
