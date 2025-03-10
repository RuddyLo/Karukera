<?php

namespace App\Form;

use App\Entity\Apartment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApartmentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $yes_or_not = [
            'Non' => false,
            'Oui' => true
        ];
        $builder
            ->add('name', TextareaType::class, [
                'label' => "Dénomination de l'appartement",
                'row_attr' => [
                    'class' => 'form-group col-sm-12',
                ],
                'attr' => [
                    'class' => 'form-control',
                    
                ]
                ])
                ->add('description', TextareaType::class, [
                    'label' => 'Description',
                    'row_attr' => [
                        'class' => 'form-group col-12'
                    ],
                    'attr' => [
                        'class' => 'form-control',
                        'rows' => 6
                    ]
                ])
                ->add('is_active', ChoiceType::class, [
                    'label' => 'Afficher',
                    'row_attr' => [
                        'class' => 'form-group col-md-6'
                    ],
                    'attr' => [
                        'class' => 'form-control',
                    ],
                    'choices' => $yes_or_not
                ])
                ->add('is_favorite', ChoiceType::class, [
                    'label' => 'Mettre dans à la une',
                    'row_attr' => [
                        'class' => 'form-group col-md-6'
                    ],
                    'attr' => [
                        'class' => 'form-control',
                    ],
                    'choices' => $yes_or_not
                ])
            
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Apartment::class,
        ]);
    }
}
