<?php

namespace Devlabs\SportifyBundle\Controller;

use Devlabs\SportifyBundle\Entity\OAuthAccessToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Devlabs\SportifyBundle\Form\UserType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class UserController extends AbstractController
{
    public function profileAction(Request $request)
    {
        // if user is not logged in, redirect to login page
        if (!is_object($user = $this->getUser())) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        $form = $this->createForm(UserType::class, $user, array(
            'action' => $this->generateUrl('user_profile'),
            'method' => 'POST',
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();

            if ($plainPassword) {
                $user->setPassword($this->get('security.password_encoder')->encodePassword($user, $plainPassword));
            }

            $user->setUsernameCanonical(mb_strtolower($user->getUsername(), 'UTF-8'));
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'notice',
                'Your profile was updated successfully!'
            );
        }

        // get user standings and set them as global Twig var
        $this->get('app.twig.helper')->setUserScores($user);

        return $this->render(
            'User/profile.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

    public function tokensAction(Request $request)
    {
        // if user is not logged in, redirect to login page
        if (!is_object($user = $this->getUser())) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        $accessToken = $this->getDoctrine()->getManager()
            ->getRepository(OAuthAccessToken::class)
            ->getLastNotExpired($user);

        $formData = array();
        $form = $this->createFormBuilder($formData)
            ->add('password', PasswordType::class)
            ->add('button', SubmitType::class, array(
                'label' => 'Request token'
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $formData = $form->getData();

            try {
                $this->get('app.oauth_token_issuer')->issuePasswordGrantToken(
                    $this->getParameter('sportify_api.client_id'),
                    $this->getParameter('sportify_api.client_secret'),
                    $user->getUsername(),
                    $formData['password']
                );

                $flashMsg = 'Successfully generated token.';
            } catch (AuthenticationException $e) {
                $flashMsg = $e->getMessage();
            }

            $this->get('session')->getFlashBag()->add('message', $flashMsg);

            return $this->redirectToRoute('user_tokens');
        }

        // get user standings and set them as global Twig var
        $this->get('app.twig.helper')->setUserScores($user);

        return $this->render(
            'User/tokens.html.twig',
            array(
                'access_token' => $accessToken,
                'form' => $form->createView()
            )
        );
    }
}
