<?php

namespace Devlabs\SportifyBundle\Controller;

use Devlabs\SportifyBundle\Entity\User;
use Devlabs\SportifyBundle\Form\ResettingFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResettingController extends AbstractController
{
    const TOKEN_TTL = 86400;

    public function requestAction()
    {
        return $this->render('Resetting/request.html.twig', array('invalid_username' => null));
    }

    public function sendEmailAction(Request $request)
    {
        $username = $request->request->get('username');
        $user = $this->findUser($username);

        if (!$user) {
            return $this->render('Resetting/request.html.twig', array('invalid_username' => $username));
        }

        if (!$user->isPasswordRequestNonExpired(self::TOKEN_TTL)) {
            $user->setConfirmationToken($this->generateToken());
            $user->setPasswordRequestedAt(new \DateTime());
            $this->getDoctrine()->getManager()->flush();
            $this->sendResettingEmail($user);
        }

        return $this->redirectToRoute('fos_user_resetting_check_email');
    }

    public function checkEmailAction()
    {
        return $this->render('Resetting/checkEmail.html.twig');
    }

    public function resetAction(Request $request, $token)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('confirmationToken' => $token));

        if (!$user || !$user->isPasswordRequestNonExpired(self::TOKEN_TTL)) {
            throw new NotFoundHttpException('Invalid password reset token.');
        }

        $form = $this->createForm(ResettingFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($this->get('security.password_encoder')->encodePassword($user, $user->getPlainPassword()));
            $user->eraseCredentials();
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->render('Resetting/reset.html.twig', array(
            'form' => $form->createView(),
            'token' => $token,
        ));
    }

    private function findUser($username)
    {
        if (!$username) {
            return null;
        }

        try {
            return $this->get('app.user_provider')->loadUserByUsername($username);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function sendResettingEmail(User $user)
    {
        $confirmationUrl = $this->generateUrl('fos_user_resetting_reset', array(
            'token' => $user->getConfirmationToken(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);

        $context = array('user' => $user, 'confirmationUrl' => $confirmationUrl);
        $template = $this->get('twig')->load('templates/emails/password_resetting.email.twig');

        $message = (new \Swift_Message())
            ->setSubject(trim($template->renderBlock('subject', $context)))
            ->setFrom($this->getParameter('mailer_sender_address'))
            ->setTo($user->getEmail())
            ->setBody($template->renderBlock('body_text', $context), 'text/plain')
            ->addPart($template->renderBlock('body_html', $context), 'text/html')
        ;

        $this->get('mailer')->send($message);
    }
}
