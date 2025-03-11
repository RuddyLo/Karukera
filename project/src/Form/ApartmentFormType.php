<?php

namespace App\Form;

use App\Entity\Apartment;
use Doctrine\DBAL\Types\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType as TypeTextType;
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
            ->add('name', TypeTextType::class, [
                'label' => "Dénomination de l'appartement",
                'row_attr' => [
                    'class' => 'form-group col-sm-12 m-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    
                ]
                ])
                ->add('description', TextareaType::class, [
                    'label' => 'Description',
                    'row_attr' => [
                        'class' => 'form-group col-12 m-2'
                    ],
                    'attr' => [
                        'class' => 'form-control',
                        'rows' => 6
                    ]
                ])
                ->add('is_active', ChoiceType::class, [
                    'label' => 'Afficher',
                    'row_attr' => [
                        'class' => 'form-group col-md-6 my-2'
                    ],
                    'attr' => [
                        'class' => 'form-control',
                    ],
                    'choices' => $yes_or_not
                ])
                ->add('is_favorite', ChoiceType::class, [
                    'label' => 'Mettre dans à la une',
                    'row_attr' => [
                        'class' => 'form-group col-md-6 my-2'
                    ],
                    'attr' => [
                        'class' => 'form-control',
                    ],
                    'choices' => $yes_or_not
                ])
                ->add('imageUrl', FileType::class, [
                    'mapped' => false,
                    'label' => false,
                    'required' => false,
                    'row_attr' => [
                        'class' => 'form-group col-12',
                    ],
                    'attr'      => [
                        'accept' => 'image/*',
                        'class' => 'd-none',
                        
                    ]
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
