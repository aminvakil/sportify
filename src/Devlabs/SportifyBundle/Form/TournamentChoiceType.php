<?php

namespace Devlabs\SportifyBundle\Form;

use Devlabs\SportifyBundle\Entity\Tournament;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class TournamentChoiceType extends AbstractType
{
    protected $data;
    protected $choices;

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => 'Devlabs\SportifyBundle\Entity\Tournament',
            'choices' => null
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->data = $options['data'];
        $this->choices = $options['choices'];

        $builder
            ->add('id', EntityType::class, array(
                'class' => Tournament::class,
                'choices' => $this->choices,
                'data' => $this->data,
                'choice_label' => 'name',
                'label' => false,
                'placeholder' => 'Select tournament'
            ))
        ;
    }
}
