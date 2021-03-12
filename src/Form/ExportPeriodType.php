<?php

namespace App\Form;

use App\Entity\ExportPeriod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportPeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('beginDate', DateType::class, [
                'label' => 'Début de la période d\'export',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => [
                    'class' => 'js-datepicker'
                ]
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Fin de la période d\'export',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => [
                    'class' => 'js-datepicker'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ExportPeriod::class,
        ]);
    }
}
