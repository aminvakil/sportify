<?php

namespace Devlabs\SportifyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;

class ResettingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', RepeatedType::class, array(
            'type' => PasswordType::class,
            'first_options' => array('label' => 'New password'),
            'second_options' => array('label' => 'Confirm password'),
            'invalid_message' => 'The password fields must match.',
            'error_bubbling' => true,
        ));
    }

    public function getBlockPrefix()
    {
        return 'fos_user_resetting_form';
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
