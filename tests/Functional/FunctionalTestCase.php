<?php

namespace Tests\Functional;

use Devlabs\SportifyBundle\Entity\OAuthClient;
use Devlabs\SportifyBundle\Entity\User;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalTestCase extends WebTestCase
{
    protected $client;
    protected $em;

    protected function setUp()
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->resetDatabase();
    }

    protected function tearDown()
    {
        if ($this->em) {
            $this->em->close();
        }

        $this->em = null;
        $this->client = null;

        parent::tearDown();
    }

    protected function resetDatabase()
    {
        $connection = $this->em->getConnection();
        $schemaManager = $connection->getSchemaManager();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        foreach ($schemaManager->listTableNames() as $tableName) {
            $connection->executeQuery('DROP TABLE IF EXISTS '.$connection->quoteIdentifier($tableName));
        }
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($metadata);
    }

    protected function createUser($username, $password = 'testpass', $enabled = true, array $roles = array())
    {
        $user = new User();
        $user->setUsername($username);
        $user->setUsernameCanonical(mb_strtolower($username, 'UTF-8'));
        $user->setEmail($username.'@example.com');
        $user->setEmailCanonical(mb_strtolower($username.'@example.com', 'UTF-8'));
        $user->setSlackUsername($username);
        $user->setEnabled($enabled);
        $user->setRoles($roles);
        $user->setPassword($this->client->getContainer()->get('security.password_encoder')->encodePassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function login($usernameOrEmail, $password = 'testpass')
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Login')->form(array(
            '_username' => $usernameOrEmail,
            '_password' => $password,
        ));
        $this->client->submit($form);

        return $this->client->getResponse();
    }

    protected function register($email, $password = 'testpass', $username = null, $slackUsername = 'test-slack')
    {
        $crawler = $this->client->request('GET', '/register/');
        $form = $crawler->selectButton('Confirm')->form(array(
            'fos_user_registration_form[email]' => $email,
            'fos_user_registration_form[username]' => $username ?: $email,
            'fos_user_registration_form[slackUsername]' => $slackUsername,
            'fos_user_registration_form[plainPassword][first]' => $password,
            'fos_user_registration_form[plainPassword][second]' => $password,
        ));

        return $this->client->submit($form);
    }

    protected function createOAuthClient(array $grantTypes = array('password'))
    {
        $client = new OAuthClient();
        $client->setName('Test client');
        $client->setRedirectUris(array('http://localhost/'));
        $client->setAllowedGrantTypes($grantTypes);

        $this->em->persist($client);
        $this->em->flush();

        return $client;
    }
}
