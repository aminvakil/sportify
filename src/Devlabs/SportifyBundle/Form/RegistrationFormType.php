<?php

namespace Devlabs\SportifyBundle\Form;

use Devlabs\SportifyBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, array(
                'label' => 'Email',
                'error_bubbling' => true,
            ))
            ->add('username', null, array(
                'label' => 'Username',
                'error_bubbling' => true,
            ))
            ->add('slackUsername', null, array(
                'label' => 'Slack username',
                'error_bubbling' => true,
            ))
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'first_options' => array('label' => 'Password'),
                'second_options' => array('label' => 'Password confirmation'),
                'invalid_message' => 'The password fields must match.',
                'error_bubbling' => true,
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => User::class,
        ));
    }

    public function getBlockPrefix(): string
    {
        return 'fos_user_registration_form';
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
