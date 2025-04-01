<?php

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\Equipment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('name', TextType::class, [
                'label' => "Dénomination de l'appartement",
                'row_attr' => [
                    'class' => 'form-group col-sm-12 m-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ],

            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'row_attr' => [
                    'class' => 'form-group col-12 m-2'
                ],
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'required' => true,
                ],

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
            ->add('images', FileType::class, [
                'label' => "Images de l'appartement ",
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'row_attr' => ['class' => 'form-group col-12'],
                'attr' => [

                    'accept' => 'image/*',
                    'class' => 'form-control mt-4',
                ],
            ])

            ->add('equipments', EntityType::class, [
                'class' => Equipment::class, // On indique l'entité Equipment
                'choice_label' => 'name', // Affiche le nom des équipements
                'multiple' => true, // Permet la sélection multiple
                'expanded' => false, // Utilisation d'une liste déroulante
                'attr' => ['class' => 'dropdown-checkbox form-select'], // Ajout d’une classe pour le style
                'label' => 'Équipements',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Apartment::class,
        ]);
    }
}
