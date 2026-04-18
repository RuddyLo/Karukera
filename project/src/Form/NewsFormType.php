<?php

namespace App\Form;

use App\Entity\News;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;

class NewsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'row_attr' => [
                    'class' => 'form-group col-sm-6 m-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ],
                
            ])
            ->add('locale', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Langue',
                'choices' => [
                    'Français' => 'fr',
                    'English' => 'en',
                ],
                'row_attr' => [
                    'class' => 'form-group col-sm-6 m-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Activer cette nouveauté',
                'required' => false,
            
            ])
            ->add('images', FileType::class, [
    'label' => 'Ajouter des images',
    'mapped' => false,
    'multiple' => true,
    'required' => false,
    'attr' => [
        'accept' => 'image/*',
        'class' => 'form-control',
        'style' => 'border: 2px dashed #ced4da; padding: 1rem; background: #f8f9fa;',
    ],
    'row_attr' => [
        'class' => 'mb-3',
    ],
    'constraints' => [
        new All([
            new File(mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])
        ])
    ],
]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => News::class,
        ]);
    }
}