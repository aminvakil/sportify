<?php

namespace Devlabs\SportifyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SecurityController extends AbstractController
{
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        return $this->render('Security/login.html.twig', array(
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'csrf_token' => $this->get('security.csrf.token_manager')->getToken('authenticate'),
        ));
    }

    public function checkAction()
    {
        throw new \LogicException('This route is handled by the firewall.');
    }

    public function logoutAction()
    {
        throw new \LogicException('This route is handled by the firewall.');
    }
}
