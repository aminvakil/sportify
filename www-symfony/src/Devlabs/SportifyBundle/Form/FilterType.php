<?php

namespace Devlabs\SportifyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class FilterType extends AbstractType
{
    protected $data;

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->data = $options['data'];

        $builder
            ->add('tournament_id', EntityType::class, array(
                'class' => 'DevlabsSportifyBundle:Tournament',
                'choices' => $this->data['tournaments_joined'],
                'choice_label' => 'name',
                'label' => false,
                'data' => $this->data['tournament_selected']
            ))
            ->add('date_from', TextType::class, array(
                'label' => false,
                'data' => $this->data['date_from']
            ))
            ->add('date_to', TextType::class, array(
                'label' => false,
                'data' => $this->data['date_to']
            ))
            ->add('button', SubmitType::class, array('label' => 'FILTER'))
        ;
    }
}
