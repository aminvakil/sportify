<?php

namespace Devlabs\SportifyBundle\Form;

use Devlabs\SportifyBundle\Entity\Team;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class TeamChoiceType extends AbstractType
{
    protected $data;
    protected $choices;

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => 'Devlabs\SportifyBundle\Entity\Team',
            'choices' => null
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->data = $options['data'];
        $this->choices = $options['choices'];

        $builder
            ->add('id', EntityType::class, array(
                'class' => Team::class,
                'choices' => $this->choices,
                'choice_label' => 'name',
                'label' => false,
                'data' => $this->data
            ))
        ;
    }
}
