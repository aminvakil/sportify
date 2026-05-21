<?php

namespace Tests\Functional;

use Devlabs\SportifyBundle\Entity\User;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationLoginTest extends WebTestCase
{
    public function testRegisteredUserCanLoginInLocalDevConfiguration()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $this->resetDatabase($em);

        $email = 'registered-login@example.com';
        $password = 'testpass';

        $crawler = $client->request('GET', '/register/');
        $form = $crawler->selectButton('Confirm')->form(array(
            'fos_user_registration_form[email]' => $email,
            'fos_user_registration_form[username]' => $email,
            'fos_user_registration_form[slackUsername]' => 'registered-login',
            'fos_user_registration_form[plainPassword][first]' => $password,
            'fos_user_registration_form[plainPassword][second]' => $password,
        ));
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/register/confirmed'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $user = $em->getRepository(User::class)->findOneBy(array('email' => $email));
        $this->assertNotNull($user);
        $this->assertTrue($user->isEnabled());
        $this->assertNull($user->getConfirmationToken());

        $client->restart();
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Login')->form(array(
            '_username' => $email,
            '_password' => $password,
        ));
        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertMatchesRegularExpression('#/$#', $client->getResponse()->headers->get('Location'));
    }

    private function resetDatabase($em)
    {
        $connection = $em->getConnection();
        $schemaManager = $connection->createSchemaManager();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        foreach ($schemaManager->listTableNames() as $tableName) {
            $connection->executeQuery('DROP TABLE IF EXISTS '.$connection->quoteIdentifier($tableName));
        }
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($metadata);
    }
}
