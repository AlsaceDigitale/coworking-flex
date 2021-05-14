<?php

namespace App\Form;

use App\Entity\HomeTexts;
use App\Entity\Options;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextHomeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstText', CKEditorType::class)
            ->add('firstActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Activer le premier text'
            ])
            ->add('firstPictureFile', FileType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('secondText', CKEditorType::class)
            ->add('secondActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Activer le deuxième text'
            ])
            ->add('secondPictureFile', FileType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('thirdText', CKEditorType::class)
            ->add('thirdActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Activer le troisième text'
            ])
            ->add('thirdPictureFile', FileType::class, [
                'required' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => HomeTexts::class,
        ]);
    }
}
