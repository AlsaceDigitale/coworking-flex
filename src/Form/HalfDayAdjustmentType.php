<?php

namespace App\Form;

use App\Entity\HalfDayAdjustment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HalfDayAdjustmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('customer_id')
            ->add('counteradd')
            ->add('counterremove')
            ->add('arrival_month')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => HalfDayAdjustment::class,
        ]);
    }
}
