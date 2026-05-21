<?php

namespace Devlabs\SportifyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('current_password', PasswordType::class, array(
                'constraints' => new UserPassword(),
                'mapped' => false,
                'error_bubbling' => true,
            ))
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'first_options' => array('label' => 'New password'),
                'second_options' => array('label' => 'Confirm password'),
                'invalid_message' => 'The password fields must match.',
                'error_bubbling' => true,
            ))
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'fos_user_change_password_form';
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
