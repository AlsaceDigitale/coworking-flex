<?php

namespace App\Form;

use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lastname', TextType::class, [
                'label' => 'Nom'
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom'
            ])
            ->add('society', TextType::class, [
                'label' => 'Société (facultatif)',
                'required' => false
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays'
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse'
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville'
            ])
            ->add('zip', TextType::class, [
                'label' => 'Code postal'
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone'
            ])
            ->add('mail', EmailType::class, [
                'label' => 'E-mail'
            ])
            ->add('username', TextType::class, [
                'label' => 'Pseudo'
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe'
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirmation du mot de passe'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
