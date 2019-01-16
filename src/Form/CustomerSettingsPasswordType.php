<?php

namespace App\Form;

use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerSettingsPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'old_password',
                PasswordType::class,
                [
                'label' => 'Ancien mot de passe',
                'mapped' => false,
                ]
            )
            ->add(
                'password',
                PasswordType::class,
                [
                'label' => 'Nouveau mot de passe'
                ]
            )
            ->add(
                'confirm_password',
                PasswordType::class,
                [
                'label' => 'Confirmation du mot de passe'
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
            'data_class' => Customer::class,
            ]
        );
    }
}
