<?php

namespace Tests\Functional;

use Devlabs\SportifyBundle\Entity\User;

require_once __DIR__.'/FunctionalTestCase.php';

class AuthFlowTest extends FunctionalTestCase
{
    public function testInvalidLoginShowsAnErrorAndDoesNotAuthenticateUser()
    {
        $this->createUser('login_failure', 'correct-password');

        $response = $this->login('login_failure@example.com', 'wrong-password');
        $this->assertTrue($response->isRedirect());
        $this->assertMatchesRegularExpression('#/login$#', $response->headers->get('Location'));

        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Invalid credentials.', $crawler->filter('body')->text(null, false));

        $this->client->request('GET', '/user/profile');
        $this->assertTrue($this->client->getResponse()->isRedirect('/login'));
    }

    public function testLoggedInUserCanLogout()
    {
        $this->createUser('logout_user', 'testpass');
        $this->login('logout_user@example.com', 'testpass');
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->request('GET', '/logout');
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertMatchesRegularExpression('#/$#', $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', '/user/profile');
        $this->assertTrue($this->client->getResponse()->isRedirect('/login'));
    }

    public function testDuplicateRegistrationShowsValidationError()
    {
        $this->createUser('duplicate_user', 'testpass');

        $this->register('duplicate_user@example.com', 'testpass', 'duplicate_user');

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertStringContainsString('already exists', $this->client->getResponse()->getContent());
    }

    public function testRegistrationRequiresMatchingPasswords()
    {
        $crawler = $this->client->request('GET', '/register/');
        $form = $crawler->selectButton('Confirm')->form(array(
            'fos_user_registration_form[email]' => 'password-mismatch@example.com',
            'fos_user_registration_form[username]' => 'password-mismatch@example.com',
            'fos_user_registration_form[slackUsername]' => 'password-mismatch',
            'fos_user_registration_form[plainPassword][first]' => 'first-password',
            'fos_user_registration_form[plainPassword][second]' => 'second-password',
        ));
        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertStringContainsString('The password fields must match.', $this->client->getResponse()->getContent());
        $this->assertNull($this->em->getRepository(User::class)->findOneBy(array('email' => 'password-mismatch@example.com')));
    }

    public function testPasswordResetRequestAndResetAllowLoginWithNewPassword()
    {
        $user = $this->createUser('reset_user', 'old-password');

        $this->client->request('POST', '/resetting/send-email', array('username' => 'reset_user@example.com'));
        $this->assertTrue($this->client->getResponse()->isRedirect('/resetting/check-email'));

        $this->em->clear();
        $user = $this->em->getRepository(User::class)->find($user->getId());
        $token = $user->getConfirmationToken();
        $this->assertNotEmpty($token);
        $this->assertNotNull($user->getPasswordRequestedAt());

        $crawler = $this->client->request('GET', '/resetting/reset/'.$token);
        $form = $crawler->selectButton('Submit')->form(array(
            'fos_user_resetting_form[plainPassword][first]' => 'new-password',
            'fos_user_resetting_form[plainPassword][second]' => 'new-password',
        ));
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect('/login'));

        $this->client->restart();
        $this->login('reset_user@example.com', 'new-password');
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    public function testProfilePasswordChangeAllowsLoginWithNewPassword()
    {
        $this->createUser('profile_user', 'old-password');
        $this->login('profile_user@example.com', 'old-password');

        $crawler = $this->client->request('GET', '/user/profile');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $form = $crawler->selectButton('Submit changes')->form(array(
            'user[username]' => 'profile_user',
            'user[password_confirm]' => 'old-password',
            'user[password][first]' => 'new-password',
            'user[password][second]' => 'new-password',
        ));
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertStringContainsString('Your profile was updated successfully!', $this->client->getResponse()->getContent());

        $this->client->restart();
        $this->login('profile_user@example.com', 'new-password');
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }
}
