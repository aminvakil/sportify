<?php

namespace Devlabs\SportifyBundle\Controller;

use Devlabs\SportifyBundle\Entity\User;
use Devlabs\SportifyBundle\Form\RegistrationFormType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    public function registerAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $confirmationEnabled = $this->getParameter('app.registration_confirmation_enabled');
            $this->updateCanonicalFields($user);
            $user->setEnabled(!$confirmationEnabled);
            $user->setPassword($this->container->get('security.user_password_hasher')->hashPassword($user, $user->getPlainPassword()));
            $user->eraseCredentials();

            if ($confirmationEnabled) {
                $user->setConfirmationToken($this->generateToken());
            }

            $em = $this->container->get('doctrine')->getManager();
            $em->persist($user);

            try {
                $em->flush();
            } catch (UniqueConstraintViolationException $e) {
                $form->addError(new FormError('A user with this username or email already exists.'));

                return $this->render('Registration/register.html.twig', array('form' => $form->createView()));
            }

            if ($confirmationEnabled) {
                $this->sendConfirmationEmail($user);

                return $this->redirectToRoute('fos_user_registration_check_email');
            }

            return $this->redirectToRoute('fos_user_registration_confirmed');
        }

        return $this->render('Registration/register.html.twig', array('form' => $form->createView()));
    }

    public function checkEmailAction()
    {
        return $this->render('Registration/checkEmail.html.twig');
    }

    public function confirmAction($token)
    {
        $user = $this->container->get('doctrine')->getRepository(User::class)->findOneBy(array('confirmationToken' => $token));

        if (!$user) {
            throw new NotFoundHttpException('Invalid confirmation token.');
        }

        $user->setEnabled(true);
        $user->setConfirmationToken(null);
        $this->container->get('doctrine')->getManager()->flush();

        return $this->redirectToRoute('fos_user_registration_confirmed');
    }

    public function confirmedAction()
    {
        return $this->render('Registration/confirmed.html.twig', array('targetUrl' => null));
    }

    private function updateCanonicalFields(User $user)
    {
        $user->setUsernameCanonical($this->canonicalize($user->getUsername()));
        $user->setEmailCanonical($this->canonicalize($user->getEmail()));
    }

    private function canonicalize($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    private function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function sendConfirmationEmail(User $user)
    {
        $confirmationUrl = $this->generateUrl('fos_user_registration_confirm', array(
            'token' => $user->getConfirmationToken(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);

        $this->sendTemplatedEmail('templates/emails/registration.email.twig', $user, $confirmationUrl);
    }

    private function sendTemplatedEmail($template, User $user, $confirmationUrl)
    {
        $context = array('user' => $user, 'confirmationUrl' => $confirmationUrl);
        $twigTemplate = $this->container->get('twig')->load($template);

        $message = (new Email())
            ->subject(trim($twigTemplate->renderBlock('subject', $context)))
            ->from($this->getParameter('mailer_sender_address'))
            ->to($user->getEmail())
            ->text($twigTemplate->renderBlock('body_text', $context))
            ->html($twigTemplate->renderBlock('body_html', $context))
        ;

        $this->container->get('mailer')->send($message);
    }
}
